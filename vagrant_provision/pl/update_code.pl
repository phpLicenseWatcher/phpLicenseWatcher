#!/usr/bin/env perl

# This script will update code files from repository to project directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Path qw(remove_tree);
use File::Spec::Functions qw(catfile rootdir);

# rsync provides easy recursive copy, but is not part of Perl core libraries.
sub exec_rsync {
    my ($source, $dest) = @_;
    if ((system "rsync -qa $source $dest") != 0) {
        print "rsync $source: $!\n";
        exit 1;
    }
}

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
my ($source, $dest, $files, @source_path, @dest_path);

# ** -------------------------- END CONFIGURATION --------------------------- **

# Remove everything from HTML directory
remove_tree(catfile(@HTML_PATH), {safe => 1, keep_root => 1});

# Copy code to HTML directory
foreach (qw(*.php *.html *.css vendor)) {
    $files = catfile(@REPO_PATH, $_);
    foreach $source (glob($files)) {
        $dest = catfile(@HTML_PATH);
        exec_rsync($source, $dest);
    }
}

#Copy dev config file
$source = catfile(@CONFIG_PATH, $CONFIG_FILE);
$dest = catfile(@HTML_PATH);
exec_rsync($source, $dest);

# All done!
exit 0;
