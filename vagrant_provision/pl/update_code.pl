#!/usr/bin/env perl

# This script will update code files from repository to working directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Spec::Functions qw(catfile rootdir);

# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Paths (as arrays of directories)
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @CONFIG_PATH = (@REPO_PATH, "vagrant_provision", "config");

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
        $work = fileparse($source);
        $dest = catfile(@HTML_PATH, $work);
        copy $source, $dest;
        chown 1000, 33, $dest;
        chmod 0551, $dest;
    }
}

#Copy dev config file
$source = catfile(@CONFIG_PATH, $CONFIG_FILE);
$dest = catfile(@HTML_PATH, $CONFIG_FILE);
copy $source, $dest;
chown 1000, 33, $dest;
chmod 0551, $dest;

# All done!
exit 0;
