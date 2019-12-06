<?php

namespace Ncucc\SimpleAuth;

class Client {
    private $config;
    private $client;
    
    function __construct($config) {
        $this->config = $config;
        $this->client = new \GuzzleHttp\Client();
    }
    
    public function retrieveToken($code) {
        $res = $this->client->request('POST', $this->config['token_url'], [
            'headers' => [
                 'Accept' => 'application/json',
             ],
             'auth' => [
                 $this->config['client_id'], 
                 $this->config['client_secret']
             ],
             'form_params' => [
                 'grant_type' => "authorization_code",
                 'redirect_uri' => '',
                 'code' => $code,
                 'client_id' => $this->config['client_id'],
             ]
        ]);
        
        if (($code = $res->getStatusCode()) == 200) {
            $data = json_decode($res->getBody());

            if (property_exists($data, 'access_token')) {
                return $data->access_token;
            } else if (property_exists($data, 'error')) {
                throw new \Exception("oauth error: " . $data->error);
            } else {
                throw new \Exception("oauth error");
            }
        } else {
            throw new \Exception("http status code = " . $code);
        }
    }
    
    public function retrieveUserInfo($token) {
        $res = $this->client->request('GET', $this->config['userinfo_url'], [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);
        
        if (($code = $res->getStatusCode()) == 200) {
            return json_decode($res->getBody());
        } else {
            throw new \Exception("http status code = " . $code);
        }
    }
}
