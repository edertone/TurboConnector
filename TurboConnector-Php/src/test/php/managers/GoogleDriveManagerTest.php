<?php

/**
 * TurboConnector is a general purpose library to facilitate connection to remote locations and external APIS.
 *
 * Website : -> https://turboframework.org/en/libs/turboconnector
 * License : -> Licensed under the Apache License, Version 2.0. You may not use this file except in compliance with the License.
 * License Url : -> http://www.apache.org/licenses/LICENSE-2.0
 * CopyRight : -> Copyright 2024 Edertone Advanded Solutions. http://www.edertone.com
 */

namespace org\turbodepot\src\test\php\managers;


use PHPUnit\Framework\TestCase;
use org\turboconnector\src\main\php\managers\GoogleDriveManager;
use org\turbotesting\src\main\php\utils\AssertUtils;
use org\turbodepot\src\main\php\managers\FilesManager;


/**
 * test
 */
class GoogleDriveManagerTest extends TestCase {


    /**
     * @see TestCase::setUp()
     *
     * @return void
     */
    protected function setUp(){

        $this->sut = new GoogleDriveManager(__DIR__.'/../../resources/managers/mailMicrosoft365Manager/fake-composer-root/vendor');
    }


    /**
     * test
     *
     * @return void
     */
    public function testConstruct(){

        AssertUtils::throwsException(function() { new GoogleDriveManager('invali path'); }, '/Could not find autoload.php file on invali path/');

        $this->assertTrue(true);
    }


    /**
     * test
     * @return void
     */
    public function testGetFilesTimeToLive(){

        $this->assertSame(-1, $this->sut->getFilesTimeToLive());
    }


    /**
     * test
     * @return void
     */
    public function testGetListsTimeToLive(){

        $this->assertSame(-1, $this->sut->getListsTimeToLive());
    }


    /**
     * test
     *
     * @return void
     */
    public function testEnableCache(){

        $tempDir = (new FilesManager())->createTempDirectory('');

        $this->assertSame(-1, $this->sut->getFilesTimeToLive());
        $this->assertSame(-1, $this->sut->getListsTimeToLive());
        $this->assertNull($this->sut->enableCache($tempDir));
        $this->assertSame(0, $this->sut->getFilesTimeToLive());
        $this->assertSame(0, $this->sut->getListsTimeToLive());

        AssertUtils::throwsException(function() use ($tempDir) { $this->sut->enableCache($tempDir); }, '/Google drive cache can only be enabled once/');
    }


    /**
     * test
     * @return void
     */
    public function testGetDirectoryList(){

        AssertUtils::throwsException(function() { $this->sut->getDirectoryList(''); }, '/Could not perform google drive authentication/');

        $this->assertTrue(true);
    }


    /**
     * test
     *
     * @return void
     */
    public function testGetCacheZoneName(){

        $tempDir = (new FilesManager())->createTempDirectory('');

        AssertUtils::throwsException(function() { $this->sut->getCacheZoneName(); }, '/Cache is not enabled for this instance/');

        $this->assertNull($this->sut->enableCache($tempDir, 'custom-cache-name'));
        $this->assertSame('custom-cache-name', $this->sut->getCacheZoneName());
    }


    /**
     * test
     *
     * @return void
     */
    public function testClearCache(){

        $tempDir = (new FilesManager())->createTempDirectory('');

        AssertUtils::throwsException(function() { $this->sut->clearCache(); }, '/Cache is not enabled for this instance/');

        $this->assertNull($this->sut->enableCache($tempDir));
        $this->asserttrue($this->sut->clearCache());
    }
}
