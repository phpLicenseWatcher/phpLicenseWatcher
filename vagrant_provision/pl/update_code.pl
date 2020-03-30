#!/usr/bin/env perl

# This script will update code files from repository to project directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Copy qw(copy);
use File::Path qw(remove_tree);
use File::Spec::Functions qw(catfile rootdir);

# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Paths (as arrays of directories)
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @CONFIG_PATH = (@REPO_PATH, "vagrant_provision", "config");

my $VAGRANT_NAME = "vagrant";

# HTML file ownership UID and GID
my $HTML_UID = getpwnam("www-data");
my $HTML_GID = getgrnam("www-data");
my $HTML_PERMISSIONS = 0664;

# Files
my $CONFIG_FILE = "config.php";

# Vars and arrays
my ($source, $dest, $files, @source_path, @dest_path);

# ** -------------------------- END CONFIGURATION --------------------------- **

# Help provide some error messaging if there is a copy error.
sub copy_file {
    my ($source, $dest) = @_;
    print STDERR "copy $source: $!" and exit 1 if !(copy $source, $dest);
    chown $HTML_UID, $HTML_GID, $dest;
    chmod $HTML_PERMISSIONS, $dest;
}

if (defined $ARGV[0] && $ARGV[0] eq "composer") {
    $dest = catfile(@HTML_PATH);
    print STDERR "composer exited ", $? >> 8, "\n" and exit 1 if !(system "su -c \"composer -d$dest update\" $VAGRANT_NAME");

    # CLI arg "composer" only updates composer code in working directory.
    exit 0;
}

# Remove everything from HTML directory
remove_tree(catfile(@HTML_PATH), {safe => 1, keep_root => 1});

# Copy code to HTML directory
foreach (qw(*.php *.html *.css *.json)) {
    $files = catfile(@REPO_PATH, $_);
    foreach $source (glob($files)) {
        $files = fileparse($source);
        $dest = catfile(@HTML_PATH, $files);
        copy_file($source, $dest);
    }
}

# Copy dev config file
$source = catfile(@CONFIG_PATH, $CONFIG_FILE);
$dest = catfile(@HTML_PATH);
copy_file($source, $dest);

# All done!
exit 0;
