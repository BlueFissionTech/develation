<?php

namespace BlueFission\Services;

use BlueFission\Behavioral\Configurable;
use BlueFission\Data\Storage\Storage;

class MultiFactorAuthenticator extends Authenticator
{
    protected $_config = [
        'third_party_auth_table' => 'third_party_auth',
        'provider_field' => 'provider',
        'provider_id_field' => 'provider_id',
    ];

    public function __construct(Storage $datasource, $config = null)
    {
        parent::__construct($datasource, $config);
    }

    public function authenticateWithThirdParty(string $provider, string $providerId)
    {
        if (empty($provider) || empty($providerId)) {
            $this->_status[] = "Provider and Provider ID required";
            return false;
        }

        $userInfo = $this->getUserByThirdParty($provider, $providerId);

        if (!$userInfo) {
            $this->_status[] = "User not found";
            return false;
        }

        $this->username = $userInfo[$this->config('username_field')];
        $this->id = $userInfo[$this->config('id_field')];

        return true;
    }

    private function getUserByThirdParty($provider, $providerId)
    {
        $thirdPartyAuth = $this->_datasource;
        $thirdPartyAuth->reset();
        $thirdPartyAuth->clear();
        $thirdPartyAuth->config('name', $this->config('third_party_auth_table'));
        $thirdPartyAuth->activate();
        $thirdPartyAuth->field($this->config('provider_field'), $provider);
        $thirdPartyAuth->field($this->config('provider_id_field'), $providerId);
        $thirdPartyAuth->read();
        $dbCheck = $thirdPartyAuth->data();

        if (!empty($dbCheck)) {
            $user = $this->getUser($dbCheck[$this->config('id_field')]);
            return $user;
        } else {
            return false;
        }
    }
}
