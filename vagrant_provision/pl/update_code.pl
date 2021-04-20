#!/usr/bin/env perl

# This script will update code files from repository to project directory.
use strict;
use warnings;
use autodie;
use File::Basename qw(dirname fileparse);
use File::Path qw(remove_tree);
use File::Spec::Functions qw(catdir catfile);

# permit us to use modules from same dir as this script.
use Cwd qw(abs_path);
use lib dirname(abs_path($0));
use config;

# main()
# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# CLI arg = full:  remove/reinstall all code and dependencies to HTML directory
# CLI arg = update-composer:  Run update only on composer dependencies
# No CLI arg:  (default) rsync latest development code to HTML directory
if (defined $ARGV[0]) {
    if ($ARGV[0] eq "full") {
        # composer("install"); # Composer is disabled.
        clear_html_folder();
        rsync_code("full");
    } elsif ($ARGV[0] eq "update-composer") {
        # composer("update"); # Composer is disabled.
    }
} else {
    rsync_code();
}

# All done!
exit 0;

# rsync provides easy recursive copy, but is not part of Perl core libraries.
sub exec_rsync {
    my ($source, $dest) = @_;

    if ((system "rsync -a $source $dest") != 0) {
        print STDERR "rsync exited ", $? >> 8, "\n";
        exit 1;
    }
}

# Run composer to either install or update packages.
# Composer is disabled.
# sub composer {
#     my $cmd = shift;
#     my $dest = catdir(@CONFIG::REPO_PATH);
#
#     if ((system "su -c \"composer -d$dest $cmd\" $CONFIG::VAGRANT_USER") != 0) {
#         print STDERR "composer exited ", $? >> 8, "\n";
#         exit 1;
#     }
#
#     print "Composer: $cmd done.\n";
# }

# Remove everything from HTML directory
sub clear_html_folder {
    remove_tree(catdir(@CONFIG::HTML_PATH), {safe => 1, keep_root => 1});
    print "Cleared HTML directory.\n";
}

# Copy code to HTML directory
sub rsync_code {
    my ($source, $files);
    my $dest = catdir(@CONFIG::HTML_PATH);
    my @file_list = qw(*.php *.html *.css *.js);

    # Normally, we just want to rsync development code, but a full provision
    # also requires config file and composer packages.
    my $option = shift if (@_);
    if (defined $option && $option eq "full") {
        # Composer is disabled.
        # push(@file_list, catfile(@CONFIG::CONFIG_PATH, $CONFIG::CONFIG_FILE), $CONFIG::COMPOSER_PACKAGES);
        push(@file_list, catfile(@CONFIG::CONFIG_PATH, $CONFIG::CONFIG_FILE));
    }

    foreach (@file_list) {
        $files = catfile(@CONFIG::REPO_PATH, $_);
        foreach $source (glob($files)) {
            exec_rsync($source, $dest);
        }
    }

    print "Installed/Updated development code.\n";
}
