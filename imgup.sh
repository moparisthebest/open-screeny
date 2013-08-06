#!/bin/bash
# This script reads a file from stdin, and moves it to a certain directory with the shortest name possible that doesn't conflict based on the sha1sum, then echos the URL the file will be available at
#
# Mainly meant to be used for images from scripts like open-screeny.sh, it can really be used for any file uploads, nothing is format specific
#
# Required dependencies are sha1sum, and standard unix utilities tee and cut
#
# https://github.com/moparisthebest/open-screeny
#
dir_name="$1"  # directory to store images in
url="$2"       # url pointing to directory above
extension="$3" # extension to put on file

# put a . in front of the extension if it isn't empty
[ ! -z "$extension" ] && extension=".$extension"

tmp_name="/tmp/imgup-$$${extension}"

sha1="$(tee "$tmp_name" | sha1sum | cut -d' ' -f1)"
mkdir -p "$dir_name"

# find shortest substring of hash that doesn't already exist for shortest url possible
# you may change the 5 here to something longer or shorter for more or less security against people guessing your file name
for x in {5..40}
do
    new_name="${sha1:0:$x}"
    fname="${dir_name}/${new_name}${extension}"
    # if the file doesn't exist, or if it exists, but the hash is the same, break
    [ ! -e "$fname" ] || [ "$(sha1sum "$fname" | cut -d' ' -f1)" == "$sha1" ] && break
done

mv "$tmp_name" "$fname"
echo "${url}/${new_name}${extension}"
