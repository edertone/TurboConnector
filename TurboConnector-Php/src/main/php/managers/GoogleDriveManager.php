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


use Throwable;
use stdClass;
use Google\Client;
use Google\Service\Drive;
use UnexpectedValueException;
use org\turbocommons\src\main\php\utils\StringUtils;
use org\turbodepot\src\main\php\managers\CacheManager;


/**
 * GoogleDriveManager class
 */
class GoogleDriveManager {


    /**
     * Contains an instance of the google api client class
     * @var Client
     */
    private $_client = null;


    /**
     * Contains an instance of the google drive service
     * @var Drive
     */
    private $_service = null;


    /**
     * A cache manager instance that is used by this class
     * @var CacheManager
     */
    private $_cacheManager = null;


    /**
     * Tells if the manager has correctly authenticated to the google drive api
     * @var boolean
     */
    private $_isAuthenticated = false;


    /**
     * Stores the path to the service account credentials for authentication
     * @var string
     */
    private $_serviceAccountCredentials = '';


    /**
     * See getter for docs
     *
     * @see GoogleDriveManager::enableCache
     * @var int
     */
    private $_listsTimeToLive = -1;


    /**
     * See getter for docs
     *
     * @see GoogleDriveManager::enableCache
     * @var int
     */
    private $_filesTimeToLive = -1;


    /**
     * This class is an abstraction of the Google drive api. It allows us to interact with a google drive account like we interact
     * with a standard file system.
     *
     * It requires the composer "google/apiclient" depedency
     *
     * How to use this class:
     *
     * - Before creating an instance of GoogleDriveManager, the google-api-php-client library must be downloaded and deployed
     *   into our project with composer.
     *
     * - We must login on the google api console and make sure we have a service account and a service account key.
     *   Service accounts are a special type of google user accounts that represent non human applications. The service account key is a file containing
     *   the account credentials that allow the related application to login into the google api. We must save this file so it is accessible by our project.
     *
     * - We must share with the service account all the google drive files or folders we want to access with GoogleDriveManager. Google drive resources are
     *   shared with service accounts exactly the same way as with normal users. Service accounts are identified with an email, exactly like normal user accounts.
     *   We can use that email to grant access to google drive resources.
     *
     * @throws UnexpectedValueException
     *
     * @param string $vendorRoot A full file system path to the root of the composer vendor folder were the google-api-php-client library is installed.
     *               It must be accessible by our project and contain a valid autoload.php file
     */
    public function __construct(string $vendorRoot){

        if(!is_file($vendorRoot.'/autoload.php')){

            throw new UnexpectedValueException('Specified vendorRoot folder is not valid. Could not find autoload.php file on '.$vendorRoot);
        }

        require_once $vendorRoot.'/autoload.php';
    }


    /**
     * Enables cache to store google drive files locally for speed improvements
     *
     * @param string $rootPath Full file system path to the root of a folder where all the cached data will be stored.
     * @param string $zoneName To isolate all the cached data from any other elements that may exist on the cache folder, we must define a cache zone name.
     *        we can leave here the default name or use any other we want.
     * @param int $listsTimeToLive Defines the number of seconds after which the operations related to listing files and folder cache data will be deleted.
     *        Set it to 0 to for an infinite time. (1 hour = 3600 seconds, 1 day = 86400 seconds, 1 month = 2592000, 1 year = 31536000)
     * @param int $filesTimeToLive Defines the number of seconds after which the operations related to getting files content cache data will be deleted.
     *        Set it to 0 to for an infinite time. (1 hour = 3600 seconds, 1 day = 86400 seconds, 1 month = 2592000, 1 year = 31536000)
     *
     * @throws UnexpectedValueException
     *
     * @see CacheManager::__construct
     *
     * @return void
     */
    public function enableCache($rootPath, $zoneName = 'google-drive', $listsTimeToLive = 0, $filesTimeToLive = 0){

        if($this->_cacheManager !== null){

            throw new UnexpectedValueException('Google drive cache can only be enabled once');
        }

        $this->_cacheManager = new CacheManager($rootPath, $zoneName);

        $this->_listsTimeToLive = $listsTimeToLive;
        $this->_filesTimeToLive = $filesTimeToLive;
        $this->_cacheManager->setSectionTimeToLive('getDirectoryList', $listsTimeToLive);
        $this->_cacheManager->setSectionTimeToLive('getFileLocalPath', $filesTimeToLive);
    }


    /**
     * Specifies that the google drive manager will authenticate to google drive api with a service account credentials
     *
     * @param string $serviceAccountCredentials A full file system path to the json file that contains the service account credentials that will be used to
     *        authenticate with the google drive api (See this class constructor for more info on service accounts).
     *
     * @throws UnexpectedValueException
     */
    public function setServiceAccountCredentials($serviceAccountCredentials){

        if(!is_file($serviceAccountCredentials)){

            throw new UnexpectedValueException('Could not find serviceAccountCredentials file. Make sure you download the generated service account key json file and specify it here');
        }

        $this->_serviceAccountCredentials = $serviceAccountCredentials;
    }


    /**
     * Auxiliary method to perform google drive authentication on demand.
     */
    private function authenticate(){

        if($this->_isAuthenticated){

            return;
        }

        // Check if authentication must be performed with service account credentials
        if($this->_serviceAccountCredentials !== ''){

            $this->_client = new Client();
            $this->_client->setScopes([Drive::DRIVE]);
            $this->_client->setAuthConfig($this->_serviceAccountCredentials);
            $this->_client->useApplicationDefaultCredentials();

            $this->_service = new Drive($this->_client);

            $this->_isAuthenticated = true;
        }

        if(!$this->_isAuthenticated){

            throw new UnexpectedValueException('Could not perform google drive authentication');
        }
    }


    /**
     * Gives the number of cache seconds that have been defined for the lis operations
     *
     * @see GoogleDriveManager::enableCache
     *
     * @return int The number of defined seconds or -1 if cache is not enabled
     */
    public function getListsTimeToLive(){

        return $this->_listsTimeToLive;
    }


    /**
     * Gives the number of cache seconds that have been defined for the file read operations
     *
     * @see GoogleDriveManager::enableCache
     *
     * @return int The number of defined seconds or -1 if cache is not enabled
     */
    public function getFilesTimeToLive(){

        return $this->_filesTimeToLive;
    }


    /**
     * Get a list with all the items under the specified google drive folder.
     *
     * @param string $parentId The google drive identifier for the directory that contains the elements we want to list. If the parent is empty
     *        the root list will be retrieved. Notice that the root elements will be those that are SPECIFICALLY shared with this service account
     *        at the google drive configuration.
     *
     * @return \stdClass[] An array with one object for each one of the child elements found. Each object will have three
     *         properties: id, with the id of the child element, isDirectory which will be true if the child element is a directory,
     *         and name which will contain the child element name
     *
     */
    public function getDirectoryList($parentId = ''){

        if($this->_cacheManager !== null &&
           ($cachedList = $this->_cacheManager->get(__FUNCTION__, $parentId)) !== null){

            return json_decode($cachedList);
        }

        // Authentication is performed here to improve response time when data is cached
        $this->authenticate();

        $result = [];
        $pageToken = null;

        do {

            // Request the list to the google drive API
            $query = StringUtils::isEmpty($parentId) ? 'sharedWithMe' : "'".$parentId."' in parents";

            $parameters = [
                'q' => $query,
                'pageSize' => 1000,
                'fields' => 'nextPageToken, files(id, name, mimeType, owners)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
                "orderBy" => "name"
            ];

            if($pageToken){

                $parameters['pageToken'] = $pageToken;
            }

            $itemList = $this->_service->files->listFiles($parameters);

            // Fill the result array with the received elements
            foreach ($itemList->getFiles() as $item){

                $itemStd = new stdClass();
                $itemStd->id = $item->getId();
                $itemStd->isDirectory = $item->getMimeType() === 'application/vnd.google-apps.folder';
                $itemStd->name = $item->getName();

                $result[] = $itemStd;
            }

            $pageToken = $itemList->getNextPageToken();

        } while ($pageToken != null);

        // Save all the received list to cache
        if($this->_cacheManager !== null){

            $this->_cacheManager->save(__FUNCTION__, $parentId, json_encode($result));
        }

        return $result;
    }


    /**
     * Obtain the real name of the file from the specified google drive id.
     *
     * @param string $id The google drive id for the file we want to inspect
     *
     * @return string The real name of the file that belongs to the specified id
     */
    public function getFileName($id){

        // Check if the filename is already cached
        if($this->_cacheManager !== null &&
           ($cachedName = $this->_cacheManager->get(__FUNCTION__, $id)) !== null){

            return $cachedName;
        }

        // Authentication is performed here to improve response time when data is cached
        $this->authenticate();

        try {

            // Request the file metadata to google drive api
            $file = $this->_service->files->get($id, array('fields' => 'name'));

            return $file->getName();

        } catch (Throwable $e) {

            throw new UnexpectedValueException('Could not retrieve file name for id '.$id, 0, $e);
        }
    }


    /**
     * This method will return the local filesystem path where we can find the specified google drive file.
     * Before giving us this path, the method will download all the file data locally, and once all the file is
     * stored in our machine, the path to it will be provided.
     *
     * Cache must be enabled for this method to work, cause the local file copy is stored on the cache folder.
     *
     * @param string $id The google drive id for the file we want to retrieve
     *
     * @return string The full file system path to the file we want to get. Notice the path will contain the file id on google drive, not
     *         the real file name, so we will have to use the getFileName method if we want the real file name.
     */
    public function getFileLocalPath($id){

        // This method requires that local cache is enabled
        if($this->_cacheManager === null){

            throw new UnexpectedValueException('This method requires that local cache is enabled for this GoogleDriveManager instance');
        }

        // Check if the file is cached
        if(($filePath = $this->_cacheManager->getPath(__FUNCTION__, $id)) !== null){

            return $filePath;
        }

        // Authentication is performed here to improve response time when data is cached
        $this->authenticate();

        // Create a new cache entry to store all the file data
        $this->_cacheManager->save(__FUNCTION__, $id, '');
        $cachePath = $this->_cacheManager->getPath(__FUNCTION__, $id);

        try {

            // Request the file to google drive api
            $file = $this->_service->files->get($id, array('alt' => 'media'));
            $fileContents = $file->getBody();

            // Save all the file data into the cache entry
            $outHandle = fopen($cachePath, "w+");

            // Until we have reached the EOF, read 1024 bytes at a time and write to the output file handle.
            while (!$fileContents->eof()) {

                fwrite($outHandle, $fileContents->read(1024));
            }

        } catch (Throwable $e) {

            $this->_cacheManager->clearId(__FUNCTION__, $id);

            throw $e;
        }

        fclose($outHandle);

        return $cachePath;
    }


    /**
     * Gives the name for the cache zone that is currently being used
     *
     * @throws UnexpectedValueException
     */
    public function getCacheZoneName(){

        if($this->_cacheManager === null){

            throw new UnexpectedValueException('Cache is not enabled for this instance');
        }

        return $this->_cacheManager->getZoneName();
    }


    /**
     * Force a removal for all the locally cached google drive requests and files
     */
    public function clearCache(){

        if($this->_cacheManager === null){

            throw new UnexpectedValueException('Cache is not enabled for this instance');
        }

        return $this->_cacheManager->clearZone();
    }
}
