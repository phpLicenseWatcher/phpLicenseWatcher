#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catdir catfile rootdir);

# Help with logging executed commands and their results.
sub exec_cmd {
    my $cmd = shift;
    print "\$ $cmd\n";
    system $cmd;
    print "\n";
}

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
my @REQUIRED_PACKAGES = ("apache2", "php", "php-db", "php-mysql", "mysql-server", "mysql-client", "lsb");

# List of Flex LM binaries
my @FLEXLM_FILES = ("adskflex", "lmgrd", "lmutil");

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
my ($cmd, $source, $dest, $file, $files, $conf, @source_path, @dest_path, @working_path);

# ** -------------------------- END CONFIGURATION --------------------------- **

# Run Ubuntu updates and install required Ubuntu packages
exec_cmd("apt-get -q update");

# This prevents grub-pc from calling up a user interactive menu that will halt provisioning.
exec_cmd("DEBIAN_FRONTEND=noninteractive apt-get -qy -o DPkg::options::='--force-confdef' -o DPkg::options::='--force-confold' dist-upgrade");

foreach (@REQUIRED_PACKAGES) {
    exec_cmd("apt-get -qy install $_");
}

# Copy Flex LM files to system.
# TO DO: Some error handling if these files don't exist.
#        Maybe we don't necessarily have to halt provisioning on error.
@source_path = (@REPO_PATH, "vagrant_provision", "flex_lm");
@dest_path   = @FLEXNETSERVER_PATH;

$dest = catdir(@dest_path);
if (!-e $dest) {
    mkdir $dest, 0700;
    print "Created directory: $dest\n";
}

foreach (@FLEXLM_FILES) {
    $source = catfile(@source_path, $_);
    $dest   = catfile(@dest_path, $_);
    if (-f $source) {
        copy $source, $dest;
        print "Copied Flex LM binary $_\n";
    } else {
        print "Flex LM binary $_ NOT FOUND, NOT COPIED.\n";
    }
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

# Call script to copy code files to /var/www/html
print "Copying repository code.\n";
@working_path = (@REPO_PATH, "vagrant_provision", "pl");
$file = catfile(@working_path, $UPDATE_CODE);
exec_cmd("perl $file");

# Done!
print "All done!\n";
exit 0;
