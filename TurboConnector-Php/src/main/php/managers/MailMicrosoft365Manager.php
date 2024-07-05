<?php

/**
 * TurboConnector is a general purpose library to facilitate connection to remote locations and external APIS.
 *
 * Website : -> https://turboframework.org/en/libs/turboconnector
 * License : -> Licensed under the Apache License, Version 2.0. You may not use this file except in compliance with the License.
 * License Url : -> http://www.apache.org/licenses/LICENSE-2.0
 * CopyRight : -> Copyright 2024 Edertone Advanded Solutions. http://www.edertone.com
 */


namespace org\turboconnector\src\main\php\managers;


use UnexpectedValueException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


/**
 * TODO docs
 */
class MailMicrosoft365Manager extends MailManagerBase{


    private $_clientId = '';
    private $_clientSecret = '';
    private $_tenantId = '';


    public function __construct(string $vendorRoot){

        if(!is_file($vendorRoot.'/autoload.php')){

            throw new UnexpectedValueException('Specified vendorRoot folder is not valid. Could not find autoload.php file on '.$vendorRoot);
        }

        require_once $vendorRoot.'/autoload.php';
    }


    public function setCredentialsClientId($clientId){

        $this->_clientId = $clientId;
    }


    public function setCredentialsClientSecret($clientSecret){

        $this->_clientSecret = $clientSecret;
    }


    public function setCredentialsTenantId($tenantId){

        $this->_tenantId = $tenantId;
    }


    public function send(){

        $this->_sanitizeValues();

        try {

            // Get the access token to authenticate to the api
            $tokenEndpoint = "https://login.microsoftonline.com/{".$this->_tenantId."}/oauth2/v2.0/token";

            $guzzle = new Client();

            $response = $guzzle->post($tokenEndpoint, [
                'form_params' => [
                    'client_id' => $this->_clientId,
                    'client_secret' => $this->_clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $accessToken = json_decode($response->getBody()->getContents())->access_token;

            // Compose the list of receiver addresses to send
            $receiverAdressesArray = [];

            foreach ($this->_receiverAddresses as $receiver) {

                $receiverAdressesArray[] = ['emailAddress' => ['address' => $receiver]];
            }

            // Compose the email
            $email = [
                'message' => [
                    'subject' => $this->_subject,
                    'body' => [
                        'contentType' => $this->_isHTML ? 'HTML' : 'Text',
                        'content' => $this->_body
                    ],
                    'toRecipients' => $receiverAdressesArray
                ]
            ];

            // Send the email
            $graphEndpoint = "https://graph.microsoft.com/v1.0/users/$this->_senderAddress/sendMail";

            $guzzle->post($graphEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json; charset=UTF-8'
                ],
                'json' => $email
            ]);

        } catch (GuzzleException $e) {

            throw new UnexpectedValueException($e->getMessage());
        }

        return true;
    }
}
