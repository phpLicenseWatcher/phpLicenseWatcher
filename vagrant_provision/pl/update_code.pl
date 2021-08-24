#!/usr/bin/env perl

# This script will update code files from repository to project directory.
use strict;
use warnings;
use autodie;
use File::Copy qw(copy);
use File::Basename qw(fileparse);
use File::Path qw(remove_tree make_path);
use File::Spec::Functions qw(catdir catfile);
use FindBin qw($RealBin);
use lib $RealBin;
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
    my $source = catdir(@CONFIG::REPO_PATH);
    my $dest   = catdir(@CONFIG::HTML_PATH);
    my @files  = build_file_list($source);
    copy_code($source, $dest, @files);
    print "Copied code files.\n";
}

# All done!
exit 0;

# # rsync provides easy recursive copy, but is not part of Perl core libraries.
# sub exec_rsync {
#     my ($source, $dest) = @_;
#
#     if ((system "rsync -a $source $dest") != 0) {
#         print STDERR "rsync exited ", $? >> 8, "\n";
#         exit 1;
#     }
# }

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
    my $html_path = catdir(@CONFIG::HTML_PATH);
    remove_tree($html_path, {safe => 1, keep_root => 1});
    print "Cleared HTML directory.\n";
}

# Optional params: $sub_path  Default: $sub_path = ""
sub build_file_list {
    my $root_path = shift;
    my ($sub_path, $search_path, $path, $file, @file_list);

    # Get 2nd parameter, if 2nd argument was passed in.
    $sub_path = scalar @_ > 0 ? shift : "";

    foreach (@CONFIG::CODE_FILES) {
        $path = catfile($root_path, $sub_path, $_);
        foreach (glob $path) {
            next if (!-e $_); # Occasionally glob matches to a non-existent file.  Skip those.
            $file = fileparse($_);
            if (-d $_) {
                # Recurse into directory and push results onto @file_list
                $search_path = catdir($sub_path, $file);
                push @file_list, build_file_list($root_path, $search_path);
                next;
            }

            $file = catfile($sub_path, $file) if ($sub_path ne "");
            push @file_list, $file;
        }
    }

    return @file_list;
}

# Copy all code files (other than config files)
# Expected param: string source path, string dest path, array @file_list of files to copy.
sub copy_code {
    my $source_path = shift;
    my $dest_path   = shift;
    my @file_list   = @_;
    my ($source, $dest);

    foreach (@file_list) {
        $source = catfile($source_path, $_);
        $dest = catfile($dest_path, $_);

        # Make sure dir exists before copy
        make_path($1) if ($dest =~ /^(.+\/)/);

        unlink $dest if (-e $dest);
        copy_file ($source, $dest);
        chmod 0644, $dest;
    }
}

# Copy a single file.
# Expected arguments: source path/file, destination path/file
sub copy_file {
    my ($source, $dest) = @_;
    if (!copy($source, $dest)) {
        # No autodie on File::Copy
        print STDERR "Can't copy file: $!\n";
        print STDERR "Source: ${source}\n";
        print STDERR "Dest:   ${dest}\n";
        exit 1;
    }
}

# # Copy code to HTML directory
# sub rsync_code {
#     my @repo_path   = @CONFIG::REPO_PATH;
#     my $config_file = catfile(@CONFIG::CONFIG_PATH, $CONFIG::CONFIG_FILE);
#     my $dest        = catdir(@CONFIG::HTML_PATH);
#     my @file_list   = qw(*.php *.html *.css *.js);
#     my ($source, $files);
#
#     # Normally, we just want to rsync development code, but a full provision
#     # also requires config file and composer packages.
#     my $option = shift if (@_);
#     if (defined $option && $option eq "full") {
#         # Composer is disabled.
#         # push(@file_list, catfile(@CONFIG::CONFIG_PATH, $CONFIG::CONFIG_FILE), $CONFIG::COMPOSER_PACKAGES);
#         push(@file_list, $config_file);
#     }
#
#     foreach (@file_list) {
#         $files = catfile(@repo_path, $_);
#         foreach $source (glob($files)) {
#             exec_rsync($source, $dest);
#         }
#     }
#
#     print "Installed/Updated development code.\n";
# }
