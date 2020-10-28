#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
# Note that we are restricted to core perl as this script is run *before* we can
# get any additional perl libraries.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catdir catfile rootdir);

# Root required
print STDERR "Root required.\n" and exit 1 if ($> != 0);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Paths (as arrays of directories)
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @FLEXNETSERVER_PATH = (rootdir(), "opt", "flexnetserver");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @APACHE_PATH = (rootdir(), "etc", "apache2");
my @CACHE_PATH = (rootdir(), "var", "cache", "phplw");

# Packages needed by OS
my @REQUIRED_PACKAGES = ("apache2", "php", "php-xml", "php-gd", "php-mysql", "mysql-server", "mysql-client", "lsb", "composer");

# Non super user account.  Some package systems run better when not as root.
my $NOT_SUPERUSER = "vagrant";
my $NOT_SUPERUSER_UID = getpwnam $NOT_SUPERUSER;
my $NOT_SUPERUSER_GID = getgrnam $NOT_SUPERUSER;

# Cache files owner
my $CACHE_OWNER = "www-data";
my $CACHE_OWNER_UID = getpwnam $CACHE_OWNER;
my $CACHE_OWNER_GID = getgrnam $CACHE_OWNER;
my $CACHE_PERMISSIONS = 0700;

# List of Flex LM binaries and ownership
my @FLEXLM_FILES = ("adskflex", "lmgrd", "lmutil");
my $FLEXLM_OWNER = "www-data";
my $FLEXLM_OWNER_UID = getpwnam $FLEXLM_OWNER;
my $FLEXLM_OWNER_GID = getgrnam $FLEXLM_OWNER;
my $FLEXLM_PERMISSIONS = 0700;

# DB config
my @DB_HOSTS = ("localhost", "_gateway");
my @DB_CONFIG_PATH = (rootdir(), "etc", "mysql", "mysql.conf.d");
my $DB_CONFIG_FILE = "mysqld.cnf";
my $DB_NAME = "vagrant";
my $DB_USER = "vagrant";
my $DB_PASS = "vagrant";

# Other relevant files
my $SQL_FILE = "phplicensewatcher.sql";
my $CONF_FILE = "phplw.conf";
my $UPDATE_CODE = "update_code.pl";

# IP address to bind MySQL to.
# The VM is supposed to have only one IP address outside of 127.0.0.0/8 and it
# should be within 10.0.2.0/24 via Virtualbox's NAT.  So, `hostname -I` should
# return only one result.  Due to a small level of uncertainty, we'll
# specifically use the first result found within 10.0.2.0/24.
my @hosts = split / /, `hostname -I`;
chomp (my @filtered_hosts = grep(/10\.0\.2\.\d{1,3}/, @hosts));
my $IP = $filtered_hosts[0];
print STDERR "IP within 10.0.2.0/24 expected, not found.  MySQL may not be accessible.\nIP(s) found: @hosts\n" if (!defined $IP);

# Vars and arrays
my ($source, $dest, $file, $files, $conf, @source_path, @dest_path, @working_path);

# ** -------------------------- END CONFIGURATION --------------------------- **

# Help with logging executed commands and their results.
sub exec_cmd {
    my $cmd = shift;
    print "\$ $cmd\n";
    print STDERR "$cmd exited ", $? >> 8, "\n" and exit 1 if ((system $cmd) != 0);
    print "\n";
}

# Run Ubuntu updates and install required Ubuntu packages
exec_cmd("apt-get -q update");

# This prevents grub-pc from calling up a user interactive menu that will halt provisioning.
exec_cmd("DEBIAN_FRONTEND=noninteractive apt-get -qy -o DPkg::options::='--force-confdef' -o DPkg::options::='--force-confold' dist-upgrade");

foreach (@REQUIRED_PACKAGES) {
    exec_cmd("apt-get -qy install $_");
}

# Run composer to retrieve PHP dependencies
# Composer cannot be run as superuser.
exec_cmd("su -c \"composer -d" . catfile(@REPO_PATH) . " install\" $NOT_SUPERUSER");

# Prepare cache directory
$dest = catdir(@CACHE_PATH);
mkdir $dest, 0701;
chown $CACHE_OWNER_UID, $CACHE_OWNER_GID, $dest;
print "Created cache file directory: $dest\n";

# Copy Flex LM files to system.
@source_path = (@REPO_PATH, "vagrant_provision", "flex_lm");
@dest_path   = @FLEXNETSERVER_PATH;

$dest = catdir(@dest_path);
mkdir $dest, 0701;
print "Created directory: $dest\n";
foreach (@FLEXLM_FILES) {
    $source = catfile(@source_path, $_);
    $dest   = catfile(@dest_path, $_);

    # autodie doesn't work with File::Copy
    if (copy $source, $dest) {
        print "Copied Flex LM binary $_\n";
    } else {
        print STDERR "Flex LM binary $_: $!\n";
        exit 1;
    }

    chown $FLEXLM_OWNER_UID, $FLEXLM_OWNER_GID, $dest;
    print "$_ ownership granted to $FLEXLM_OWNER\n";

    chmod $FLEXLM_PERMISSIONS, $dest;
    print "$_ permissions set to ", sprintf("0%o\n", $FLEXLM_PERMISSIONS);
}

# Setup mysql
# (1) bind IP in cfg if IP was found.
if (defined $IP) {
    $source = catfile(@DB_CONFIG_PATH, $DB_CONFIG_FILE);
    $dest = catfile(@DB_CONFIG_PATH, $DB_CONFIG_FILE . ".bak");

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
        $_ =~ s/bind\-address\s+= 127\.0\.0\.1/bind\-address = $IP/g;
        print $dest_fh $_;
    }

    close($source_fh);
    close($dest_fh);
    print "MySQL configured to listen on localhost:53306.  Restarting MySQL...\n";
    exec_cmd("service mysql restart");
} else {
    print STDERR "10.0.2.0/24 IP not found in VM.\nMySQL will not be listening on localhost:53306.\n";
}

# (2) Create database
print "\n";
print "Setting up mysql database.  Password security warning can be ignored.\n";
exec_cmd("mysql -e \"CREATE DATABASE $DB_NAME;\"");

# (3) Create database user account (various connection hosts)
foreach (@DB_HOSTS) {
    exec_cmd("mysql -e \"CREATE USER '$DB_USER'\@'$_' IDENTIFIED BY '$DB_PASS';\"");
    exec_cmd("mysql -e \"GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'\@'$_';\"");
}

exec_cmd("mysql -e \"FLUSH PRIVILEGES;\"");

# (4) Setup database schema
$file = catfile(@REPO_PATH, $SQL_FILE);
exec_cmd("mysql --user=$DB_USER --password=$DB_PASS --database=$DB_NAME < $file");

# Setup apache conf
# First disable all currently active conf files
print "Setting up Apache2\n";
@working_path = (@APACHE_PATH, "sites-enabled");
$files = catfile(@working_path, "*");
foreach (glob($files)) {
    $conf = fileparse($_);
    $conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
    exec_cmd("a2dissite $conf");
}

# Copy phpLicenseWatcher conf file
@source_path = (@REPO_PATH, "vagrant_provision", "apache");
@dest_path   = (@APACHE_PATH, "sites-available");
$source = catfile(@source_path, $CONF_FILE);
$dest   = catfile(@dest_path, $CONF_FILE);
copy $source, $dest;

# Activate phpLicenseWatcher conf file
$conf = $CONF_FILE;
$conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
exec_cmd("a2ensite $conf");
exec_cmd("apachectl restart");

# Call script to copy code files to HTML directory
print "Copying repository code.\n";
@working_path = (@REPO_PATH, "vagrant_provision", "pl");
$file = catfile(@working_path, $UPDATE_CODE);
exec_cmd("perl $file full");

# Done!
print "All done!\n";
exit 0;
