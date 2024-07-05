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
use org\turbotesting\src\main\php\utils\AssertUtils;
use org\turboconnector\src\main\php\managers\MailPhpManager;
use org\turboconnector\src\main\php\managers\MailMicrosoft365Manager;


/**
 * test
 */
class MailMicrosoft365ManagerTest extends TestCase {


    /**
     * @see TestCase::setUp()
     *
     * @return void
     */
    protected function setUp(){

        $this->sut = new MailMicrosoft365Manager(__DIR__.'/../../resources/managers/mailMicrosoft365Manager/fake-composer-root/vendor');
    }


    /**
     * test
     *
     * @return void
     */
    public function testConstruct(){

        AssertUtils::throwsException(function() { new MailMicrosoft365Manager('invali path'); }, '/Could not find autoload.php file on invali path/');

        $this->assertTrue(true);
    }


    /**
     * test
     *
     * @return void
     */
    public function testSetCredentialsClientId(){

        $this->assertNull($this->sut->setCredentialsClientId('1234'));
    }


    /**
     * test
     *
     * @return void
     */
    public function testSetCredentialsClientSecret(){

        $this->assertNull($this->sut->setCredentialsClientSecret('1234'));
    }


    /**
     * test
     *
     * @return void
     */
    public function testSetCredentialsTenantId(){

        $this->assertNull($this->sut->setCredentialsTenantId('1234'));
    }


    /**
     * test
     *
     * @return void
     */
    public function testSend(){

        // Test empty values
        // Test wrong values
        // Test exceptions
        AssertUtils::throwsException(function() { $this->sut->send(); }, '/senderAddress must be a non empty string/');

        $this->sut->setSenderAddress('a@test.com');
        AssertUtils::throwsException(function() { $this->sut->send(); }, '/receiverAddresses must be a non empty array/');

        $this->sut->setReceiverAddresses(['dest@test.com']);
        AssertUtils::throwsException(function() { $this->sut->send(); }, '/Email text must be a non empty string/');

        $this->sut->setEncoding('hello');
        AssertUtils::throwsException(function() { $this->sut->send(); }, '/Invalid encoding specified: hello/');

        $this->sut->setEncoding(MailMicrosoft365Manager::UTF8);
        $this->sut->setSubject('hola');
        AssertUtils::throwsException(function() { $this->sut->send(); }, "/Class .GuzzleHttp.Client. not found/");

        // TODO - MailPhpManager must validate receiver is a valid email address

        // Test ok values
        // TODO
        $this->assertTrue(true);
    }
}
