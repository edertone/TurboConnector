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

use CURLFile;
use UnexpectedValueException;


class TurboDockerFileToolsManager {


    private $baseUrl;


    /**
     * Manager constructor.
     *
     * @param string $baseUrl The base URL of the service server (e.g., http://file-tools:5001).
     */
    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Converts a given image to JPG format.
     *
     * @param string $imagePath The local file path of the image to convert.
     * @param int $jpegQuality The quality of the resulting JPG (0 to 100). Default is 75.
     * @param string $transparentColor The Hex color code to replace transparency with (e.g., '#FFFFFF'). Default is white.
     *
     * @return array An array containing the response body (binary image data or error) and the HTTP status code.
     */
    public function imageToJpg($imagePath, $jpegQuality = 75, $transparentColor = '#FFFFFF') {
        $fields = [
            'jpegQuality' => $jpegQuality,
            'transparentColor' => $transparentColor
        ];
        return $this->post('/image-to-jpg', $fields, ['image' => $imagePath]);
    }

    /**
     * Validates if a file is a valid PDF.
     *
     * @param string $pdfPath The local file path of the PDF to validate.
     *
     * @return array An array containing the response body (validation result) and the HTTP status code.
     */
    public function pdfIsValid($pdfPath) {
        return $this->post('/pdf-is-valid', [], ['pdf' => $pdfPath]);
    }

    /**
     * Counts the number of pages in a PDF file.
     *
     * @param string $pdfPath The local file path of the PDF.
     *
     * @return array An array containing the response body (page count) and the HTTP status code.
     */
    public function pdfCountPages($pdfPath) {
        return $this->post('/pdf-count-pages', [], ['pdf' => $pdfPath]);
    }

    /**
     * Extracts a specific page from a PDF and converts it to a JPG image.
     *
     * @param string $pdfPath The local file path of the source PDF.
     * @param int $page The page number to extract (1-based index).
     * @param int|null $width The desired width of the output image (optional).
     * @param int|null $height The desired height of the output image (optional).
     * @param int $jpegQuality The quality of the resulting JPG (0 to 100). Default is 75.
     *
     * @return array An array containing the response body (binary image data) and the HTTP status code.
     */
    public function pdfGetPageAsJpg($pdfPath, $page, $width = null, $height = null, $jpegQuality = 75) {
        $fields = [
            'page' => $page,
            'jpegQuality' => $jpegQuality
        ];
        if ($width) $fields['width'] = $width;
        if ($height) $fields['height'] = $height;
        return $this->post('/pdf-get-page-as-jpg', $fields, ['pdf' => $pdfPath]);
    }

    /**
     * Converts an HTML string to a binary PDF file.
     *
     * @param string $html The HTML content to convert.
     *
     * @return array An array containing the response body (binary PDF data) and the HTTP status code.
     */
    public function htmlToPdfBinary($html) {
        $fields = ['html' => $html];
        return $this->post('/html-to-pdf-binary', $fields);
    }

    /**
     * Converts an HTML string to a Base64 encoded PDF string.
     *
     * @param string $html The HTML content to convert.
     *
     * @return array An array containing the response body (Base64 string) and the HTTP status code.
     */
    public function htmlToPdfBase64($html) {
        $fields = ['html' => $html];
        return $this->post('/html-to-pdf-base64', $fields);
    }

    /**
     * Stores raw data (string or binary) in the cache.
     * Handles creating a temporary file internally to safely transfer binary data.
     *
     * @param string $key The unique key to identify the cached item.
     * @param string $valueRaw The raw content (e.g., string or PDF binary string) to store.
     * @param int|null $expire Expiration time in seconds (optional).
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheSet($key, $valueRaw, $expire = null) {
        // Create a temp file to store the binary data
        $tempPath = tempnam(sys_get_temp_dir(), 'tdft_');
        if ($tempPath === false) {
            throw new UnexpectedValueException("Could not create temporary file for cache transfer.");
        }

        // Write the raw data to the temp file
        file_put_contents($tempPath, $valueRaw);

        try {
            // Delegate to the file path method
            return $this->cacheSetFromFilePath($key, $tempPath, $expire);
        } finally {
            // Clean up: delete the temp file immediately
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Stores a file in the cache using a local file path.
     *
     * @param string $key The unique key to identify the cached item.
     * @param string $valuePath The local file path of the content to store in the cache.
     * @param int|null $expire Expiration time in seconds (optional).
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheSetFromFilePath($key, $valuePath, $expire = null) {
        $fields = ['key' => $key];
        if ($expire) {
            $fields['expire'] = $expire;
        }
        return $this->post('/cache-set', $fields, ['value' => $valuePath]);
    }

    /**
     * Retrieves a value from the cache by its key.
     *
     * @param string $key The unique key of the item to retrieve.
     *
     * @return array An array containing the response body (cached content) and the HTTP status code.
     */
    public function cacheGet($key) {
        $fields = ['key' => $key];
        return $this->post('/cache-get', $fields);
    }

    /**
     * Deletes a specific item from the cache.
     *
     * @param string $key The unique key of the item to delete.
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheDeleteKey($key) {
        $fields = ['key' => $key];
        return $this->post('/cache-delete-key', $fields);
    }

    /**
     * Deletes all items from the cache.
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheDeleteAll() {
        return $this->post('/cache-delete-all', []);
    }

    /**
     * Removes only the expired items from the cache.
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cachePrune() {
        return $this->post('/cache-prune', []);
    }

    /**
     * Performs a multipart/form-data POST request to the configured service.
     *
     * @param string $endpoint The API endpoint to call (e.g., '/image-to-jpg').
     * @param array $fields An associative array of non-file fields to send.
     * @param array $files An associative array where the key is the field name and the value is the local file path.
     *
     * @return array An array containing two elements: [0] => response body (string), [1] => HTTP status code (int).
     * @throws UnexpectedValueException If a cURL error occurs.
     */
    private function post($endpoint, $fields, $files = []) {
        $url = $this->baseUrl . $endpoint;
        $curl = curl_init();
        $data = $fields;
        foreach ($files as $key => $filePath) {
            $data[$key] = new CURLFile($filePath);
        }
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data,
        ]);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            throw new UnexpectedValueException('Curl error: ' . curl_error($curl));
        }
        curl_close($curl);
        return [$response, $httpCode];
    }
}