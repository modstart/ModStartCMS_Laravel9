<?php

namespace Sabre\VObject\ITip;


class Message
{
    
    public $uid;

    
    public $component;

    
    public $method;

    
    public $sequence;

    
    public $sender;

    
    public $senderName;

    
    public $recipient;

    
    public $recipientName;

    
    public $scheduleStatus;

    
    public $message;

    
    public $significantChange = true;

    
    public function getScheduleStatus()
    {
        if (!$this->scheduleStatus) {
            return false;
        } else {
            list($scheduleStatus) = explode(';', $this->scheduleStatus);

            return $scheduleStatus;
        }
    }
}
