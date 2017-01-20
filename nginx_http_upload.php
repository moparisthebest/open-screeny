<?php

/*
  PHP script to handle file uploads and downloads for Prosody's mod_http_upload_external

  Tested with Nginx 1.11.8 and PHP 7.0.14

  ** Why this script?

  This script only allows uploads that have been authorized by mod_http_upload_external. It
  does allow the file name to be specified by the user which can be dangerous but this makes
  every attempt to ensure file names are safe.  It does this by only allowing one folder deep
  (only once instance of /) with the folder being a valid uuid and the name only containing
  these characters [A-Za-z0-9\-\._] which *should* be safe on any filesystem.

  With that said, I do not consider myself a PHP developer, and at the time of writing, this
  code has had no external review. Use it at your own risk. I make no claims that this code
  is secure.

  ** How to use?

  Put this file someplace the nginx user can access it, it doesn't need to be in any web root.

  It is YOUR responsibility to ensure the upload directory is not executable by the web server
  in any way, for example mod_php on apache happily executes *.php and *.php3 and a ton of other
  things unless you stop it.  You must ensure it's a pure data directory that cannot be executed.

  In nginx, set your configuration similar to:

    location ^~ /up {
        limit_except GET HEAD PUT {
            deny all;
        }
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /path/to/nginx_http_upload.php;
        fastcgi_param DOCUMENT_ROOT /var/www;
        fastcgi_param HTTP_UPLOAD_CONFIG_STORE_DIR /var/www/up/;
        fastcgi_param HTTP_UPLOAD_CONFIG_WEB_ROOT /up/;
        fastcgi_param HTTP_UPLOAD_CONFIG_SECRET "this is your secret string";
        if (\$request_method = PUT) {
            fastcgi_pass unix:/var/run/php-fpm.sock;
        }
    }

  In Prosody set:

    http_upload_external_base_url = "https://example.com/up/"
    http_upload_external_secret = "this is your secret string"

  ** License

  (C) 2016 Matthew Wild <mwild1@gmail.com>
  (C) 2016 Travis Burtrum <admin@moparisthebest.com>

  Permission is hereby granted, free of charge, to any person obtaining a copy of this software
  and associated documentation files (the "Software"), to deal in the Software without restriction,
  including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
  and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
  subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all copies or substantial
  portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
  BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
  DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

/*\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\*/
/*         CONFIGURATION OPTIONS                   */
/*\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\*/

/* Change this to an absolute path to a directory that is writable by your web server and available at $CONFIG_WEB_ROOT below (like '/var/www/up/') */
$CONFIG_STORE_DIR = $_SERVER['HTTP_UPLOAD_CONFIG_STORE_DIR'];

/* Change this to a web root pointing to the $CONFIG_STORE_DIR above (like '/up/') */
$CONFIG_WEB_ROOT = $_SERVER['HTTP_UPLOAD_CONFIG_WEB_ROOT'];

/* This must be the same as 'http_upload_external_secret' that you set in Prosody's config file */
$CONFIG_SECRET = $_SERVER['HTTP_UPLOAD_CONFIG_SECRET'];

/* For people who need options to tweak that they don't understand... here you are */
$CONFIG_CHUNK_SIZE = 4096;

/*\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\*/
/*         END OF CONFIGURATION                    */
/*\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\*/

/* Do not edit below this line unless you know what you are doing (spoiler: nobody does) */
$upload_file_name = substr($_SERVER['PHP_SELF'], strlen($CONFIG_WEB_ROOT));
$store_file_name = $CONFIG_STORE_DIR . $upload_file_name;

$request_method = $_SERVER['REQUEST_METHOD'];

if(array_key_exists('v', $_GET) === TRUE && $request_method === 'PUT') {

	$upload_file_size = $_SERVER['CONTENT_LENGTH'];
	$upload_token = $_GET['v'];

	$calculated_token = hash_hmac('sha256', "$upload_file_name $upload_file_size", $CONFIG_SECRET);
	// hash_equals compares in constant time, if your version doesn't have it, look for replacement here:
	// https://secure.php.net/manual/en/function.hash-equals.php
	if(!hash_equals($calculated_token, $upload_token)) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	// validate file name

	// should only have one /
	if(substr_count($upload_file_name, '/') > 1) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	$dir_file = explode('/', $upload_file_name);
	$uuid = $dir_file[0];
	$file = $dir_file[1];

	// validate uuid
	if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid)) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	// validate filename, can be a little less strict
	if (!preg_match('/^[A-Za-z0-9\-\._]+$/', $file)) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	// safe to create uuid folder now
	// ensure the single directory is created, with only web server having access
	@mkdir($CONFIG_STORE_DIR . $uuid, 0700);

	/* Open a file for writing */
	$store_file = @fopen($store_file_name, 'x');

	// file already exists
	if($store_file === FALSE) {
		header('HTTP/1.0 409 Conflict');
		exit;
	}

	// now that the file has been created, ensure whole file is under our $CONFIG_STORE_DIR
	// this really should be impossible due to our regex's above, but no harm in being extra
	// paranoid before we write to a file
	$real_store_file_name = realpath($store_file_name);
	if ($real_store_file_name === false || strpos($real_store_file_name, $CONFIG_STORE_DIR) !== 0) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	/* PUT data comes in on the stdin stream */
	$incoming_data = fopen('php://input', 'r');

	/* Read the data a chunk at a time and write to the file */
	while ($data = fread($incoming_data, $CONFIG_CHUNK_SIZE)) {
  		fwrite($store_file, $data);
	}

	/* Close the streams */
	fclose($incoming_data);
	fclose($store_file);
} else {
	header('HTTP/1.0 400 Bad Request');
}

exit;
