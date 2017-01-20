#!/bin/sh
set -e

# export these from another file, or set them here if you are lazy
# http_upload_url='https://example.com/up/'
# http_upload_hmac_key='this is your secret string'

[ -z "$http_upload_url" ]             && echo "variable http_upload_url must be set, exiting..."      1>&2 && exit 1
[ -z "$http_upload_hmac_key" ]        && echo "variable http_upload_hmac_key must be set, exiting..." 1>&2 && exit 1
[ -z "$http_upload_file_size_limit" ] && http_upload_file_size_limit=$((100 * 1024 * 1024)) # bytes, default to 100 * 1024 * 1024 = 100 MB

file_to_upload="$1"

base_name="$(basename "$file_to_upload")"

file_size="$(stat -c %s "$file_to_upload")"

[ $file_size -gt $http_upload_file_size_limit ] && echo "file size $file_size greater than limit of $http_upload_file_size_limit, exiting..." 1>&2 && exit 1

uuid="$(uuidgen 2>/dev/null || cat /proc/sys/kernel/random/uuid 2>/dev/null || cat /compat/linux/proc/sys/kernel/random/uuid)"

hmac_secret="$(echo -n "${uuid}/${base_name} $file_size" | openssl dgst -sha256 -hmac "$http_upload_hmac_key" -r | awk '{ print $1 }')"

get_url="${http_upload_url}${uuid}/${base_name}"

curl -f -T "$file_to_upload" "${get_url}?v=${hmac_secret}"

echo "$get_url"
