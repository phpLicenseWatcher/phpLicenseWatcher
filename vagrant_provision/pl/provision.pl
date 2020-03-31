#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catdir catfile rootdir);

# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Paths (as arrays of directories)
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @FLEXNETSERVER_PATH = (rootdir(), "opt", "flexnetserver");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @APACHE_PATH = (rootdir(), "etc", "apache2");

# Packages needed by OS
my @REQUIRED_PACKAGES = ("apache2", "php", "php-gd", "php-db", "php-mysql", "mysql-server", "mysql-client", "lsb", "composer");

# Non super user account.  Some package systems run better when not as root.
my $NOT_SUPERUSER = "vagrant";
my $NOT_SUPERUSER_UID = getpwnam $NOT_SUPERUSER;
my $NOT_SUPERUSER_GID = getgrnam $NOT_SUPERUSER;

# List of Flex LM binaries
my @FLEXLM_FILES = ("adskflex", "lmgrd", "lmutil");
my $FLEXLM_OWNER = "www-data";
my $FLEXLM_OWNER_UID = getpwnam $FLEXLM_OWNER;
my $FLEXLM_OWNER_GID = getgrnam $FLEXLM_OWNER;
my $FLEXLM_PERMISSIONS = 0700;

# DB config
my $DB_NAME = "phplw_dev";
my $DB_HOST = "localhost";
my $DB_USER = "phplw_dev_dbuser";
my $DB_PASS = "phplw_dev_dbpassword";

# Other relevant files
my $SQL_FILE = "phplicensewatcher.sql";
my $CONF_FILE = "phplw.conf";
my $UPDATE_CODE = "update_code.pl";

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
# Create database
print "\n";
print "Setting up mysql database.  Password security warning can be ignored.\n";
exec_cmd("mysql -e \"CREATE DATABASE $DB_NAME;\"");

# Create database user (no password)
exec_cmd("mysql -e \"CREATE USER '$DB_USER'\@'$DB_HOST' IDENTIFIED BY '$DB_PASS';\"");
exec_cmd("mysql -e \"GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'\@'$DB_HOST';\"");

# Setup database schema
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
