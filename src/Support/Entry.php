<?php

namespace MacsiDigital\Zoom\Support;

use MacsiDigital\API\Support\Authentication\JWT;
use MacsiDigital\API\Support\Entry as ApiEntry;
use MacsiDigital\Zoom\Facades\Client;

class Entry extends ApiEntry
{
    protected $modelNamespace = '\MacsiDigital\Zoom\\';

    protected $pageField = 'page_number';

    protected $maxQueries = '5';

    protected $apiKey = null;

    protected $apiSecret = null;
    protected $account_id = null;

    protected $tokenLife = null;

    protected $baseUrl = null;

    // Amount of pagination results per page by default, leave blank if should not paginate
    // Without pagination rate limits could be hit
    protected $defaultPaginationRecords = '30';

    // Max and Min pagination records per page, will vary by API server
    protected $maxPaginationRecords = '300';

    protected $resultsPageField = 'page_number';
    protected $resultsTotalPagesField = 'page_count';
    protected $resultsPageSizeField = 'page_size';
    protected $resultsTotalRecordsField = 'total_records';

    protected $allowedOperands = ['='];

    /**
     * Entry constructor.
     * @param $apiKey
     * @param $apiSecret
     * @param $tokenLife
     * @param $maxQueries
     * @param $baseUrl
     */
    public function __construct($apiKey = null, $apiSecret = null, $tokenLife = null, $maxQueries = null, $baseUrl = null, $account_id = null)
    {
        $this->apiKey = $apiKey ? $apiKey : config('zoom.api_key');
        $this->apiSecret = $apiSecret ? $apiSecret : config('zoom.api_secret');
        $this->account_id = $account_id ? $account_id : config('zoom.account_id');
        $this->tokenLife = $tokenLife ? $tokenLife : config('zoom.token_life');
        $this->maxQueries = $maxQueries ? $maxQueries : (config('zoom.max_api_calls_per_request') ? config('zoom.max_api_calls_per_request') : $this->maxQueries);
        $this->baseUrl = $baseUrl ? $baseUrl : config('zoom.base_url');
    }

    public function newRequest()
    {
        $zoom_supported_authentication_methods = [
            'jwt' => function () {
                return $this->jwtRequest();
            },
            'oauth2' => function () {
                return $this->oauth2Request();
            }
        ];

        if (array_key_exists(config('zoom.authentication_method'), $zoom_supported_authentication_methods)) {
            return $zoom_supported_authentication_methods[config('zoom.authentication_method')]();
        }
    }

    public function jwtRequest()
    {
        $jwtToken = JWT::generateToken(['iss' => $this->apiKey, 'exp' => time() + $this->tokenLife], $this->apiSecret);

        return Client::baseUrl($this->baseUrl)->withToken($jwtToken);
    }

    public function oauth2Request()
    {
        if (session()->has('zoom_oauth_token') || session()->get('zoom_oauth_token_expire_in') > time()) {
            return  Client::baseUrl($this->baseUrl)->withToken(session()->get('zoom_oauth_token')['access_token']);
        } else {
            $zoom_token_res = $this->generateAccessToken();
            if ($zoom_token_res->status() === 200) {
                session(['zoom_oauth_token' => $zoom_token_res->json()]);
                session(['zoom_oauth_token_expire_in' => strtotime(time() + $zoom_token_res->json()['expires_in'])]);
                return  Client::baseUrl($this->baseUrl)->withToken(session()->get('zoom_oauth_token')['access_token']);
            }
        }
    }
    public function generateAccessToken($grant_type = "account_credentials")
    {
        return  Client::baseUrl("https://zoom.us/oauth/token")
            ->withBasicAuth($this->apiKey, $this->apiSecret)->asForm()->post('',  [
                "grant_type" => $grant_type,
                "account_id" => $this->account_id
            ]);
    }
}
