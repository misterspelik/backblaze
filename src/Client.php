<?php

namespace Backblaze;

use Backblaze\Storage\File;
use Backblaze\Storage\Bucket;

use Backblaze\Config\Config;
use Backblaze\Http\Client as HttpClient;

class Client
{
    private $config;
    private $http;

    public function __construct($applicationKeyId, $applicationKey, $bucketName, $options=[])
    {
        $this->config = Config::getInstance();
        $this->config->setUp($applicationKeyId, $applicationKey, $bucketName, $options);

        $this->http = new HttpClient(['exceptions' => false]);
    }

    public function upload(array $options)
    {
        $options['FileName'] = File::normalizeName($options['FileName']);

        if (!isset($options['BucketId']) && isset($options['BucketName'])) {
            $options['BucketId'] = $this->getBucketIdFromName($options['BucketName']);
        }

        if (is_resource($options['Body'])) {
            // We need to calculate the file's hash incrementally from the stream.
            $context = hash_init('sha1');
            hash_update_stream($context, $options['Body']);
            $options['hash'] = hash_final($context);

            // Similarly, we have to use fstat to get the size of the stream.
            $options['size'] = fstat($options['Body'])['size'];

            // Rewind the stream before passing it to the HTTP client.
            rewind($options['Body']);
        } else {
            // We've been given a simple string body, it's super simple to calculate the hash and size.
            $options['hash'] = sha1($options['Body']);
            $options['size']= strlen($options['Body']);
        }

        if (!isset($options['FileLastModified'])) {
            $options['FileLastModified'] = round(microtime(true) * 1000);
        }

        if (!isset($options['FileContentType'])) {
            $options['FileContentType'] = 'b2/x-auto';
        }
        
        return $this->http->upload($options);
    }

    public function download()
    {
        
    }

    private function getBucketIdFromName($name): ?string
    {
        $buckets = $this->http->getBucketsList();
        foreach ($buckets as $bucket) {
            if ($bucket->getName() === $name) {
                return $bucket->getId();
            }
        }
        return null;
    }
}