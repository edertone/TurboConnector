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
 * MailMicrosoft365Manager class
 */
class MailMicrosoft365Manager extends MailManagerBase{


    /**
     * @see setCredentialsClientId()
     * @var string
     */
    private $_clientId = '';


    /**
     * @see setCredentialsClientSecret()
     * @var string
     */
    private $_clientSecret = '';


    /**
     * @see setCredentialsTenantId()
     * @var string
     */
    private $_tenantId = '';


    /**
     * This class is an abstraction of the Microsoft Office 365 email service. It allows us to send emails through this service
     * in an easy way, through the Microsoft Graph API.
     *
     * It requires the composer "guzzlehttp/guzzle" depedency
     *
     * How to use this class:
     *
     * - Before creating an instance of MailMicrosoft365Manager, the guzzlehttp library must be downloaded and deployed
     *   into our project with composer.
     *
     * - We must login on the microsoft portal and register an application to be able to access the microsoft 365 email features through the API.
     *   We must create a clientId, a tenantId and a clientSecret.
     *   We must then set the required permissions to that clientId so it can send emails. We must be sure that Mail.Send permission is enabled.
     *
     * - We can then access the feature with this class using the generated credentials
     *
     * @throws UnexpectedValueException
     *
     * @param string $vendorRoot A full file system path to the root of the composer vendor folder were the guzzlehttp/guzzle library is installed.
     *               It must be accessible by our project and contain a valid autoload.php file
     */
    public function __construct(string $vendorRoot){

        if(!is_file($vendorRoot.'/autoload.php')){

            throw new UnexpectedValueException('Specified vendorRoot folder is not valid. Could not find autoload.php file on '.$vendorRoot);
        }

        require_once $vendorRoot.'/autoload.php';
    }


    /**
     * Specify the string identifier for the Microsoft GRAPH api client ID credentials.
     * This will be used to authorize the mail sending
     *
     * @param string $clientId A valid client ID string for the microsoft API
     */
    public function setCredentialsClientId($clientId){

        $this->_clientId = $clientId;
    }


    /**
     * Specify the string identifier for the Microsoft GRAPH api client secret.
     * This will be used to authorize the mail sending
     *
     * @param string $clientSecret A valid client secret string for the microsoft API
     */
    public function setCredentialsClientSecret($clientSecret){

        $this->_clientSecret = $clientSecret;
    }


    /**
     * Specify the string identifier for the Microsoft GRAPH api tenant ID.
     * This will be used to authorize the mail sending
     *
     * @param string $tenantId A valid tenant Id string for the microsoft API
     */
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
    }
}
