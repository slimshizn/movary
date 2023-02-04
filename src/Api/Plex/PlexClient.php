<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Util\Json;
use Movary\ValueObject\Config;
use GuzzleHttp\Client as httpClient;
use RuntimeException;

class PlexClient
{
    private const BASE_URL = "https://plex.tv/api/v2";
    private const APP_NAME = 'Movary';
    private $defaultPostAndGetData;
    private $defaultPostAndGetHeaders;

    public function __construct(private readonly httpClient $httpClient, private readonly Config $config)
    {
        $this->defaultPostAndGetData = [
            'X-Plex-Client-Identifier' => $this->config->getAsString('PLEX_IDENTIFIER'),
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->config->getAsString('APPLICATION_VERSION'),
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
        $this->defaultPostAndGetHeaders = [
            'accept' => 'application/json'
        ];
    }


    public function sendGetRequest(string $relativeUrl, ?array $customGetData = [], ?array $customGetHeaders = []) : Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;
        $data = array_merge($this->defaultPostAndGetData, $customGetData);
        $httpHeaders = array_merge($this->defaultPostAndGetHeaders, $customGetHeaders);
        $options = [
            'query' => $data,
            'headers' => $httpHeaders
        ];
        $response = $this->httpClient->request('get', $url, $options);
        $statusCode = $response->getStatusCode();
        // match(true) {
        //     $statusCode === 401 => throw PlexAuthenticationError::create(),
        //     $statusCode !== 200 && $statusCode !== 201 => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
        //     default => true
        // };
        return Json::decode((string)$response->getBody());
    }

    public function sendPostRequest(string $relativeUrl, ?array $customPostData = [], ?array $customPostHeaders = []) : Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;
        $postData = array_merge($this->defaultPostAndGetData, $customPostData);
        $httpHeaders = array_merge($this->defaultPostAndGetHeaders, $customPostHeaders);
        $options = [
            'form_params' => $postData,
            'headers' => $httpHeaders
        ];
        $response = $this->httpClient->request('post', $url, $options);
        $statusCode = $response->getStatusCode();
        match(true) {            
            $statusCode === 401 => throw PlexAuthenticationError::create(),
            $statusCode !== 200 && $statusCode !== 201 => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }
}