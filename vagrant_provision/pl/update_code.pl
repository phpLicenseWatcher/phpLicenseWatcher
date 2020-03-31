#!/usr/bin/env perl

# This script will update code files from repository to project directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Path qw(remove_tree);
use File::Spec::Functions qw(catfile rootdir);

# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Files and paths
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @CONFIG_PATH = ("vagrant_provision", "config");
my $CONFIG_FILE = "config.php";

# Users
my $VAGRANT_USERNAME = "vagrant";

# ** -------------------------- END CONFIGURATION --------------------------- **

# rsync provides easy recursive copy, but is not part of Perl core libraries.
sub exec_rsync {
    my ($source, $dest) = @_;

    if ((system "rsync -a $source $dest") != 0) {
        print STDERR "rsync exited ", $? >> 8, "\n";
        exit 1;
    }
}

# Update *only* composer dependencies
sub exec_composer {
    my $cmd = shift;
    my $dest = catfile(@HTML_PATH);

    if ((system "su -c \"composer -d$dest $cmd\" $VAGRANT_USERNAME") != 0) {
        print STDERR "composer exited ", $? >> 8, "\n";
        exit 1;
    }

    print "Composer: $cmd done.\n";
}

# Remove everything from HTML directory
sub remove_all {
    remove_tree(catfile(@HTML_PATH), {safe => 1, keep_root => 1});
    print "Cleared HTML directory.\n";
}

# Copy code to HTML directory
sub copy_code {
    my ($source, $files);
    my $dest = catfile(@HTML_PATH);

    foreach (qw(*.php *.html *.css *.json), catfile(@CONFIG_PATH, $CONFIG_FILE)) {
        $files = catfile(@REPO_PATH, $_);
        foreach $source (glob($files)) {
            exec_rsync($source, $dest);
        }
    }

    print "Installed/Updated development code.\n";
}

# CLI arg = full:  remove/reinstall all code and dependencies to HTML directory
# CLI arg = update-composer:  Run update only on composer dependencies
# No CLI arg:  (default) rsync latest dev code to HTML directory
if (defined $ARGV[0]) {
    if ($ARGV[0] eq "full") {
        remove_all();
        copy_code();
        exec_composer("install");
    } elsif ($ARGV[0] eq "update-composer") {
        exec_composer("update");
    }
} else {
    copy_code();
}

# All done!
exit 0;
