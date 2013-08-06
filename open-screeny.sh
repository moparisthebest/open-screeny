#!/bin/bash
# This script takes screenshots, uploads them to a service, optionally shortens the URL, puts it in your clipboard, and opens it in a browser.
# Currently supported services are imgur, puush, and imgup.sh (which is a script included with this one that can be called locally or over ssh)
# Currently supported URL shortening services are tinyurl and b1t.it
#
# Required dependencies are curl, scrot, xclip, notify-send, xdg-open, md5sum, and standard unix utilities grep, date, and cut
#
# You may add upload and url shortening methods as you please, if you do, please contribute them back with a pull request or patch.
# https://github.com/moparisthebest/open-screeny
#
# You may set your defaults here, but ideally you will set them by exporting the right values in your .profile or .bashrc or similar
set -e # exit on error
[ -z "$puush_api_key" ] && export puush_api_key='' # find API key here: http://puush.me/account/settings
[ -z "$imgur_api_key" ] && export imgur_api_key='486690f872c678126a2c09a9e196ce1b' # nabbed from here: https://github.com/dave1010/scripts/blob/master/shoot
[ -z "$imgup_path" ]    && export imgup_path='' # example: 'ssh user@host ~/imgup.sh ~/htdocs/s http://host/s png'

# if these are empty, go with defaults we know to exist and work without configuration
[ -z "$upload" ] && export upload='imgur' # must be one of 'puush', 'imgur', or 'imgup'
[ -z "$shorturl" ] && export shorturl=''  # must be one of 'tinyurl', 'b1tit', or '' (no shorturl)

filename="$1" # if there is no filename to upload, we take a screenshot and upload that

#######################################################################################################################################
# The following are implemented upload methods, they take one argument, the file to upload, and echo the URL the file was uploaded to #
#######################################################################################################################################

function upload_imgur {
    [ -z "$imgur_api_key" ] && echo '$imgur_api_key is empty, cannot upload!' && return
    curl -s -F "image=@$1" -F "key=$imgur_api_key" https://imgur.com/api/upload.xml | grep -E -o "<original_image>(.)*</original_image>" | grep -E -o "http://i.imgur.com/[^<]*"
}

function upload_puush {
    [ -z "$puush_api_key" ] && echo '$puush_api_key is empty, cannot upload!' && return
    curl -s -X POST -H 'Content-Type: multipart/form-data' -F "k=$puush_api_key" -F "c=$(md5sum "$1" | cut -d' ' -f1)" -F "z=poop" -F "f=@${1};filename=ss ($(date '+%Y-%m-%d at %I.%M.%S')).png;type=application/octet-stream" http://puush.me/api/up | cut -d, -f2
}

function upload_imgup {
    [ -z "$imgup_path" ] && echo '$imgup_path is empty, cannot upload!' && return
    $imgup_path < "$1"
}

####################################################################################################################################
# The following are implemented shorturl methods, they take one argument, the long url, and echo the URL the long was shortened to #
####################################################################################################################################

function shorturl_tinyurl {
    curl -s "https://tinyurl.com/api-create.php?url=$1"
}

function shorturl_b1tit {
    # caution!  This doesn't work as of this moment because they recently added a still-undocumented 'secret key' parameter that is required...
    echo "http://b1t.it/$(curl -s -d "url=$1" http://b1t.it | sed -e 's/^.*"id":"//' -e 's/".*$//')"
}

# you probably don't need to touch below here

if [ -z "$filename" ]
then
    filename="/tmp/open-screeny-$$.png"                  # store file someplace
    #trap 'rm -f "$filename"' EXIT                             # delete file on exit
    scrot -z -s -b -q 0 "$filename"                           # take screenshot
fi
url="$("upload_$upload" "$filename")"                         # upload image
[ ! -z "$shorturl" ] && url="$("shorturl_$shorturl" "$url")"  # shorten url if requested
echo "$url" | xclip -selection c                              # put url in clipboard
notify-send "Screenshot Uploaded" "<a href=\"$url\">$url</a>" # pop up handy notification
xdg-open "$url"                                               # open url in browser
                                                              # profit?
