#!/bin/sh

# Source: https://helgesver.re/articles/laravel-sail-create-minio-bucket-automatically

/usr/bin/mc alias set local ${S3_ENDPOINT} ${S3_ACCESS_KEY_ID} ${S3_SECRET_ACCESS_KEY};
/usr/bin/mc rm -r --force local/${S3_BUCKET};
/usr/bin/mc mb --ignore-existing local/${S3_BUCKET};
/usr/bin/mc anonymous set public local/${S3_BUCKET};

exit 0;
