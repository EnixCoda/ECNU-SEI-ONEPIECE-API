<?php

namespace App\Museum;

use \Qiniu\Auth;
use \Qiniu\Storage\BucketManager;

class Qiniu {
    private static function getAuth() {
        $auth = new Auth(env('QINIU_AK'), env('QINIU_SK'));
        return $auth;
    }

    public static function getBucket($key = 'default') {
        return [
            'default' => env('QINIU_BUCKET_NAME'),
            'archive' => env('QINIU_ARCHIVE_BUCKET_NAME')
        ][$key];
    }

    private static function getBucketManager() {
        $auth = self::getAuth();
        $bucketManager = new BucketManager($auth);
        return $bucketManager;
    }

    public static function getUploadToken($key) {
        $auth = self::getAuth();
        $uploadToken = $auth->uploadToken(
            self::getBucket(),
            $key,
            1200,
            array(
                'insertOnly' => 1,
                'returnBody' => '{"name": $(fname), "etag": $(etag), "key": $(key)}'
            )
        );
        return $uploadToken;
    }

    public static function getList($prefix = '', $limit = 1000) {
        /**
         *
         * @param string $prefix
         * @param int $limit
         *
         * @return array
         * */
        $bucketMgr = self::getBucketManager();
        $allRecords = [];
        $marker = '';
        $limit = min(1000, $limit);
        do {
            list($records, $marker, $err) = $bucketMgr->listFiles(self::getBucket(), $prefix, $marker, $limit);
            if ($err !== NULL) {
                return NULL;
            } else {
                $allRecords = array_merge($allRecords, $records);
            }
        } while ($marker !== NULL);

        return $allRecords;
    }

    public static function archive($from) {
        return self::move($from, $from . '-' . time(), 'archive');
    }

    public static function move($from, $to, $newBucket = NULL) {
        /**
         * @param string $from
         * @param string $to
         * @param string $newBucket [optional]
         *
         * @return bool
         */
        $bucketManager = self::getBucketManager();
        $bucket = self::getBucket();
        $newBucket = $newBucket ? self::getBucket($newBucket) : $bucket;
        $err = $bucketManager->move($bucket, $from, $newBucket, $to);
        if ($err !== NULL) {
            // ($err->getResponse()->error);
            return false;
        }

        return true;
    }

    public static function delete($uniqueFileKey) {
        /**
         * @param string $uniqueFileKey
         *
         * @return bool :operation succeeded
         */
        $bucketManager = self::getBucketManager();
        $bucket = self::getBucket();
        $err = $bucketManager->delete($bucket, $uniqueFileKey);
        if ($err !== NULL) {
            // ($err->getResponse()->error);
            return false;
        }
        $result = app('db')
            ->table('file')
            ->where('key', $uniqueFileKey)
            ->delete();
        if ($result === false) {
            return false;
        }
        return true;
    }
}
