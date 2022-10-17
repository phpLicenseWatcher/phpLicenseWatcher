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
use Data::Dumper;

# main()
# Root required
print "Root required.\n" and exit 1 if ($> != 0);

# CLI arg = full:  remove/reinstall all code and dependencies to HTML directory
# CLI arg = update-composer:  Run update only on composer dependencies
# No CLI arg:  (default) copy latest development code to HTML directory
my ($source, $dest, $file, $composer, @files);
if (defined $ARGV[0] && $ARGV[0] eq "full") {
    clear_html_folder();
    $source = catdir(@CONFIG::REPO_PATH);
    $dest   = catdir(@CONFIG::HTML_PATH);
    $composer = $CONFIG::COMPOSER_PACKAGES;
    @files = build_file_list($source);
    push @files, full_recursive_file_list($composer);
    print "Install development code.\n";
    copy_code($source, $dest, @files);

    # Config file copy
    $source = catdir(@CONFIG::CONFIG_PATH);
    $dest   = catdir(@CONFIG::HTML_PATH);
    $file   = $CONFIG::CONFIG_FILE;
    print "Install vagrant config file.\n";
    copy_code($source, $dest, $file);
} elsif (defined $ARGV[0] && $ARGV[0] eq "composer-install-packages") {
    composer("install");
} elsif (defined $ARGV[0] && $ARGV[0] eq "composer-update-packages") {
    composer("update");
} else {
    $source = catdir(@CONFIG::REPO_PATH);
    $dest   = catdir(@CONFIG::HTML_PATH);
    $composer = $CONFIG::COMPOSER_PACKAGES;
    @files  = build_file_list($source);
    push @files, full_recursive_file_list($composer);
    print "Update development code.\n";
    copy_code($source, $dest, @files);
}

# All done!
exit 0;

# Run composer to either install or update packages.
sub composer {
    my $cmd = shift;
    my $dest = catdir(@CONFIG::REPO_PATH);

    if ((system "su -c \"composer -d$dest $cmd\" $CONFIG::VAGRANT_USER") != 0) {
        print STDERR "composer exited ", $? >> 8, "\n";
        exit 1;
    }

    print "Composer: $cmd done.\n";
}

# Remove everything from HTML directory
sub clear_html_folder {
    my $html_path = catdir(@CONFIG::HTML_PATH);
    remove_tree($html_path, {safe => 1, keep_root => 1});
    print "Cleared HTML directory.\n";
}

# Expected param: $root_path (path to start gathering filenames for code copying)
# Optional param: $sub_path  Additional dirs off of $root_path.  Default: $sub_path = ""
sub build_file_list {
    my $root_path = shift;
    my ($sub_path, $search_path, $path, $file, @file_list);

    # Get $sub_path, if param exists.
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

# Recursive copy of all files in a directory tree. Intended for Composer files in "vendor".
# Expected param: start dir for copying files (such as "vendor").
sub full_recursive_file_list {
    my $path = shift;
    my $glob_pattern = catdir(@CONFIG::REPO_PATH, $path, "*");
    my ($file, @files);
    foreach (glob $glob_pattern) {
        next if (!-e $_); # Occasionally glob matches to a non-existent file.  Skip those.
        $file = fileparse($_);
        $file = catfile($path, $file);
        if (-d $_) {
            push @files, full_recursive_file_list($file);
        } else {
            push @files, $file;
        }
    }

    return @files;
}

# Copy all code files (other than config files)
# Expected param: string source path, string dest path, array @file_list of files to copy.
sub copy_code {
    my $source_path = shift;
    my $dest_path   = shift;
    my @file_list   = @_;
    my $uid = $CONFIG::WWW_UID;
    my $gid = $CONFIG::WWW_GID;
    my $dir_perms = $CONFIG::WWW_DIR_PERMISSIONS;
    my $file_perms = $CONFIG::WWW_FILE_PERMISSIONS;
    my ($source, $dest);

    foreach (@file_list) {
        $source = catfile($source_path, $_);
        $dest = catfile($dest_path, $_);
        # Make sure dir exists before copy
        make_path($1, {mode => $dir_perms, owner => $uid, group => $gid}) if ($dest =~ /^(.+\/)/);
        unlink $dest if (-e $dest);
        copy_file ($source, $dest);
        chown $uid, $gid, $dest;
        chmod $file_perms, $dest;
    }

    print "Files copy/update done.\n";
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
