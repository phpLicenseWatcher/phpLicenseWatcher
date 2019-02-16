#!/usr/bin/env perl

# This script will provision a Vagrant Virtualbox VM for development.
use strict;
use warnings;
use autodie;
use File::Copy;
use File::Spec;

# Install required ubuntu packages
my @packages = ("apache2", "php", "php-db", "mysql-server", "mysql-client", "lsb");
system "apt-get update";
foreach (@packages) {
    system "apt-get -y install $_";
}

# Copy Flex LM files to system.
# TO DO: Some error handling if these files don't exist.
#        Maybe we don't necessarily have to halt provisioning on error.
my @files = ("adskflex", "lmgrd", "lmutil");
my $source_dir = "vagrant_setup/flex_lm";
my $dest_dir   = "/opt/flexnetserver";
mkdir $dest_dir, 0700;
foreach (@files) {
    copy File::Spec->catfile($source_dir, $_), File::Spec->catfile($dest_dir, $_);
}

# Setup mysql
# TO DO: Create SQL file complete schema and dummy data for development.
system "mysql < /home/vagrant/github_phplw/phplicensewatcher.sql";

# Remove extraneous files from /var/www/html
unlink "/var/www/html/*";

# Setup apache conf
copy "/home/vagrant/github_phplw/vagrant_setup/apache/phplw.conf", "/etc/apache2/sites_available"
system "a2dissite 000-default";
system "a2ensite phplw";
system "apachectl restart";

# Call script tp Rsync code files to /var/www/html
system "perl update_code.pl";

# Done!
exit 0;
