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
     * Converts raw image data to JPG format.
     *
     * @param string $rawImageData The binary data of the image.
     * @param int $jpegQuality The quality of the resulting JPG (0 to 100). Default is 75.
     * @param string $transparentColor The Hex color code to replace transparency with (e.g., '#FFFFFF'). Default is white.
     *
     * @return array An array containing the response body (binary image data or error) and the HTTP status code.
     */
    public function imageToJpg($rawImageData, $jpegQuality = 75, $transparentColor = '#FFFFFF') {
        return $this->processWithTempFile($rawImageData, function($tempPath) use ($jpegQuality, $transparentColor) {
            return $this->imageToJpgFromFilePath($tempPath, $jpegQuality, $transparentColor);
        });
    }

    /**
     * Converts a given image file to JPG format.
     *
     * @param string $imagePath The local file path of the image to convert.
     * @param int $jpegQuality The quality of the resulting JPG (0 to 100). Default is 75.
     * @param string $transparentColor The Hex color code to replace transparency with (e.g., '#FFFFFF'). Default is white.
     *
     * @return array An array containing the response body (binary image data or error) and the HTTP status code.
     */
    public function imageToJpgFromFilePath($imagePath, $jpegQuality = 75, $transparentColor = '#FFFFFF') {
        $fields = [
            'jpegQuality' => $jpegQuality,
            'transparentColor' => $transparentColor
        ];
        return $this->post('/image-to-jpg', $fields, ['image' => $imagePath]);
    }

    /**
     * Validates if raw data is a valid PDF.
     *
     * @param string $rawPdfData The binary PDF data.
     *
     * @return array An array containing the response body (validation result) and the HTTP status code.
     */
    public function pdfIsValid($rawPdfData) {
        return $this->processWithTempFile($rawPdfData, function($tempPath) {
            return $this->pdfIsValidFromFilePath($tempPath);
        });
    }

    /**
     * Validates if a file is a valid PDF.
     *
     * @param string $pdfPath The local file path of the PDF to validate.
     *
     * @return array An array containing the response body (validation result) and the HTTP status code.
     */
    public function pdfIsValidFromFilePath($pdfPath) {
        return $this->post('/pdf-is-valid', [], ['pdf' => $pdfPath]);
    }

    /**
     * Counts the number of pages in a raw PDF string.
     *
     * @param string $rawPdfData The binary PDF data.
     *
     * @return array An array containing the response body (page count) and the HTTP status code.
     */
    public function pdfCountPages($rawPdfData) {
        return $this->processWithTempFile($rawPdfData, function($tempPath) {
            return $this->pdfCountPagesFromFilePath($tempPath);
        });
    }

    /**
     * Counts the number of pages in a PDF file.
     *
     * @param string $pdfPath The local file path of the PDF.
     *
     * @return array An array containing the response body (page count) and the HTTP status code.
     */
    public function pdfCountPagesFromFilePath($pdfPath) {
        return $this->post('/pdf-count-pages', [], ['pdf' => $pdfPath]);
    }

    /**
     * Extracts a specific page from raw PDF data and converts it to a JPG image.
     *
     * @param string $rawPdfData The binary PDF data.
     * @param int $page The page number to extract (1-based index).
     * @param int|null $width The desired width of the output image (optional).
     * @param int|null $height The desired height of the output image (optional).
     * @param int $jpegQuality The quality of the resulting JPG (0 to 100). Default is 75.
     *
     * @return array An array containing the response body (binary image data) and the HTTP status code.
     */
    public function pdfGetPageAsJpg($rawPdfData, $page, $width = null, $height = null, $jpegQuality = 75) {
        return $this->processWithTempFile($rawPdfData, function($tempPath) use ($page, $width, $height, $jpegQuality) {
            return $this->pdfGetPageAsJpgFromFilePath($tempPath, $page, $width, $height, $jpegQuality);
        });
    }

    /**
     * Extracts a specific page from a PDF file and converts it to a JPG image.
     *
     * @param string $pdfPath The local file path of the source PDF.
     * @param int $page The page number to extract (1-based index).
     * @param int|null $width The desired width of the output image (optional).
     * @param int|null $height The desired height of the output image (optional).
     * @param int $jpegQuality The quality of the resulting JPG (0 to 100). Default is 75.
     *
     * @return array An array containing the response body (binary image data) and the HTTP status code.
     */
    public function pdfGetPageAsJpgFromFilePath($pdfPath, $page, $width = null, $height = null, $jpegQuality = 75) {
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
     *
     * @param string $namespace The namespace for the cache item.
     * @param string $key The unique key to identify the cached item within the namespace.
     * @param string $valueRaw The raw content (e.g., string or PDF binary string) to store.
     * @param int|null $expire Expiration time in seconds (optional). If set to null, the item will not expire.
     *
     * @return array An array containing the response body at index 0 and the HTTP status code at index 1.
     */
    public function cacheSet($namespace, $key, $valueRaw, $expire = null) {
        return $this->processWithTempFile($valueRaw, function($tempPath) use ($namespace, $key, $expire) {
            return $this->cacheSetFromFilePath($namespace, $key, $tempPath, $expire);
        });
    }

    /**
     * Stores a file in the cache using a local file path.
     *
     * @param string $namespace The namespace for the cache item.
     * @param string $key The unique key to identify the cached item within the namespace.
     * @param string $valuePath The local file path of the content to store in the cache.
     * @param int|null $expire Expiration time in seconds (optional). If set to null, the item will not expire.
     *
     * @return array An array containing the response body at index 0 and the HTTP status code at index 1.
     */
    public function cacheSetFromFilePath($namespace, $key, $valuePath, $expire = null) {
        $fields = [
            'namespace' => $namespace,
            'key' => $key
        ];
        if ($expire) {
            $fields['expire'] = $expire;
        }
        return $this->post('/cache-set', $fields, ['value' => $valuePath]);
    }

    /**
     * Retrieves a value from the cache by its namespace and key.
     *
     * @param string $namespace The namespace of the item.
     * @param string $key The unique key of the item to retrieve.
     *
     * @return array An array containing the cached content at index 0 and the HTTP status code at index 1 (200 means item found ok).
     */
    public function cacheGet($namespace, $key) {
        $fields = [
            'namespace' => $namespace,
            'key' => $key
        ];
        return $this->post('/cache-get', $fields);
    }

    /**
     * Retrieves a value from the cache by its namespace and key and streams the output directly.
     *
     * @param string $namespace The namespace of the item.
     * @param string $key The unique key of the item to retrieve.
     *
     * @return int The HTTP status code of the response (200 means item found ok).
     */
    public function cacheGetStreamOutput($namespace, $key) {
        $url = $this->baseUrl . '/cache-get';
        $curl = curl_init();

        $fields = [
            'namespace' => $namespace,
            'key' => $key
        ];

        // Flag to determine if we should output the body
        $isError = false;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,

            // 1. Intercept Headers to check for non-200 status
            CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$isError) {
                $len = strlen($header);
                // Check if the header line is an HTTP status code indicating error (e.g., HTTP/1.1 404 or 500)
                if (preg_match('/^HTTP\/[\d\.]+\s+([45]\d\d)/', $header, $matches)) {
                    $isError = true;
                }
                return $len;
            },

            // 2. Only echo data if we haven't detected an error
            CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$isError) {
                if (!$isError) {
                    echo $data;
                }
                return strlen($data);
            }
            ]);

        curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $httpCode;
    }

    /**
     * Deletes a specific item from the cache.
     *
     * @param string $namespace The namespace of the item.
     * @param string $key The unique key of the item to delete.
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheDeleteKey($namespace, $key) {
        $fields = [
            'namespace' => $namespace,
            'key' => $key
        ];
        return $this->post('/cache-delete-key', $fields);
    }

    /**
     * Deletes all items within a specific namespace.
     *
     * @param string $namespace The namespace to clear.
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheClearNamespace($namespace) {
        $fields = ['namespace' => $namespace];
        return $this->post('/cache-clear-namespace', $fields);
    }

    /**
     * Deletes all items from the cache (all namespaces).
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cacheDeleteAll() {
        return $this->post('/cache-delete-all', []);
    }

    /**
     * Removes only the expired items from the cache (across all namespaces).
     *
     * @return array An array containing the response body and the HTTP status code.
     */
    public function cachePrune() {
        return $this->post('/cache-prune', []);
    }

    /**
     * Helper method to handle temporary file creation, execution of a callback, and cleanup.
     *
     * @param string $rawData The raw data to write to the temp file.
     * @param callable $callback The function to execute. Receives the temp file path as an argument.
     *
     * @return mixed The result of the callback.
     * @throws UnexpectedValueException If the temp file cannot be created.
     */
    private function processWithTempFile($rawData, callable $callback) {
        if (($tempPath = tempnam(sys_get_temp_dir(), 'tdft_')) === false) {
            throw new UnexpectedValueException("Could not create temporary file for data transfer.");
        }

        file_put_contents($tempPath, $rawData);

        try {
            return $callback($tempPath);
        } finally {
            // Clean up: delete the temp file immediately
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
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