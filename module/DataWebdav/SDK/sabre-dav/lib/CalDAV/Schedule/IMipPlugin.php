<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\DAV;
use Sabre\VObject\ITip;


class IMipPlugin extends DAV\ServerPlugin {

    
    protected $senderEmail;

    
    protected $itipMessage;

    
    function __construct($senderEmail) {

        $this->senderEmail = $senderEmail;

    }

    
    function initialize(DAV\Server $server) {

        $server->on('schedule', [$this, 'schedule'], 120);

    }

    
    function getPluginName() {

        return 'imip';

    }

    
    function schedule(ITip\Message $iTipMessage) {

                        if (!$iTipMessage->significantChange) {
            if (!$iTipMessage->scheduleStatus) {
                $iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
            }
            return;
        }

        $summary = $iTipMessage->message->VEVENT->SUMMARY;

        if (parse_url($iTipMessage->sender, PHP_URL_SCHEME) !== 'mailto')
            return;

        if (parse_url($iTipMessage->recipient, PHP_URL_SCHEME) !== 'mailto')
            return;

        $sender = substr($iTipMessage->sender, 7);
        $recipient = substr($iTipMessage->recipient, 7);

        if ($iTipMessage->senderName) {
            $sender = $iTipMessage->senderName . ' <' . $sender . '>';
        }
        if ($iTipMessage->recipientName) {
            $recipient = $iTipMessage->recipientName . ' <' . $recipient . '>';
        }

        $subject = 'SabreDAV iTIP message';
        switch (strtoupper($iTipMessage->method)) {
            case 'REPLY' :
                $subject = 'Re: ' . $summary;
                break;
            case 'REQUEST' :
                $subject = $summary;
                break;
            case 'CANCEL' :
                $subject = 'Cancelled: ' . $summary;
                break;
        }

        $headers = [
            'Reply-To: ' . $sender,
            'From: ' . $this->senderEmail,
            'Content-Type: text/calendar; charset=UTF-8; method=' . $iTipMessage->method,
        ];
        if (DAV\Server::$exposeVersion) {
            $headers[] = 'X-Sabre-Version: ' . DAV\Version::VERSION;
        }
        $this->mail(
            $recipient,
            $subject,
            $iTipMessage->message->serialize(),
            $headers
        );
        $iTipMessage->scheduleStatus = '1.1; Scheduling message is sent via iMip';

    }

        
    
    protected function mail($to, $subject, $body, array $headers) {

        mail($to, $subject, $body, implode("\r\n", $headers));

    }

    
    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Email delivery (rfc6047) for CalDAV scheduling',
            'link'        => 'http://sabre.io/dav/scheduling/',
        ];

    }

}
