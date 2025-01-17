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
use org\turbocommons\src\main\php\model\BaseStrictClass;
use org\turbocommons\src\main\php\utils\StringUtils;


/**
 * A Synchronous class to manage and operate with ftp connections
 *
 * NOTICE that this class may not be available on all languages
 */
class FTPManager extends BaseStrictClass {


    /** Variable that contains the last error that happened (if any). */
    public $lastError = '';


    /** Define the type of data transfer for the ftp connection: FTP_BINARY (default and recommended) or FTP_ASCII */
    public $transferMode = FTP_BINARY;


    /** Variable that stores the FTP connection id so it can be used for all the operations */
    private $_connectionId = false;


    /**
     * Initialize a new connection to the specified remote FTP location
     *
     * @param string $userName The username for the ftp session we want to start
     * @param string $psw The password for the ftp user
     * @param string $host The FTP server address. This parameter shouldn't have any trailing slashes and shouldn't be prefixed with ftp://.
     * @param int $port This parameter specifies an alternate port to connect to. If it is omitted or set to zero, then the default FTP port, 21, will be used.
     * @param int $timeout This parameter specifies the timeout for all subsequent network operations. If omitted, the default value is 90 seconds. The timeout can be changed and queried at any time
     *
     * @throws UnexpectedValueException If connection cannot be established
     */
    public function __construct($userName, $psw, $host, $port = null, $timeout = 90){

        // Initialize and store the ftp connection
        $this->_connectionId = ftp_connect($host, $port, $timeout);

        if(!$this->_connectionId){

            throw new UnexpectedValueException('Ftp connection error: Verify host / port parameters and connection is available');
        }

        // login with username and password
        if(!ftp_login($this->_connectionId, $userName, $psw)){

            $this->closeConnection();

            throw new UnexpectedValueException('Ftp login error: Verify user credentials');
        }
    }


    // TODO: adaptar la funció createDirectory de FileSystemUtils (si es aplicable)



    /**
     * Gives the list of items that are stored on the specified ftp folder. It will give files and directories, and each element will be the item name, without the path to it.
     * The contents of any subfolder will not be listed. We must call this method for each child folder if we want to get it's list.
     *
     * @param string $path Full path to the directory we want to list. Example: 'folder/' by default the root ftp folder is used ('/')
     *
     * @return array The list of item names inside the specified path
     */
    public function getDirectoryList($path = '/'){

        $list = ftp_nlist($this->_connectionId, $path);

        if($list === false){

            throw new UnexpectedValueException('Could not get FTP directory list for '.$path);
        }

        // remove all the folder information from the received files
        $result = [];

        foreach($list as $l){

            $result[] = StringUtils::getPathElement($l);
        }

        return $result;

    }


    // TODO: adaptar la funcio getDirectorySize de FileSystemUtils (si es aplicable)


    // TODO: adaptar la funcio deleteDirectory de FileSystemUtils (si es aplicable)


    // TODO: adaptar la funcio isDirectoryEmpty de FileSystemUtils (si es aplicable)


    /**
     * Create a file to the specified ftp path and write the specified data to it.
     *
     * @param string $pathToFtpFile The full ftp path where the file will be stored, including the full file name
     * @param string $data Information to store on the file (a string, a block of bytes, etc...)
     *
     * @return bool Returns true on success or false on failure.
     */
    public function saveFile($pathToFtpFile, $data = ''){

        // To avoid creating a temporary file and then storing it on the ftp, we directly write to the ftp location via a text stream,
        // so no hard drive is used for the operation.
        $stream = fopen('data://text/plain,'.$data, 'r');

        if($stream === false){

            throw new UnexpectedValueException('Could not write to file: '.$pathToFtpFile);
        }

        $res = ftp_fput($this->_connectionId, $pathToFtpFile, $stream, FTP_BINARY);

        fclose($stream);

        return $res;
    }


    // TODO: adaptar la funció getFileSize de FileSystemUtils (si es aplicable)


    /**
     * Read and return an ftp file contents. Remember that big files may become problematic as we are only using memory to read the file data, the file is not stored no disk.
     *
     * @param string $ftpPath The file full ftp path including the file name
     *
     * @return string The file binary information
     */
    public function readFile($ftpPath){

        // Open a temporary file handle that works only on ram memory. This way we won't be using the hard drive.
        $stream = fopen('php://temp', 'r+');

        if($stream === false){

            throw new UnexpectedValueException('Could not create stream to php://temp: '.$ftpPath);
        }

        if($this->_connectionId === false){

            throw new UnexpectedValueException('FTP connection is NOT active! trying to read: '.$ftpPath);
        }

        if (ftp_fget($this->_connectionId, $stream, $ftpPath, $this->transferMode, 0)) {

            rewind($stream);

            $result = stream_get_contents($stream);

            fclose($stream);

            return $result;
        }

        fclose($stream);

        throw new UnexpectedValueException('Error reading FTP file: '.$ftpPath);
    }


    // TODO: adaptar la funció readFileBuffered de FileSystemUtils (si es aplicable)


    /**
     * Get a file from an ftp path and store it directly to the specified file path (If the specified file exists, will be overriden).
     *
     * @param string $ftpPath The file full ftp path including the file name
     * @param string $localPath The local path where the file will be stored, including the filename where the data will be saved.
     *
     * @return boolean True on sucess or false if the download fails
     */
    public function downloadFile($ftpPath, $localPath){

        $handle = fopen($localPath, 'w');

        if (ftp_fget($this->_connectionId, $handle, $ftpPath, $this->transferMode, 0)) {

            $result = true;

        } else {

            $result = false;
        }

        fclose($handle);

        return $result;
    }


    // TODO: adaptar la funci� deleteFile de FileSystemUtils (si es aplicable)


    /**
     * Terminate the current FTP connection
     *
     * @return void
     */
    public function closeConnection(){

        if(!$this->_connectionId){

            return;
        }

        ftp_close($this->_connectionId);

        $this->_connectionId = false;
    }


    /**
     * Ftp destructor will always close the current connection to avoid problems with opened connections.
     */
    public function __destruct() {

        if(!$this->_connectionId){

            return;
        }

        ftp_close($this->_connectionId);
    }
}
