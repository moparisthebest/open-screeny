# Open-Screeny

open-screeny.sh
------------
This script takes screenshots, uploads them to a service, optionally shortens the URL, puts it in your clipboard, and opens it in a browser.

Currently supported services are [imgur][1], [puush][2], http_upload.sh, and imgup.sh (the latter 2 being included in this repo)
There is also an open-source implementation of the [puush server API][5] this should work with.

Currently supported URL shortening services are [tinyurl][3] and [b1t.it][4]

Required dependencies are curl, scrot, xclip, notify-send, xdg-open, md5sum, and standard unix utilities grep, date, and cut

See open-screeny.sh for the enviromental variables that need set for certain services, the default is to use imgur which requires no additional configuration.

You probably want to bind this to 'Print-Screen' or some other button combination for the best ease-of-use.

http_upload.sh
------------
This script uploads the file given in the first argument to a special http_upload script on the server, compatible with prosody's [mod_http_upload_external][6].

One example PHP script for the server included here as nginx_http_upload.php in this repository, another is included with the prosody module.

Mainly meant to be used for images from scripts like open-screeny.sh, it can really be used for any file uploads, nothing is format specific.

Required dependencies are openssl, curl, and standard unix utilities stat, awk, and basename

imgup.sh
------------
This script reads a file from stdin, and moves it to a certain directory with the shortest name possible that doesn't conflict based on the sha1sum, then echos the URL the file will be available at.

This can be called locally or over ssh for ease of use.

Mainly meant to be used for images from scripts like open-screeny.sh, it can really be used for any file uploads, nothing is format specific.

Required dependencies are sha1sum, and standard unix utilities tee and cut

Licensing
------------
Seriously?  It consists of trivial shell scripts using standard unix utilities. If you must have a license, take your pick of any GNU, Apache, BSD, or MIT license, any version.  If you need to modify this code though, you should contribute back to it, if just to be nice.

nginx_http_upload.php is licensed seperately as mentioned at the top of the file since it's a derived work.

[1]: http://imgur.com/
[2]: http://puush.me/
[3]: https://tinyurl.com/
[4]: http://b1t.it/
[5]: https://github.com/Hidendra/puush-api
[6]: https://modules.prosody.im/mod_http_upload_external.html
