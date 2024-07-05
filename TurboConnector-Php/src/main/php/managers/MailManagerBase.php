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
use org\turbocommons\src\main\php\utils\ArrayUtils;


/**
 * Base class with common code for the classes that send emails
 */
abstract class MailManagerBase extends BaseStrictClass {


    /**
     * Defines the iso 8859 encoding for emails
     * @var string
     */
    const ISO_8859_1 = 'ISO_8859_1';


    /**
     * Defines the utf8 encoding for emails
     * @var string
     */
    const UTF8 = 'UTF8';


    /**
     * The charset that is defined when sending the email. MailPhpManager::UTF8 by default. It is VERY important to make sure that the subject and message parameters
     * are passed with the same encoding as the one defined here, otherwise strange characters will appear on the received email.
     * @var string
     */
    protected $_encoding = 'UTF8';


    /**
     * @see setHTML()
     * @var boolean
     */
    protected $_isHTML = false;


    /**
     * @see setSenderAddress()
     * @var boolean
     */
    protected $_senderAddress = '';


    /**
     * @see setReceiverAddresses()
     * @var boolean
     */
    protected $_receiverAddresses = [];


    /**
     * @see setSubject()
     * @var boolean
     */
    protected $_subject = '';


    /**
     * @see setBody()
     * @var boolean
     */
    protected $_body = '';


    /**
     * Stores the number of attached files to the current email
     */
    protected $_attachmentsLen = 0;


    /**
     * Structure with the filenames and binary data of the files to attach to the mail
     */
    protected $_attachments = [];


   /** Specify the encoding for the mail that will be sent
    *
    * @param string $encoding ::UTF8 or ::ISO_8859_1
    */
    public function setEncoding(string $encoding){

        $this->_encoding = $encoding;
    }


    /**
     * Enable or disable HTML mode for the email sending
     *
     * @param bool $isHTML True or false to enable or disable HTML processing on emails
     */
    public function setHTML(bool $isHTML){

        $this->_isHTML = $isHTML;
    }


    /**
     * Specify the address from were the mails will be sent
     *
     * @param string $sender A valid email address
     */
    public function setSenderAddress(string $sender){

        $this->_senderAddress = $sender;
    }


    /**
     * Specify a list of valid email addresses were the mail will be sent
     *
     * @param array $receiver A list of strings with mail addresses
     */
    public function setReceiverAddresses(array $receiver){

        $this->_receiverAddresses = $receiver;
    }


    /**
     * Specify the title for the mail that will be sent
     *
     * @param string $subject A text to write at the email title
     */
    public function setSubject(string $subject){

        $this->_subject = $subject;
    }


    /**
     * Specify the contents of the mail that will be sent.
     * Remember to enable HTML mode to send emails formatted as html.
     *
     * @param string $body The contents of the mail to send
     */
    public function setBody(string $body){

        $this->_body = $body;
    }


    /**
     * Attach a file from binary data to the email
     *
     * @param string $filename The name for the file when sent on the email
     * @param string $binary_data The file binary data to attach
     *
     * @return void
     */
    public function attachFile($filename, $fileData){

        $f = [];
        $f['filename'] = $filename;
        $f['binary'] = chunk_split(base64_encode($fileData));

        $this->_attachments[] = $f;
        $this->_attachmentsLen ++;
    }


    /**
     * Aux method to verify that email addresses are valid and email subject and body are not empty
     */
    protected function _sanitizeValues(){

        StringUtils::forceNonEmptyString($this->_senderAddress, 'senderAddress');
        ArrayUtils::forceNonEmptyArray($this->_receiverAddresses, 'receiverAddresses');

        // Sanitize the sender and receiver addresses to remove non email characters
        $this->_senderAddress = trim(filter_var($this->_senderAddress, FILTER_SANITIZE_EMAIL));

        for ($i = 0; $i < count($this->_receiverAddresses); $i++) {

            StringUtils::forceNonEmptyString($this->_receiverAddresses[$i], 'receiverAddress');
            $this->_receiverAddresses[$i] = trim(filter_var($this->_receiverAddresses[$i], FILTER_SANITIZE_EMAIL));
        }

        // TODO - validate email addresses are valid

        // Verify encoding is correct
        if ($this->_encoding !== self::UTF8 && $this->_encoding !== self::ISO_8859_1) {

            throw new UnexpectedValueException('Invalid encoding specified: '.$this->_encoding);
        }

        // Make sure no empty email is sent
        StringUtils::forceNonEmptyString($this->_subject.$this->_body, 'Email text');
    }


    /**
     * Execute the sending of an email with all the currently defined settings
     */
    abstract public function send();
}
