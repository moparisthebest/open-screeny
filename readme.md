# Open-Screeny

open-screeny.sh
------------
This script takes screenshots, uploads them to a service, optionally shortens the URL, puts it in your clipboard, and opens it in a browser.

Currently supported services are [imgur][1], [puush][2], and imgup.sh (which is a script included with this one that can be called locally or over ssh)
There is also an open-source implementation of the [puush server API][5] this should work with.

Currently supported URL shortening services are [tinyurl][3] and [b1t.it][4]

Required dependencies are curl, scrot, xclip, notify-send, xdg-open, md5sum, and standard unix utilities grep, date, and cut

See open-screeny.sh for the enviromental variables that need set for certain services, the default is to use imgur which requires no additional configuration.

You probably want to bind this to 'Print-Screen' or some other button combination for the best ease-of-use.

imgup.sh
------------
This script reads a file from stdin, and moves it to a certain directory with the shortest name possible that doesn't conflict based on the sha1sum, then echos the URL the file will be available at.

Mainly meant to be used for images from scripts like open-screeny.sh, it can really be used for any file uploads, nothing is format specific.

Required dependencies are sha1sum, and standard unix utilities tee and cut

Licensing
------------
Seriously?  It consists of trivial shell scripts using standard unix utilities. If you must have a license, take your pick of any GNU, Apache, BSD, or MIT license, any version.  If you need to modify this code though, you should contribute back to it, if just to be nice.

[1]: http://imgur.com/
[2]: http://puush.me/
[3]: https://tinyurl.com/
[4]: http://b1t.it/
[5]: https://github.com/Hidendra/puush-api