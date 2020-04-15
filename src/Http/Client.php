<?php

namespace Backblaze\Http;

use Backblaze\Config\Config;
use Backblaze\Storage\Bucket;
use Backblaze\Storage\File;
use Backblaze\Handlers\ErrorHandler;
use GuzzleHttp\Client as GuzzleClient;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;

use Backblaze\Exceptions\Config\ConfigWasntSetupException;

/**
 * Client wrapper around Guzzle.
 */
class Client extends GuzzleClient
{
    private $apiUrl = null;
    private $apiAuthToken = null;

    private $uploadUrl = null;
    private $uploadAuthToken = null;

    private $config;

    public function __construct($options=[])
    {
        parent::__construct($options);

        $this->config = Config::getInstance();
        if (!$this->config->isSetUp()){
            throw new ConfigWasntSetupException();
        }
    }

    public function isAuthorized(): bool
    {
        return (!is_null($this->apiAuthToken) && !is_null($this->apiUrl));
    }

    public function getUploadUrl($bucketId): void
    {
        $response = $this->authorizedRequest('POST', '/b2_get_upload_url', [
            'bucketId' => $bucketId,
        ]);

        $this->uploadUrl = $response['uploadUrl'];
        $this->uploadAuthToken = $response['authorizationToken'];

    }

    public function getBucketsList(): array
    {
        $response = $this->authorizedRequest('POST', '/b2_list_buckets', [
            'accountId' => $this->accountId,
        ]);

        $buckets = [];
        foreach ($response['buckets'] as $bucket) {
            $buckets[] = new Bucket($bucket['bucketId'], $bucket['bucketName'], $bucket['bucketType']);
        }

        return $buckets;
    }

    public function upload($bucketId, array $options): array
    {
        $this->getUploadUrl($bucketId);

        $response = $this->request('POST', $this->uploadUrl, [
            'headers' => [
                'Authorization'                      => $this->uploadAuthToken,
                'Content-Type'                       => $options['FileContentType'],
                'Content-Length'                     => $options['size'],
                'X-Bz-File-Name'                     => $options['FileName'],
                'X-Bz-Content-Sha1'                  => $options['hash'],
                'X-Bz-Info-src_last_modified_millis' => $options['FileLastModified'],
            ],
            'body' => $options['Body'],
        ]);

        return new File(
            $response['fileId'],
            $response['fileName'],
            $response['contentSha1'],
            $response['contentLength'],
            $response['contentType'],
            $response['fileInfo']
        );
    }

    public function authorize(): bool
    {
        if (!$this->config->needReauth()){
            return true;
        }

        $credentials = base64_encode($this->config->getAapplicationKeyId() . ":" . $this->config->getAapplicationKey());

        $response = $this->request('GET', $this->config->getApiAuthUrl(),[
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials,
            ],
        ]);

        $this->apiAuthToken = $response['authorizationToken'];
        $this->apiUrl = $response['apiUrl'].'/'.$this->config->getApiVersion();
        $this->downloadUrl = $response['downloadUrl'];
        
        $this->config->touchReauthTime();

        return true;
    }

    public function authorizedRequest($method, $uri, $json = [])
    {
        $this->authorize();

        return $this->request($method, $this->apiUrl.$uri, [
            'headers' => [
                'Authorization' => $this->apiAuthToken,
            ],
            'json' => $json,
        ]);
    }

    /**
     * Sends a response to the B2 API, automatically handling decoding JSON and errors.
     *
     * @param string $method
     * @param null   $uri
     * @param array  $options
     * @param bool   $asJson
     *
     * @throws GuzzleException
     *
     * @return mixed|ResponseInterface|string
     */
    public function request($method, $uri = null, array $options = [], $asJson = true)
    {
        $response = parent::request($method, $uri, $options);

        if ($response->getStatusCode() !== 200) {
           // ErrorHandler::handleErrorResponse($response);
        }

        if ($asJson) {
            return json_decode($response->getBody(), true);
        }

        return $response->getBody()->getContents();
    }
}
