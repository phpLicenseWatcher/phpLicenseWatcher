#!/usr/bin/env perl

# This script will update code files from repository to working directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catfile rootdir);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Paths (as arrays of directories)
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @CONFIG_PATH = (@REPO_PATH, "vagrant_setup", "config");

# Files
my $CONFIG_FILE = "config.php";

# Vars and arrays
my ($source, $dest, $work, @source_path, @dest_path);

# ** -------------------------- END CONFIGURATION --------------------------- **

# Remove extraneous files from /var/www/html
$work = catfile(@HTML_PATH, "*");
foreach (glob($work)) {
    unlink $_ if (-f $_);
}

# Copy code to HTML directory
foreach(qw(*.php *.html *.css)) {
    $work = catfile(@REPO_PATH, $_);
    foreach (glob($work)) {
        $source = $_;
        $dest = catfile(@HTML_PATH, fileparse($source));
        copy $source, $dest;
    }
}

#Copy dev config file
$source = catfile(@CONFIG_PATH, $CONFIG_FILE);
$dest = catfile(@HTML_PATH, $CONFIG_FILE);
copy $source, $dest;

# All done!
exit 0;
