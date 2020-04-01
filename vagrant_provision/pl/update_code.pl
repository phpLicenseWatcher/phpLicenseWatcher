#!/usr/bin/env perl

# This script will update code files from repository to project directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(fileparse);
use File::Path qw(remove_tree);
use File::Spec::Functions qw(catdir catfile rootdir);

# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# ** ---------------------------- CONFIGURATION ----------------------------- **
# TO DO: maybe create common config file for provision.pl and update_code.pl

# Files and paths
my @REPO_PATH = (rootdir(), "home", "vagrant", "github_phplw");
my @HTML_PATH = (rootdir(), "var", "www", "html");
my @CONFIG_PATH = ("vagrant_provision", "config");
my $COMPOSER_PACKAGES = "vendor";
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

# Run composer to either install or update packages.
sub composer {
    my $cmd = shift;
    my $dest = catdir(@REPO_PATH);

    if ((system "su -c \"composer -d$dest $cmd\" $VAGRANT_USERNAME") != 0) {
        print STDERR "composer exited ", $? >> 8, "\n";
        exit 1;
    }

    print "Composer: $cmd done.\n";
}

# Remove everything from HTML directory
sub clear_html_folder {
    remove_tree(catdir(@HTML_PATH), {safe => 1, keep_root => 1});
    print "Cleared HTML directory.\n";
}

# Copy code to HTML directory
sub rsync_code {
    my ($source, $files);
    my $dest = catdir(@HTML_PATH);
    my @file_list = qw(*.php *.html *.css);

    # Normally, we just want to rsync development code, but a full provision
    # also requires config file and composer packages.
    my $option = shift if (@_);
    if (defined $option && $option eq "full") {
        push(@file_list, catfile(@CONFIG_PATH, $CONFIG_FILE), $COMPOSER_PACKAGES);
    }

    foreach (@file_list) {
        $files = catfile(@REPO_PATH, $_);
        foreach $source (glob($files)) {
            exec_rsync($source, $dest);
        }
    }

    print "Installed/Updated development code.\n";
}

# CLI arg = full:  remove/reinstall all code and dependencies to HTML directory
# CLI arg = update-composer:  Run update only on composer dependencies
# No CLI arg:  (default) rsync latest development code to HTML directory
if (defined $ARGV[0]) {
    if ($ARGV[0] eq "full") {
        composer("install");
        clear_html_folder();
        rsync_code("full");
    } elsif ($ARGV[0] eq "update-composer") {
        composer("update");
    }
} else {
    rsync_code();
}

# All done!
exit 0;
