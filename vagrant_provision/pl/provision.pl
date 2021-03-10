#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
# Note that we are restricted to core perl as this script is run *before* we can
# get any additional perl libraries.
use strict;
use warnings;
use autodie;
use File::Basename qw(dirname fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catdir catfile);
use lib dirname(__FILE__);
use config;

# main()
# Root required
print STDERR "Root required.\n" and exit 1 if ($> != 0);

update_ubuntu();
prepare_cache();
setup_flexlm();
setup_mysql();
setup_database();
setup_logrotate();
setup_apache();
# setup_composer();  # Composer disabled.
create_symlink();
copy_code();

# Done!
print "All done!\n";
exit 0;

# Help with logging executed commands and their results.
sub exec_cmd {
    my $cmd = shift;
    print "\$ $cmd\n";
    print STDERR "$cmd exited ", $? >> 8, "\n" and exit 1 if ((system $cmd) != 0);
    print "\n";
}

# Run Ubuntu updates and install required Ubuntu packages
sub update_ubuntu {
    exec_cmd("apt-get -q update");

    # This prevents grub-pc from calling up a user interactive menu that will halt provisioning.
    exec_cmd("DEBIAN_FRONTEND=noninteractive apt-get -qy -o DPkg::options::='--force-confdef' -o DPkg::options::='--force-confold' dist-upgrade");

    foreach (@CONFIG::REQUIRED_PACKAGES) {
        exec_cmd("apt-get -qy install $_");
    }
}

sub prepare_cache {
# Prepare cache directory
    my $dest = catdir(@CONFIG::CACHE_PATH);
    mkdir $dest, 0701;
    chown $CONFIG::CACHE_OWNER_UID, $CONFIG::CACHE_OWNER_GID, $dest;
    print "Created cache file directory: $dest\n";
}

# Copy Flex LM files to system.
sub setup_flexlm {
    my ($source, $dest);
    my @source_path = (@CONFIG::REPO_PATH, "vagrant_provision", "flex_lm");
    my @dest_path   = @CONFIG::FLEXNETSERVER_PATH;

    $dest = catdir(@dest_path);
    mkdir $dest, 0701;
    print "Created directory: $dest\n";
    foreach (@CONFIG::FLEXLM_FILES) {
        $source = catfile(@source_path, $_);
        $dest = catfile(@dest_path, $_);

        # autodie doesn't work with File::Copy
        if (copy $source, $dest) {
            print "Copied Flex LM binary $_\n";
        } else {
            print STDERR "Flex LM binary $_: $!\n";
            exit 1;
        }

        chown $CONFIG::FLEXLM_OWNER_UID, $CONFIG::VAGRANT_GID, $dest;
        print "$_ ownership granted to $CONFIG::FLEXLM_OWNER:$CONFIG::VAGRANT_USER\n";

        chmod $CONFIG::FLEXLM_PERMISSIONS, $dest;
        printf "$_ permissions set to 0%o\n", $CONFIG::FLEXLM_PERMISSIONS;
    }
}

sub setup_mysql {
    my ($source, $dest, $file, $ip, @hosts);

    # IP address to bind MySQL to.
    # The VM is supposed to have only one IP address outside of 127.0.0.0/8 and
    # it should be within 10.0.2.0/24 via Virtualbox's NAT.  So, `hostname -I`
    # should return only one result.  Due to a small level of uncertainty, we'll
    # specifically use the first result found within 10.0.2.0/24.
    @hosts = split / /, `hostname -I`;
    chomp (my @filtered_hosts = grep /10\.0\.2\.\d{1,3}/, @hosts);
    $ip = $filtered_hosts[0];
    print STDERR "IP within 10.0.2.0/24 expected, not found.  MySQL may not be accessible.\nIP(s) found: @hosts\n" if (!defined $ip);

    # bind IP in cfg if IP was found.
    if (defined $ip) {
        $source = catfile(@CONFIG::DB_CONFIG_PATH, $CONFIG::DB_CONFIG_FILE);
        $dest = catfile(@CONFIG::DB_CONFIG_PATH, $CONFIG::DB_CONFIG_FILE . ".bak");

        # move original file to a backup
        rename($source, $dest);

        # Swap so we can read from backup ($source) and write adjustments to
        # original ($dest).
        $file = $source;
        $source = $dest;
        $dest = $file;

        open(my $source_fh, "<:encoding(UTF-8)", $source);
        open(my $dest_fh, ">:encoding(UTF-8)", $dest);
        while(<$source_fh>) {
            $_ =~ s/bind\-address\s+= 127\.0\.0\.1/bind\-address = $ip/g;
            print $dest_fh $_;
        }

        close($source_fh);
        close($dest_fh);
        print "MySQL configured to listen on localhost:53306.  Restarting MySQL...\n";
        exec_cmd("service mysql restart");
    } else {
        print STDERR "10.0.2.0/24 IP not found in VM.\nMySQL will not be listening on localhost:53306.\n";
    }
}

sub setup_database {
    # (1) Create database
    print "\n";
    print "Setting up mysql database.  Password security warning can be ignored.\n";
    exec_cmd("mysql -e \"CREATE DATABASE $CONFIG::DB_NAME;\"");

    # (2) Create database user account (various connection hosts)
    foreach (@CONFIG::DB_HOSTS) {
        exec_cmd("mysql -e \"CREATE USER '$CONFIG::DB_USER'\@'$_' IDENTIFIED BY '$CONFIG::DB_PASS';\"");
        exec_cmd("mysql -e \"GRANT ALL PRIVILEGES ON $CONFIG::DB_NAME.* TO '$CONFIG::DB_USER'\@'$_';\"");
    }

    exec_cmd("mysql -e \"FLUSH PRIVILEGES;\"");

    # (3) Setup database schema
    my $file = catfile(@CONFIG::REPO_PATH, "database", $CONFIG::SQL_FILE);
    exec_cmd("mysql --user=$CONFIG::DB_USER --password=$CONFIG::DB_PASS --database=$CONFIG::DB_NAME < $file");
}

# Setup logrotate for Apache error logs on the host.
sub setup_logrotate {
    print "Setup logrotate for apache logs viewable on host\n";
    my @source_path = (@CONFIG::REPO_PATH, "vagrant_provision", "logrotate");
    my @dest_path   = @CONFIG::LOGROTATE_PATH;
    my $source = catfile(@source_path, $CONFIG::LOGROTATE_CONF_FILE);
    my $dest   = catfile(@dest_path, $CONFIG::LOGROTATE_CONF_FILE);
    copy $source, $dest;
    print "\n";
}

# Setup apache and conf files.
sub setup_apache {
    my ($files, $conf, $source, $dest, @working_path, @source_path, @dest_path);

    # (1) Disable all currently active conf files
    print "Setting up Apache2\n";
    @working_path = (@CONFIG::APACHE_PATH, "sites-enabled");
    $files = catfile(@working_path, "*");
    foreach (glob($files)) {
        $conf = fileparse($_);
        $conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
        exec_cmd("a2dissite $conf");
    }

    # (2) Copy phpLicenseWatcher conf file
    @source_path = (@CONFIG::REPO_PATH, "vagrant_provision", "apache");
    @dest_path   = (@CONFIG::APACHE_PATH, "sites-available");
    $source = catfile(@source_path, $CONFIG::APACHE_CONF_FILE);
    $dest   = catfile(@dest_path, $CONFIG::APACHE_CONF_FILE);
    copy $source, $dest;

    # (3) Activate phpLicenseWatcher Apache conf file
    $conf = $CONFIG::APACHE_CONF_FILE;
    $conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
    exec_cmd("a2ensite $conf");

    # (4) Restart Apache
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


# Call script to copy code files to HTML directory.
sub copy_code {
    print "Copying repository code.\n";
    my @working_path = (@CONFIG::REPO_PATH, "vagrant_provision", "pl");
    my $file = catfile(@working_path, $CONFIG::UPDATE_CODE);
    exec_cmd("perl $file full");
}
