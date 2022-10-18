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
if (defined $ARGV[0] && $ARGV[0] eq "full") {
    clear_html_folder();
    install_code();

    # Config file copy
    my $source = catdir(@CONFIG::CONFIG_PATH);
    my $dest   = catdir(@CONFIG::HTML_PATH);
    my $file   = $CONFIG::CONFIG_FILE;
    print "Install vagrant config file.\n";
    copy_code($source, $dest, $file);
} elsif (defined $ARGV[0] && $ARGV[0] eq "composer-install-packages") {
    composer("install");
} elsif (defined $ARGV[0] && $ARGV[0] eq "composer-update-packages") {
    composer("update");
} else {
    install_code();
}

# All done!
exit 0;

# Run composer to either install or update packages.
# Expected param: either "install" or "update".
sub composer {
    my $cmd = shift;
    my $dest = catdir(@CONFIG::REPO_PATH);

    if ((system "su -c \"composer -d$dest $cmd\" $CONFIG::VAGRANT_USER") != 0) {
        print STDERR "composer exited ", $? >> 8, "\n";
        exit 1;
    }

    print "Composer: ${cmd} done.\n";
}

# Remove everything from HTML directory
sub clear_html_folder {
    my $html_path = catdir(@CONFIG::HTML_PATH);
    remove_tree($html_path, {safe => 1, keep_root => 1});
    print "Cleared HTML directory.\n";
}

# Install code or update existing code in working dir (/var/www/html).
sub install_code {
    my $source = catdir(@CONFIG::REPO_PATH);
    my $dest = catdir(@CONFIG::HTML_PATH);
    my $composer_subpath = $CONFIG::COMPOSER_PACKAGES;
    my @code_files = @CONFIG::CODE_FILES;
    my @composer_files = @CONFIG::COMPOSER_CODE_FILES;
    my $num_code_files = scalar @code_files;
    my $num_composer_files = scalar @composer_files;
    # This build_file_list() call lists out phpLW's own code.
    my @files = build_file_list($num_code_files, @code_files, $source);
    # This build_file_list() call lists out code provided by composer.
    push @files, build_file_list($num_composer_files, @composer_files, $source, $composer_subpath);
    print "Install/Update development code.\n";
    copy_code($source, $dest, @files);
}

# Build a list of code files used by phpLW.  This list is to help ensure that
# the working dir (/var/www/html) is clean from extraneous files in the repo.
#
# Expected params: $n = number of elems expected in @code_files
#                  @code_files = array of glob patterns for building a file list
#                  $root_path = dir to start glob search
#                  $sub_path = (optional) additional dirs off of $root_path
# Return: List of all files (and paths) that make up phpLW's code.
sub build_file_list {
    my $n = shift;
    my @code_files = splice @_, 0, $n;
    my $root_path = shift;
    # Get $sub_path, if param exists.
    my $sub_path = scalar @_ > 0 ? shift : "";
    my ($search_path, $path, $file, @file_list);

    foreach (@code_files) {
        $path = catfile($root_path, $sub_path, $_);
        foreach (glob $path) {
            next if (!-e $_); # Occasionally glob matches to a non-existent file.  Skip those.
            $file = fileparse($_);
            if (-d $_) {
                # Recurse into directory and push results onto @file_list
                $search_path = catdir($sub_path, $file);
                push @file_list, build_file_list($n, @code_files, $root_path, $search_path);
            } else {
                $file = catfile($sub_path, $file) if ($sub_path ne "");
                push @file_list, $file;
            }
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
