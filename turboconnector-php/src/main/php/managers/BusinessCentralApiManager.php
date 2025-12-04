<?php

/**
 * General purpose library to facilitate connection to 3rd party services, remote locations and external APIS.
 *
 * Website : -> https://turboframework.org/en/libs/turboconnector
 * License : -> Licensed under the Apache License, Version 2.0. You may not use this file except in compliance with the License.
 * License Url : -> http://www.apache.org/licenses/LICENSE-2.0
 * CopyRight : -> Copyright 2024 Edertone Advanded Solutions. http://www.edertone.com
 */


namespace org\turboconnector\src\main\php\managers;


use UnexpectedValueException;


/**
 * BusinessCentralApiManager class
 */
class BusinessCentralApiManager {

    /** @var string */
    private $_clientId = '';

    /** @var string */
    private $_clientSecret = '';

    /** @var string */
    private $_tenantId = '';

    /** @var string */
    private $_baseUrl = '';

    /** @var string */
    private $_companyID = '';

    /**
     * Cached access token
     * @var string|null
     */
    private $_accessToken = null;

    /**
     * Timestamp when the token expires
     * @var int
     */
    private $_tokenExpiry = 0;


    /**
     * Specify the string identifier for the Microsoft GRAPH api client ID credentials.
     * This will be used to authorize the API requests
     *
     * @param string $clientId A valid client ID string for the microsoft API
     */
    public function setCredentialsClientId(string $clientId){
        $this->_clientId = $clientId;
    }

    /**
     * Specify the string identifier for the Microsoft GRAPH api client secret.
     * This will be used to authorize the API requests
     *
     * @param string $clientSecret A valid client secret string for the microsoft API
     */
    public function setCredentialsClientSecret(string $clientSecret){
        $this->_clientSecret = $clientSecret;
    }

    /**
     * Specify the string identifier for the Microsoft GRAPH api tenant ID.
     * This will be used to authorize the API requests
     *
     * @param string $tenantId A valid tenant Id string for the microsoft API
     */
    public function setCredentialsTenantId(string $tenantId){
        $this->_tenantId = $tenantId;
    }

    /**
     * Specify the base URL for the Business Central API
     *
     * @param string $baseUrl A valid full base URL for the Business Central API
     */
    public function setBaseUrl(string $baseUrl){
        $this->_baseUrl = $baseUrl;
    }

    /**
     * Specify the company ID to use in the Business Central API calls
     *
     * @param string $companyID The exact company ID as defined in Business Central
     */
    public function setCompanyID(string $companyID){
        $this->_companyID = $companyID;
    }

    /**
     * Obtain an access token from the Microsoft Identity platform.
     * Includes simple caching mechanism to avoid re-fetching on every call.
     *
     * @return string
     * @throws UnexpectedValueException
     */
    private function _obtainToken(){

        // Check if we have a valid cached token (with 5-minute safety buffer)
        if ($this->_accessToken && time() < ($this->_tokenExpiry - 300)) {
            return $this->_accessToken;
        }

        $tokenEndpoint = "https://login.microsoftonline.com/" . $this->_tenantId . "/oauth2/v2.0/token";

        $postData = [
            'client_id'     => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'scope'         => 'https://api.businesscentral.dynamics.com/.default',
            'grant_type'    => 'client_credentials',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new UnexpectedValueException("Failed to obtain access token. HTTP Code: {$httpCode}. Error: {$curlError}. Response: {$response}");
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new UnexpectedValueException("Access token not found in response: {$response}");
        }

        // Cache the token and expiry
        $this->_accessToken = $data['access_token'];
        // Default to 3599 seconds if expires_in is missing
        $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 3599;
        $this->_tokenExpiry = time() + $expiresIn;

        return $this->_accessToken;
    }

    /**
     * Debug method to list available services.
     *
     * By default, this targets the 'ODataV4' root, which returns the full list of
     * all web services published in Business Central (Pages, Codeunits, Queries).
     *
     * Examples:
     *
     * 1. List ALL Published Web Services (Restores previous behavior):
     *    $services = $bcManager->getAvailableServices();
     *
     * 2. List Standard API v2.0 Entities (Items, Customers, etc.):
     *    $services = $bcManager->getAvailableServices('api/v2.0');
     *
     * 3. Check a Custom API Group:
     *    $services = $bcManager->getAvailableServices('api/APIPublisher/APIGroup/v2.0');
     *
     * @param string $endpoint The endpoint to inspect (default: 'ODataV4').
     *
     * @return array The list of available entities/services.
     */
    public function getAvailableServices($endpoint = 'ODataV4'){

        // Special handling if user wants to see Standard API entities inside the current company
        if($endpoint === 'api/v2.0' && !empty($this->_companyID)){
            $endpoint .= "/companies({$this->_companyID})";
        }

        $result = $this->requestGet($endpoint);

        return isset($result['value']) ? $result['value'] : $result;
    }

    /**
     * Get the list of companies available in the Business Central instance
     *
     * @return array
     */
    public function getCompanies(){

        $result = $this->requestGet('api/v2.0/companies');
        return isset($result['value']) ? $result['value'] : [];
    }

    /**
     * Perform a generic GET request to the Business Central API using cURL.
     * Automatically replaces '{companyId}' placeholder in the URI with the configured ID.
     *
     * @param string $uri The API endpoint URI (e.g., "publisher/group/v2.0/companies({companyId})/Entity").
     * @param array  $queryParams (Optional) Query parameters for the request (e.g., ['$filter' => ...]).
     *
     * @return array|null The API response data, or null on failure.
     * @throws UnexpectedValueException
     */
    public function requestGet(string $uri, array $queryParams = []){

        // Validation: Base URL
        if (empty($this->_baseUrl)) {
            throw new UnexpectedValueException("Base URL is not set. Call setBaseUrl() first.");
        }

        // Validation: Check if URI needs Company ID but it's not set
        if (strpos($uri, '{companyId}') !== false && empty($this->_companyID)) {
            throw new UnexpectedValueException("The URI contains '{companyId}' but no Company ID has been set via setCompanyID().");
        }

        $accessToken = $this->_obtainToken();

        // Auto-replace the {companyId} placeholder if the ID is set
        $uri = str_replace('{companyId}', $this->_companyID, $uri);

        // Construct the Full URL: ensure no double slashes when joining base URL and URI
        $url = rtrim($this->_baseUrl, '/') . '/' . ltrim($uri, '/');

        // Add Query Parameters
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        // Execute cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Accept: application/json"
                ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle Errors
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new UnexpectedValueException("Error on GET request to '{$uri}': [{$httpCode}] " . $response . ($curlError ? " Curl Error: $curlError" : ""));
        }

        return json_decode($response, true);
    }
}