#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
use strict;
use warnings;
use autodie;
use File::Copy;
use File::Spec;

my @REPO_PATH = ("/", "home", "vagrant", "github_phplw");
my @REQUIRED_PACKAGES = ("apache2", "php", "php-db", "mysql-server", "mysql-client", "lsb");
my @FLEXLM_FILES = ("adskflex", "lmgrd", "lmutil");
my $SQL_FILE = "phplicencewacther.sql";
my $CONF_FILE = "phplw.conf";
my $CODE_UPDATE = "update_code.pl";

# Install required ubuntu packages
system "export DEBIAN_FRONTEND=noninteractive";
system "apt-get -q update";
foreach (@REQUIRED_PACKAGES) {
    system "apt-get -qy install $_";
    print "Aptitude Installed Package: $_";
}

# Copy Flex LM files to system.
# TO DO: Some error handling if these files don't exist.
#        Maybe we don't necessarily have to halt provisioning on error.
my ($source, $dest);
my @source_path = (@REPO_PATH, "vagrant_setup", "flex_lm");
my @dest_path   = ("/", "opt", "flexnetserver");

$dest = File::Spec->catdir(@dest_path);
mkdir $dest, 0700;
print "Created directory: $dest";

foreach (@FLEXLM_FILES) {
    $source = File::Spec->catfile(@source_path, $_);
    $dest   = File::Spec->catfile(@dest_path, $_);
    copy $source, $dest;
    print "Copied Flex LM binary $_";
}

# Setup mysql
# TO DO: Create SQL file with complete schema and dummy data for development.
$source = File::Spec->catfile(@REPO_PATH, $SQL_FILE);
system "mysql < $source";
print "Setup mysql with $SQL_FILE";

# Remove extraneous files from /var/www/html
@source_path = ("/", "var", "www", "html");
$source = File::Spec->catfile(@source_path, "*");
foreach (glob($source)) {
    unlink $_;
}

# Setup apache conf
@source_path = (@REPO_PATH, "vagrant_setup", "apache");
@dest_path   = ("etc", "apache2", "sites_available");
$source = File::Spec->catfile(@source_path, $CONF_FILE);
$dest   = File::Spec->catfile(@dest_path, $CONF_FILE);
copy $source, $dest;

my $conf = $CONF_FILE;
$conf =~ s{\.[^.]+$}{};
system "a2dissite 000-default";
system "a2ensite $conf";
system "apachectl restart";

print "Setup/Configured Apache2 with $CONF_FILE";

# Call script to Rsync code files to /var/www/html
@source_path = (@REPO_PATH, "vagrant_setup");
$source = File::Spec->catfile(@source_path, $CODE_UPDATE);
system "perl $source";
print "Repository code installed".

# Done!
exit 0;
