<?php

namespace BlueFission\Services;

use BlueFission\Behavioral\Configurable;
use BlueFission\Data\Storage\Storage;
use BlueFission\Net\HTTP;

class OAuthAuthenticator extends Authenticator
{
    protected $_config = [
        'oauth_table' => 'oauth',
        'provider_field' => 'provider',
        'access_token_field' => 'access_token',
    ];

    public function __construct(Storage $datasource, $config = null)
    {
        parent::__construct($datasource, $config);
    }

    public function authenticateWithOAuth(string $provider, string $accessToken)
    {
        if (empty($provider) || empty($accessToken)) {
            $this->_status[] = "Provider and Access Token required";
            return false;
        }

        $userInfo = $this->getUserByAccessToken($provider, $accessToken);

        if (!$userInfo) {
            $this->_status[] = "User not found";
            return false;
        }

        $this->username = $userInfo[$this->config('username_field')];
        $this->id = $userInfo[$this->config('id_field')];

        return true;
    }

    private function getUserByAccessToken($provider, $accessToken)
    {
        $oauth = $this->_datasource;
        $oauth->reset();
        $oauth->clear();
        $oauth->config('name', $this->config('oauth_table'));
        $oauth->activate();
        $oauth->field($this->config('provider_field'), $provider);
        $oauth->field($this->config('access_token_field'), $accessToken);
        $oauth->read();
        $dbCheck = $oauth->data();

        if (!empty($dbCheck)) {
            $user = $this->getUser($dbCheck[$this->config('id_field')]);
            return $user;
        } else {
            return false;
        }
    }
}
