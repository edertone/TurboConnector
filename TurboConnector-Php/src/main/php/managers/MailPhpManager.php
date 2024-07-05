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


/**
 * Class that acts as a basic interface to email sending operations.
 * This uses the PHP mail() method internally, which is not the best solution for reliable email sending.
 * This can be convenient for an easy way to send emails, but for more robust solutions we should use SMTP or API
 * calls for trusted mail servers
 */
class MailPhpManager extends MailManagerBase {


    public function send(){

        $this->_sanitizeValues();

        // Define the character encoding for the subject and body
        if ($this->_encoding === MailPhpManager::UTF8) {

            $encoding = 'charset="UTF8"';

        }else{

            $encoding = 'charset="ISO_8859_1"';
        }

        // Definition for the headers - using \r makes thunderbird fail!!
        $headers = "MIME-Version: 1.0\n";
        $headers .= 'From: '.$this->_senderAddress."\n";
        $headers .= 'Return-Path: '.$this->_senderAddress."\n";
        $headers .= 'Reply-To: '.$this->_senderAddress."\n";

        // Check if the mail is in html format
        if($this->_isHTML){

            $emailContentType = 'Content-type: text/html; '.$encoding;

        }else{

            $emailContentType = 'Content-Type: text/plain; '.$encoding;
        }

        // Check if there are attached files
        if($this->_attachmentsLen > 0){

            //create a boundary string. It must be unique so we use the MD5 algorithm to generate a random hash
            $mimeBoundary = '==Multipart_Boundary_x'.md5(time()).'x';

            // Output the multipart mixed headers
            $headers .= 'Content-Type: multipart/mixed; boundary="{'.$mimeBoundary."}\"\n";

            // output the text part for the e-mail
            $emailMessage  = "This is a multi-part message in MIME format.\n\n";
            $emailMessage .= '--{'.$mimeBoundary."}\n";
            $emailMessage .= $emailContentType."\n";
            $emailMessage .= "Content-Transfer-Encoding: 7bit\n\n";
            $emailMessage .= $this->_body."\n\n";

            // Output all the different attached files
            for($i=0; $i<$this->_attachmentsLen; $i++){

                $emailMessage .= '--{'.$mimeBoundary."}\n";
                $emailMessage .= "Content-Type: application/octet-stream;\n";
                $emailMessage .= ' name="'.$this->_attachments[$i]['filename']."\"\n";
                $emailMessage .= "Content-Disposition: attachment;\n";
                $emailMessage .= ' filename="'.$this->_attachments[$i]['filename']."\"\n";
                $emailMessage .= "Content-Transfer-Encoding: base64\n\n";
                $emailMessage .= $this->_attachments[$i]['binary']."\n\n";
            }

        }else {

            $emailMessage = $this->_body;
            $headers .= $emailContentType."\n";
        }

        $result = true;

        foreach($this->_receiverAddresses as $receiver){

            $result = $result & mail($receiver, $this->_subject, $emailMessage, $headers);

            if(!$result){

                throw new UnexpectedValueException('Could not queue mail "'.$this->_subject.'" to: '.$receiver);
            }
        }

        if(!$result){

            throw new UnexpectedValueException('Could not queue mail, unknown error');
        }
    }
}
