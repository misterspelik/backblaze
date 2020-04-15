<?php

namespace Backblaze\Config;

use Carbon\Carbon;

class Config
{
    protected $application_key_id;
    protected $application_key;
    protected $bucket_name;
    protected $bucket_id;
    protected $auth_timeout_seconds = 43200; // 12 * 60 * 60 (12 hours by default)
    protected $reauth_time = null;
    protected $options;

    protected $is_set_up = false;

    private static $instance = null;

    public static function getInstance() : self
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Configuration setter.
     *
     * @param $applicationKeyId
     * @param $applicationKey
     * @param $bucketName
     * @param $options
     */
    public function setUp($applicationKeyId, $applicationKey, $bucketName, $options=[]): void
    {
        $this->application_key_id = $applicationKeyId;
        $this->application_key = $applicationKey;
        $this->bucket_name = $bucketName;

        $this->options = $options;
        if (!empty($this->options['auth_timeout_seconds'])) {
            $this->auth_timeout_seconds = $this->options['auth_timeout_seconds'];
        }

        $this->reauth_time = Carbon::now('UTC')->subSeconds($this->auth_timeout_seconds * 2);

        $this->is_set_up = true;
    }

    public function getAapplicationKeyId(): string
    {
        return $this->application_key_id;
    }

    public function getApplicationKey(): string
    {
        return $this->application_key;
    }

    public function getBucketName(): string
    {
        return $this->bucket_name;
    }

    public function getBucketId(): string
    {
        return $this->bucket_id;
    }

    public function getAuthTimeout(): int
    {
        return $this->auth_timeout_seconds;
    }

    public function getBaseUrl(): string
    {
        return Urls::BASE_URL;
    }

    public function getApiVersion(): string
    {
        return Urls::API_VERSION;
    }

    public function getApiAuthUrl(): string
    {
        return implode('/', [Urls::BASE_URL, Urls::API_VERSION, Urls::API_AUTH]);
    }

    public function touchReauthTime()
    {
        $this->reauth_time = Carbon::now('UTC');
        $this->reauth_time->addSeconds($this->auth_timeout_seconds);
    }

    public function isSetUp(): bool
    {
        return $this->is_set_up;
    }

    public function needReauth(): bool
    {
        if (is_null($this->reauth_time)){
            return true;
        }

        return (Carbon::now('UTC')->timestamp >= $this->reauth_time->timestamp);
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}