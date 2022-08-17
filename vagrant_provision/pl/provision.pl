#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
# Note that we are restricted to core perl as this script is run *before* we can
# get any additional perl libraries.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catdir catfile);
use FindBin qw($RealBin);
use lib $RealBin;
use config;

# main()
# Root required
print STDERR "Root required.\n" and exit 1 if ($> != 0);

update_ubuntu();
prepare_cache();
setup_debug();
setup_lmtools();
setup_mysql();
setup_database();
setup_logrotate();
setup_php();
setup_apache();
# setup_composer();  # Composer disabled.
create_symlink();
setup_other_tweaks();
copy_code();

# Done!
print "All done!\n";
exit 0;

# Help with logging executed commands and their results.
sub exec_cmd {
    my $cmd = shift;
    print "\$ ${cmd}\n";
    print STDERR "${cmd} exited ", $? >> 8, "\n" and exit 1 if ((system $cmd) != 0);
    print "\n";
}

# Run Ubuntu updates and install required Ubuntu packages
sub update_ubuntu {
    my @required_packages = @CONFIG::REQUIRED_PACKAGES;

    exec_cmd("apt-get -q update");

    # This prevents grub-pc from calling up a user interactive menu that will halt provisioning.
    exec_cmd("DEBIAN_FRONTEND=noninteractive apt-get -qy -o DPkg::options::='--force-confdef' -o DPkg::options::='--force-confold' dist-upgrade");

    foreach (@required_packages) {
        exec_cmd("apt-get -qy install $_");
    }
}

# Prepare cache directory
sub prepare_cache {
    my $dest = catdir(@CONFIG::CACHE_PATH);
    my $uid  = $CONFIG::CACHE_OWNER_UID;
    my $gid  = $CONFIG::CACHE_OWNER_GID;

    mkdir $dest, 0701;
    chown $uid, $gid, $dest;
    print "Created cache file directory: ${dest}\n";
}

# Dir to write files to help with debugging.  ie. /home/vagrant/debug
# i.e. To write debugging logs.  q.v. function log_var() in common.php
sub setup_debug {
    my $dest = catdir(@CONFIG::DEBUG_PATH);
    my $uid = $CONFIG::DEBUG_UID;
    my $gid = $CONFIG::DEBUG_GID;
    my $permissions = $CONFIG::DEBUG_PERMISSIONS;

    mkdir $dest;
    chown $uid, $gid, $dest;
    chmod $permissions, $dest;
    print "Created debugging directory: ${dest}\n";
}

# Copy LM tools binaries to system.
sub setup_lmtools {
    my @source_path    = (@CONFIG::REPO_PATH, "vagrant_provision", "lmtools");
    my @dest_path      = @CONFIG::LMTOOLS_PATH;
    my @lm_files       = @CONFIG::LMTOOLS_FILES;
    my $lm_permissions = $CONFIG::LMTOOLS_PERMISSIONS;
    # File ownership is something like "www-data:vagrant"
    my $lmtools_user   = $CONFIG::LMTOOLS_OWNER;
    my $uid            = $CONFIG::LMTOOLS_OWNER_UID;
    my $vagrant_user   = $CONFIG::VAGRANT_USER;
    my $gid            = $CONFIG::VAGRANT_GID;
    my ($source, $dest);

    $dest = catdir(@dest_path);
    mkdir $dest, 0701;
    print "Created directory: ${dest}\n";
    foreach (@lm_files) {
        $source = catfile(@source_path, $_);
        $dest = catfile(@dest_path, $_);

        # autodie doesn't work with File::Copy
        print STDERR "LM binary $_: $!\n" and exit 1 unless copy $source, $dest;
        print "Copied LM binary $_\n";

        chown $uid, $gid, $dest;
        print "$_ ownership granted to ${lmtools_user}:${vagrant_user}\n";

        chmod $lm_permissions, $dest;
        printf "$_ permissions set to 0%o\n", $lm_permissions;
    }
}

sub setup_mysql {
    my @db_config_path = @CONFIG::DB_CONFIG_PATH;
    my $db_config_file = $CONFIG::DB_CONFIG_FILE;
    my ($source, $dest, $file, $ip, @hosts, @filtered_hosts);

    # IP address to bind MySQL to.
    # The VM is supposed to have only one IP address outside of 127.0.0.0/8 and
    # it should be within 10.0.2.0/24 via Virtualbox's NAT.  So, `hostname -I`
    # should return only one result.  Due to a small level of uncertainty, we'll
    # specifically use the first result found within 10.0.2.0/24.
    @hosts = split / /, `hostname -I`;
    chomp (@filtered_hosts = grep /10\.0\.2\.\d{1,3}/, @hosts);
    $ip = $filtered_hosts[0];
    print STDERR "IP within 10.0.2.0/24 expected, not found.  MySQL may not be accessible.\nIP(s) found: @{hosts}\n" if (!defined $ip);

    # bind IP in cfg if IP was found.
    if (defined $ip) {
        $source = catfile(@db_config_path, $db_config_file);
        $dest = catfile(@db_config_path, "${db_config_file}.bak");

        # move original file to a backup
        rename $source, $dest;

        # Swap so we can read from backup ($source) and write adjustments to
        # original ($dest).
        $file = $source;
        $source = $dest;
        $dest = $file;

        open my $source_fh, "<:encoding(UTF-8)", $source;
        open my $dest_fh, ">:encoding(UTF-8)", $dest;
        while(<$source_fh>) {
            $_ =~ s/bind\-address\s+= 127\.0\.0\.1/bind\-address = $ip/g;
            print $dest_fh $_;
        }

        close $source_fh;
        close $dest_fh;
        print "MySQL configured to listen on localhost:53306.  Restarting MySQL...\n";
        exec_cmd("service mysql restart");
    } else {
        print STDERR "10.0.2.0/24 IP not found in VM.\nMySQL will not be listening on localhost:53306.\n";
    }
}

sub setup_database {
    my @repo_path = @CONFIG::REPO_PATH;
    my $sql_file  = $CONFIG::SQL_FILE;
    my @db_hosts  = @CONFIG::DB_HOSTS;
    my $db_name   = $CONFIG::DB_NAME;
    my $db_user   = $CONFIG::DB_USER;
    my $db_pass   = $CONFIG::DB_PASS;

    # (1) Create database
    print "\n";
    print "Setting up mysql database.  Password security warning can be ignored.\n";
    exec_cmd("mysql -e \"CREATE DATABASE ${db_name};\"");

    # (2) Create database user account (various connection hosts)
    foreach (@db_hosts) {
        exec_cmd("mysql -e \"CREATE USER '${db_user}'\@'$_' IDENTIFIED BY '${db_pass}';\"");
        exec_cmd("mysql -e \"GRANT ALL PRIVILEGES ON ${db_name}.* TO '${db_user}'\@'$_';\"");
    }

    exec_cmd("mysql -e \"FLUSH PRIVILEGES;\"");

    # (3) Setup database schema
    my $file = catfile(@repo_path, "database", $sql_file);
    exec_cmd("mysql --user=${db_user} --password=${db_pass} --database=${db_name} < ${file}");
}

# Setup logrotate for Apache error logs on the host.
sub setup_logrotate {
    my @source_path = (@CONFIG::REPO_PATH, "vagrant_provision", "logrotate");
    my @dest_path   = @CONFIG::LOGROTATE_PATH;
    my $source = catfile(@source_path, $CONFIG::LOGROTATE_CONF_FILE);
    my $dest   = catfile(@dest_path, $CONFIG::LOGROTATE_CONF_FILE);

    print "Copy logrotate conf file.\n";
    print STDERR $! and exit 1 unless copy($source, $dest);
    print "\n";
}

# Copy php.ini for development environment.
sub setup_php {
    my $php_dev_ini    = catfile(@CONFIG::PHP_INI_DEV_PATH, $CONFIG::PHP_INI_DEV_FILE);
    my $php_apache_ini = catfile(@CONFIG::PHP_INI_APACHE_PATH, $CONFIG::PHP_INI_FILE);
    my $php_cli_ini    = catfile(@CONFIG::PHP_INI_CLI_PATH, $CONFIG::PHP_INI_FILE);
    my ($source, $dest, $file);

    print "Copying php.ini for development environment.\n";

    # Working with PHP ini for Apache and CLI
    foreach $file ($php_apache_ini, $php_cli_ini) {
        # Backup original PHP ini files.
        $source = $file;
        $dest   = "${file}.bak";
        rename $source, $dest;

        # Copy development environment PHP ini.
        $source = $php_dev_ini;
        $dest   = $file;
        print STDERR $! and exit 1 unless copy($source, $dest);
    }
}

# Setup apache and conf files.
sub setup_apache {
    my ($files, $conf, $source, $dest, @working_path, @source_path, @dest_path);

    # (1) Disable all currently active conf files
    print "Setting up Apache2\n";
    @working_path = (@CONFIG::APACHE_PATH, "sites-enabled");
    $files = catfile(@working_path, "*");
    foreach (glob $files) {
        $conf = fileparse($_);
        $conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
        exec_cmd("a2dissite $conf");
    }

    # (2) Copy phpLicenseWatcher conf file
    print "Copy conf file.\n";
    @source_path = (@CONFIG::REPO_PATH, "vagrant_provision", "apache");
    @dest_path   = (@CONFIG::APACHE_PATH, "sites-available");
    $source = catfile(@source_path, $CONFIG::APACHE_CONF_FILE);
    $dest   = catfile(@dest_path, $CONFIG::APACHE_CONF_FILE);
    print STDERR $! and exit 1 unless copy($source, $dest);

    # (3) Activate phpLicenseWatcher Apache conf file
    print "Activate conf file.\n";
    $conf = $CONFIG::APACHE_CONF_FILE;
    $conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
    exec_cmd("a2ensite $conf");

    # (4) Restart Apache
    print "Restart Apache.\n";
    exec_cmd("apachectl restart");
}

# Run composer to retrieve PHP dependencies.  Composer cannot be run as superuser.
sub setup_composer {
    exec_cmd("su -c \"composer -d" . catfile(@CONFIG::REPO_PATH) . " install\" $CONFIG::VAGRANT_USER");
}

# Create convenient symlink
# '$ sudo perl ~/update' to updte latest code within testing server.
sub create_symlink {
    print "Create convenience symlinks.\n";
    my (@scripts, @links); # parralel arrays

    push @scripts, catfile(@CONFIG::REPO_PATH, "vagrant_provision", "pl", $CONFIG::UPDATE_CODE);
    push @scripts, catfile(@CONFIG::HTML_PATH, $CONFIG::LICENSE_UTIL);
    push @scripts, catfile(@CONFIG::HTML_PATH, $CONFIG::LICENSE_CACHE);

    push @links, catfile(@CONFIG::VAGRANT_HOMEPATH, "update");
    push @links, catfile(@CONFIG::VAGRANT_HOMEPATH, "license_util");
    push @links, catfile(@CONFIG::VAGRANT_HOMEPATH, "license_cache");

    my $arr_length = scalar @scripts;
    for my $i (0..$arr_length - 1) {
        symlink $scripts[$i], $links[$i];
    }
}

# Any other small misc stuff to do.
sub setup_other_tweaks {
}

# Call script to copy code files to HTML directory.
sub copy_code {
    print "Copying repository code.\n";
    my @working_path = (@CONFIG::REPO_PATH, "vagrant_provision", "pl");
    my $file = catfile(@working_path, $CONFIG::UPDATE_CODE);
    exec_cmd("perl $file full");
}
