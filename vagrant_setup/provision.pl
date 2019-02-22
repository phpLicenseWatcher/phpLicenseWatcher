#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
use strict;
use warnings;
use autodie;
use File::Copy;
use File::Spec;

# Config
my @REPO_PATH = ("/", "home", "vagrant", "github_phplw");
my @HTML_PATH = ("/", "var", "www", "html");
my @APACHE_PATH = ("/", "etc", "apache2");
my @REQUIRED_PACKAGES = ("apache2", "php", "php-db", "mysql-server", "mysql-client", "lsb");
my @FLEXLM_FILES = ("adskflex", "lmgrd", "lmutil");
my $DB_NAME = "phplw_dev";
my $DB_HOST = "localhost";
my $DB_USER = "phplw_dev_dbuser";
my $SQL_FILE = "phplicensewatcher.sql";
my $CONF_FILE = "phplw.conf";
my $UPDATE_CODE = "update_code.pl";

# Vars and arrays
my ($source, $dest, $work, $conf, @source_path, @dest_path, @working_path);

# Install required ubuntu packages
system "apt-get update > /dev/null 2>&1";
foreach (@REQUIRED_PACKAGES) {
    system "apt-get -y install $_ > /dev/null 2>&1";
    print "Aptitude Installed Package: $_\n";
}

# Copy Flex LM files to system.
# TO DO: Some error handling if these files don't exist.
#        Maybe we don't necessarily have to halt provisioning on error.
@source_path = (@REPO_PATH, "vagrant_setup", "flex_lm");
@dest_path   = ("/", "opt", "flexnetserver");

$dest = File::Spec->catdir(@dest_path);
mkdir $dest, 0700;
print "Created directory: $dest\n";

foreach (@FLEXLM_FILES) {
    $source = File::Spec->catfile(@source_path, $_);
    $dest   = File::Spec->catfile(@dest_path, $_);
    if (-f $source) {
        copy $source, $dest;
        print "Copied Flex LM binary $_\n";
    } else {
        print "Flex LM binary $_ NOT FOUND\n";
    }
}

# Setup mysql
# Create database
system "mysql -e \"CREATE DATABASE $DB_NAME;\"";

# Create database user (no password)
system "mysql -e \"GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'$DB_HOST';\"";

# Setup Schema
$source = File::Spec->catfile(@REPO_PATH, $SQL_FILE);
system "mysql -u $DB_USER -D $DB_NAME < $source";
print "Setup mysql database phplw-dev with $SQL_FILE\n";

# Remove extraneous files from /var/www/html
$work = File::Spec->catfile(@HTML_PATH, "*");
foreach (glob($work)) {
    unlink $_;
}

# Setup apache conf
# First disable all currently active conf files
@working_path = (@APACHE_PATH, "sites-enabled");
$work = File::Spec->catfile(@working_path, "*");
foreach (glob($work)) {
    $conf = $_;
    $conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
    system "a2dissite $conf";
}

# Copy phpLicenseWatcher conf file
@source_path = (@REPO_PATH, "vagrant_setup", "apache");
@dest_path   = (@APACHE_PATH, "sites-available");
$source = File::Spec->catfile(@source_path, $CONF_FILE);
$dest   = File::Spec->catfile(@dest_path, $CONF_FILE);
copy $source, $dest;

# Activate phpLicenseWatcher conf file
$conf = $CONF_FILE;
$conf =~ s{\.[^.]+$}{};  # Removes ".conf" extension
system "a2ensite $conf";
system "apachectl restart";
print "Setup/Configured Apache2 with $CONF_FILE\n";

# Call script to Rsync code files to /var/www/html
@working_path = (@REPO_PATH, "vagrant_setup");
$work = File::Spec->catfile(@working_path, $UPDATE_CODE);
system "perl $work";
print "Repository code installed.\n".

# Done!
exit 0;
