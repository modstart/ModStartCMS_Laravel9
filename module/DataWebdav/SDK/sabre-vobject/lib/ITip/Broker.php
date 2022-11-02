<?php

namespace Sabre\VObject\ITip;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\EventIterator;


class Broker
{
    
    public $scheduleAgentServerRules = true;

    
    public $significantChangeProperties = [
        'DTSTART',
        'DTEND',
        'DURATION',
        'DUE',
        'RRULE',
        'RDATE',
        'EXDATE',
        'STATUS',
    ];

    
    public function processMessage(Message $itipMessage, VCalendar $existingObject = null)
    {
                if ('VEVENT' !== $itipMessage->component) {
            return false;
        }

        switch ($itipMessage->method) {
            case 'REQUEST':
                return $this->processMessageRequest($itipMessage, $existingObject);

            case 'CANCEL':
                return $this->processMessageCancel($itipMessage, $existingObject);

            case 'REPLY':
                return $this->processMessageReply($itipMessage, $existingObject);

            default:
                                return;
        }

        return $existingObject;
    }

    
    public function parseEvent($calendar = null, $userHref, $oldCalendar = null)
    {
        if ($oldCalendar) {
            if (is_string($oldCalendar)) {
                $oldCalendar = Reader::read($oldCalendar);
            }
            if (!isset($oldCalendar->VEVENT)) {
                                return [];
            }

            $oldEventInfo = $this->parseEventInfo($oldCalendar);
        } else {
            $oldEventInfo = [
                'organizer' => null,
                'significantChangeHash' => '',
                'attendees' => [],
            ];
        }

        $userHref = (array) $userHref;

        if (!is_null($calendar)) {
            if (is_string($calendar)) {
                $calendar = Reader::read($calendar);
            }
            if (!isset($calendar->VEVENT)) {
                                return [];
            }
            $eventInfo = $this->parseEventInfo($calendar);
            if (!$eventInfo['attendees'] && !$oldEventInfo['attendees']) {
                                                return [];
            }
            if (!$eventInfo['organizer'] && !$oldEventInfo['organizer']) {
                                return [];
            }

            $baseCalendar = $calendar;

                                                if (!$eventInfo['organizer'] && $oldEventInfo['organizer']) {
                $eventInfo['organizer'] = $oldEventInfo['organizer'];
                $eventInfo['organizerName'] = $oldEventInfo['organizerName'];
            }
        } else {
                                    if (!$oldCalendar) {
                                return [];
            }

            $eventInfo = $oldEventInfo;

            if (in_array($eventInfo['organizer'], $userHref)) {
                                $eventInfo['attendees'] = [];
                                                ++$eventInfo['sequence'];
            } else {
                                foreach ($eventInfo['attendees'] as $key => $attendee) {
                    if (in_array($attendee['href'], $userHref)) {
                        $eventInfo['attendees'][$key]['instances'] = ['master' => ['id' => 'master', 'partstat' => 'DECLINED'],
                        ];
                    }
                }
            }
            $baseCalendar = $oldCalendar;
        }

        if (in_array($eventInfo['organizer'], $userHref)) {
            return $this->parseEventForOrganizer($baseCalendar, $eventInfo, $oldEventInfo);
        } elseif ($oldCalendar) {
                                                foreach ($eventInfo['attendees'] as $attendee) {
                if (in_array($attendee['href'], $userHref)) {
                    return $this->parseEventForAttendee($baseCalendar, $eventInfo, $oldEventInfo, $attendee['href']);
                }
            }
        }

        return [];
    }

    
    protected function processMessageRequest(Message $itipMessage, VCalendar $existingObject = null)
    {
        if (!$existingObject) {
                                    $existingObject = new VCalendar();
            foreach ($itipMessage->message->getComponents() as $component) {
                $existingObject->add(clone $component);
            }
        } else {
                                                foreach ($existingObject->getComponents() as $component) {
                $existingObject->remove($component);
            }
            foreach ($itipMessage->message->getComponents() as $component) {
                $existingObject->add(clone $component);
            }
        }

        return $existingObject;
    }

    
    protected function processMessageCancel(Message $itipMessage, VCalendar $existingObject = null)
    {
        if (!$existingObject) {
                                } else {
            foreach ($existingObject->VEVENT as $vevent) {
                $vevent->STATUS = 'CANCELLED';
                $vevent->SEQUENCE = $itipMessage->sequence;
            }
        }

        return $existingObject;
    }

    
    protected function processMessageReply(Message $itipMessage, VCalendar $existingObject = null)
    {
                        if (!$existingObject) {
            return;
        }
        $instances = [];
        $requestStatus = '2.0';

                foreach ($itipMessage->message->VEVENT as $vevent) {
            $recurId = isset($vevent->{'RECURRENCE-ID'}) ? $vevent->{'RECURRENCE-ID'}->getValue() : 'master';
            $attendee = $vevent->ATTENDEE;
            $instances[$recurId] = $attendee['PARTSTAT']->getValue();
            if (isset($vevent->{'REQUEST-STATUS'})) {
                $requestStatus = $vevent->{'REQUEST-STATUS'}->getValue();
                list($requestStatus) = explode(';', $requestStatus);
            }
        }

                        $masterObject = null;
        foreach ($existingObject->VEVENT as $vevent) {
            $recurId = isset($vevent->{'RECURRENCE-ID'}) ? $vevent->{'RECURRENCE-ID'}->getValue() : 'master';
            if ('master' === $recurId) {
                $masterObject = $vevent;
            }
            if (isset($instances[$recurId])) {
                $attendeeFound = false;
                if (isset($vevent->ATTENDEE)) {
                    foreach ($vevent->ATTENDEE as $attendee) {
                        if ($attendee->getValue() === $itipMessage->sender) {
                            $attendeeFound = true;
                            $attendee['PARTSTAT'] = $instances[$recurId];
                            $attendee['SCHEDULE-STATUS'] = $requestStatus;
                                                                                    unset($attendee['RSVP']);
                            break;
                        }
                    }
                }
                if (!$attendeeFound) {
                                                            $attendee = $vevent->add('ATTENDEE', $itipMessage->sender, [
                        'PARTSTAT' => $instances[$recurId],
                    ]);
                    if ($itipMessage->senderName) {
                        $attendee['CN'] = $itipMessage->senderName;
                    }
                }
                unset($instances[$recurId]);
            }
        }

        if (!$masterObject) {
                        return;
        }
                        foreach ($instances as $recurId => $partstat) {
            $recurrenceIterator = new EventIterator($existingObject, $itipMessage->uid);
            $found = false;
            $iterations = 1000;
            do {
                $newObject = $recurrenceIterator->getEventObject();
                $recurrenceIterator->next();

                if (isset($newObject->{'RECURRENCE-ID'}) && $newObject->{'RECURRENCE-ID'}->getValue() === $recurId) {
                    $found = true;
                }
                --$iterations;
            } while ($recurrenceIterator->valid() && !$found && $iterations);

                        if (!$found) {
                continue;
            }

            unset(
                $newObject->RRULE,
                $newObject->EXDATE,
                $newObject->RDATE
            );
            $attendeeFound = false;
            if (isset($newObject->ATTENDEE)) {
                foreach ($newObject->ATTENDEE as $attendee) {
                    if ($attendee->getValue() === $itipMessage->sender) {
                        $attendeeFound = true;
                        $attendee['PARTSTAT'] = $partstat;
                        break;
                    }
                }
            }
            if (!$attendeeFound) {
                                $attendee = $newObject->add('ATTENDEE', $itipMessage->sender, [
                    'PARTSTAT' => $partstat,
                ]);
                if ($itipMessage->senderName) {
                    $attendee['CN'] = $itipMessage->senderName;
                }
            }
            $existingObject->add($newObject);
        }

        return $existingObject;
    }

    
    protected function parseEventForOrganizer(VCalendar $calendar, array $eventInfo, array $oldEventInfo)
    {
                $attendees = [];
        foreach ($oldEventInfo['attendees'] as $attendee) {
            $attendees[$attendee['href']] = [
                'href' => $attendee['href'],
                'oldInstances' => $attendee['instances'],
                'newInstances' => [],
                'name' => $attendee['name'],
                'forceSend' => null,
            ];
        }
        foreach ($eventInfo['attendees'] as $attendee) {
            if (isset($attendees[$attendee['href']])) {
                $attendees[$attendee['href']]['name'] = $attendee['name'];
                $attendees[$attendee['href']]['newInstances'] = $attendee['instances'];
                $attendees[$attendee['href']]['forceSend'] = $attendee['forceSend'];
            } else {
                $attendees[$attendee['href']] = [
                    'href' => $attendee['href'],
                    'oldInstances' => [],
                    'newInstances' => $attendee['instances'],
                    'name' => $attendee['name'],
                    'forceSend' => $attendee['forceSend'],
                ];
            }
        }

        $messages = [];

        foreach ($attendees as $attendee) {
                                    if ($attendee['href'] === $eventInfo['organizer']) {
                continue;
            }

            $message = new Message();
            $message->uid = $eventInfo['uid'];
            $message->component = 'VEVENT';
            $message->sequence = $eventInfo['sequence'];
            $message->sender = $eventInfo['organizer'];
            $message->senderName = $eventInfo['organizerName'];
            $message->recipient = $attendee['href'];
            $message->recipientName = $attendee['name'];

                        $icalMsg = new VCalendar();

            foreach ($calendar->select('VTIMEZONE') as $timezone) {
                $icalMsg->add(clone $timezone);
            }

            if (!$attendee['newInstances']) {
                                                                $message->method = 'CANCEL';

                $icalMsg->METHOD = $message->method;

                $event = $icalMsg->add('VEVENT', [
                    'UID' => $message->uid,
                    'SEQUENCE' => $message->sequence,
                    'DTSTAMP' => gmdate('Ymd\\THis\\Z'),
                ]);
                if (isset($calendar->VEVENT->SUMMARY)) {
                    $event->add('SUMMARY', $calendar->VEVENT->SUMMARY->getValue());
                }
                $event->add(clone $calendar->VEVENT->DTSTART);
                if (isset($calendar->VEVENT->DTEND)) {
                    $event->add(clone $calendar->VEVENT->DTEND);
                } elseif (isset($calendar->VEVENT->DURATION)) {
                    $event->add(clone $calendar->VEVENT->DURATION);
                }
                $org = $event->add('ORGANIZER', $eventInfo['organizer']);
                if ($eventInfo['organizerName']) {
                    $org['CN'] = $eventInfo['organizerName'];
                }
                $event->add('ATTENDEE', $attendee['href'], [
                    'CN' => $attendee['name'],
                ]);
                $message->significantChange = true;
            } else {
                                $message->method = 'REQUEST';

                $icalMsg->METHOD = $message->method;

                                                                                                                
                $message->significantChange =
                    'REQUEST' === $attendee['forceSend'] ||
                    array_keys($attendee['oldInstances']) != array_keys($attendee['newInstances']) ||
                    $oldEventInfo['significantChangeHash'] !== $eventInfo['significantChangeHash'];

                foreach ($attendee['newInstances'] as $instanceId => $instanceInfo) {
                    $currentEvent = clone $eventInfo['instances'][$instanceId];
                    if ('master' === $instanceId) {
                                                                        $exceptions = [];
                        foreach ($eventInfo['instances'] as $instanceId => $vevent) {
                            if (!isset($attendee['newInstances'][$instanceId])) {
                                $exceptions[] = $instanceId;
                            }
                        }

                                                                        if ($exceptions) {
                            if (isset($currentEvent->EXDATE)) {
                                $currentEvent->EXDATE->setParts(array_merge(
                                    $currentEvent->EXDATE->getParts(),
                                    $exceptions
                                ));
                            } else {
                                $currentEvent->EXDATE = $exceptions;
                            }
                        }

                                                                        unset($currentEvent->ORGANIZER['SCHEDULE-FORCE-SEND']);
                        unset($currentEvent->ORGANIZER['SCHEDULE-STATUS']);

                        foreach ($currentEvent->ATTENDEE as $attendee) {
                            unset($attendee['SCHEDULE-FORCE-SEND']);
                            unset($attendee['SCHEDULE-STATUS']);

                                                                                    if (!isset($attendee['PARTSTAT'])) {
                                $attendee['PARTSTAT'] = 'NEEDS-ACTION';
                            }
                        }
                    }

                    $currentEvent->DTSTAMP = gmdate('Ymd\\THis\\Z');
                    $icalMsg->add($currentEvent);
                }
            }

            $message->message = $icalMsg;
            $messages[] = $message;
        }

        return $messages;
    }

    
    protected function parseEventForAttendee(VCalendar $calendar, array $eventInfo, array $oldEventInfo, $attendee)
    {
        if ($this->scheduleAgentServerRules && 'CLIENT' === $eventInfo['organizerScheduleAgent']) {
            return [];
        }

                        if ('CANCELLED' === $eventInfo['status']) {
            return [];
        }

        $oldInstances = !empty($oldEventInfo['attendees'][$attendee]['instances']) ?
            $oldEventInfo['attendees'][$attendee]['instances'] :
            [];

        $instances = [];
        foreach ($oldInstances as $instance) {
            $instances[$instance['id']] = [
                'id' => $instance['id'],
                'oldstatus' => $instance['partstat'],
                'newstatus' => null,
            ];
        }
        foreach ($eventInfo['attendees'][$attendee]['instances'] as $instance) {
            if (isset($instances[$instance['id']])) {
                $instances[$instance['id']]['newstatus'] = $instance['partstat'];
            } else {
                $instances[$instance['id']] = [
                    'id' => $instance['id'],
                    'oldstatus' => null,
                    'newstatus' => $instance['partstat'],
                ];
            }
        }

                                                if (isset($instances['master']) && 'DECLINED' !== $instances['master']['newstatus']) {
            foreach ($eventInfo['exdate'] as $exDate) {
                if (!in_array($exDate, $oldEventInfo['exdate'])) {
                    if (isset($instances[$exDate])) {
                        $instances[$exDate]['newstatus'] = 'DECLINED';
                    } else {
                        $instances[$exDate] = [
                            'id' => $exDate,
                            'oldstatus' => null,
                            'newstatus' => 'DECLINED',
                        ];
                    }
                }
            }
        }

                foreach ($instances as $recurId => $instanceInfo) {
            if (isset($eventInfo['instances'][$recurId])) {
                $instances[$recurId]['dtstart'] = clone $eventInfo['instances'][$recurId]->DTSTART;
            } else {
                $instances[$recurId]['dtstart'] = $recurId;
            }
        }

        $message = new Message();
        $message->uid = $eventInfo['uid'];
        $message->method = 'REPLY';
        $message->component = 'VEVENT';
        $message->sequence = $eventInfo['sequence'];
        $message->sender = $attendee;
        $message->senderName = $eventInfo['attendees'][$attendee]['name'];
        $message->recipient = $eventInfo['organizer'];
        $message->recipientName = $eventInfo['organizerName'];

        $icalMsg = new VCalendar();
        $icalMsg->METHOD = 'REPLY';

        foreach ($calendar->select('VTIMEZONE') as $timezone) {
            $icalMsg->add(clone $timezone);
        }

        $hasReply = false;

        foreach ($instances as $instance) {
            if ($instance['oldstatus'] == $instance['newstatus'] && 'REPLY' !== $eventInfo['organizerForceSend']) {
                                continue;
            }

            $event = $icalMsg->add('VEVENT', [
                'UID' => $message->uid,
                'SEQUENCE' => $message->sequence,
            ]);
            $summary = isset($calendar->VEVENT->SUMMARY) ? $calendar->VEVENT->SUMMARY->getValue() : '';
                        if (isset($eventInfo['instances'][$instance['id']])) {
                $instanceObj = $eventInfo['instances'][$instance['id']];
                $event->add(clone $instanceObj->DTSTART);
                if (isset($instanceObj->DTEND)) {
                    $event->add(clone $instanceObj->DTEND);
                } elseif (isset($instanceObj->DURATION)) {
                    $event->add(clone $instanceObj->DURATION);
                }
                if (isset($instanceObj->SUMMARY)) {
                    $event->add('SUMMARY', $instanceObj->SUMMARY->getValue());
                } elseif ($summary) {
                    $event->add('SUMMARY', $summary);
                }
            } else {
                                                                                $dt = DateTimeParser::parse($instance['id'], $eventInfo['timezone']);
                                if (strlen($instance['id']) <= 8) {
                    $event->add('DTSTART', $dt, ['VALUE' => 'DATE']);
                } else {
                    $event->add('DTSTART', $dt);
                }
                if ($summary) {
                    $event->add('SUMMARY', $summary);
                }
            }
            if ('master' !== $instance['id']) {
                $dt = DateTimeParser::parse($instance['id'], $eventInfo['timezone']);
                                if (strlen($instance['id']) <= 8) {
                    $event->add('RECURRENCE-ID', $dt, ['VALUE' => 'DATE']);
                } else {
                    $event->add('RECURRENCE-ID', $dt);
                }
            }
            $organizer = $event->add('ORGANIZER', $message->recipient);
            if ($message->recipientName) {
                $organizer['CN'] = $message->recipientName;
            }
            $attendee = $event->add('ATTENDEE', $message->sender, [
                'PARTSTAT' => $instance['newstatus'],
            ]);
            if ($message->senderName) {
                $attendee['CN'] = $message->senderName;
            }
            $hasReply = true;
        }

        if ($hasReply) {
            $message->message = $icalMsg;

            return [$message];
        } else {
            return [];
        }
    }

    
    protected function parseEventInfo(VCalendar $calendar = null)
    {
        $uid = null;
        $organizer = null;
        $organizerName = null;
        $organizerForceSend = null;
        $sequence = null;
        $timezone = null;
        $status = null;
        $organizerScheduleAgent = 'SERVER';

        $significantChangeHash = '';

                        $attendees = [];

        $instances = [];
        $exdate = [];

        foreach ($calendar->VEVENT as $vevent) {
            $rrule = [];

            if (is_null($uid)) {
                $uid = $vevent->UID->getValue();
            } else {
                if ($uid !== $vevent->UID->getValue()) {
                    throw new ITipException('If a calendar contained more than one event, they must have the same UID.');
                }
            }

            if (!isset($vevent->DTSTART)) {
                throw new ITipException('An event MUST have a DTSTART property.');
            }

            if (isset($vevent->ORGANIZER)) {
                if (is_null($organizer)) {
                    $organizer = $vevent->ORGANIZER->getNormalizedValue();
                    $organizerName = isset($vevent->ORGANIZER['CN']) ? $vevent->ORGANIZER['CN'] : null;
                } else {
                    if (strtoupper($organizer) !== strtoupper($vevent->ORGANIZER->getNormalizedValue())) {
                        throw new SameOrganizerForAllComponentsException('Every instance of the event must have the same organizer.');
                    }
                }
                $organizerForceSend =
                    isset($vevent->ORGANIZER['SCHEDULE-FORCE-SEND']) ?
                    strtoupper($vevent->ORGANIZER['SCHEDULE-FORCE-SEND']) :
                    null;
                $organizerScheduleAgent =
                    isset($vevent->ORGANIZER['SCHEDULE-AGENT']) ?
                    strtoupper((string) $vevent->ORGANIZER['SCHEDULE-AGENT']) :
                    'SERVER';
            }
            if (is_null($sequence) && isset($vevent->SEQUENCE)) {
                $sequence = $vevent->SEQUENCE->getValue();
            }
            if (isset($vevent->EXDATE)) {
                foreach ($vevent->select('EXDATE') as $val) {
                    $exdate = array_merge($exdate, $val->getParts());
                }
                sort($exdate);
            }
            if (isset($vevent->RRULE)) {
                foreach ($vevent->select('RRULE') as $rr) {
                    foreach ($rr->getParts() as $key => $val) {
                                                if ('INTERVAL' === $key && 1 == $val) {
                            continue;
                        }
                        if (is_array($val)) {
                            $val = implode(',', $val);
                        }
                        $rrule[] = "$key=$val";
                    }
                }
                sort($rrule);
            }
            if (isset($vevent->STATUS)) {
                $status = strtoupper($vevent->STATUS->getValue());
            }

            $recurId = isset($vevent->{'RECURRENCE-ID'}) ? $vevent->{'RECURRENCE-ID'}->getValue() : 'master';
            if (is_null($timezone)) {
                if ('master' === $recurId) {
                    $timezone = $vevent->DTSTART->getDateTime()->getTimeZone();
                } else {
                    $timezone = $vevent->{'RECURRENCE-ID'}->getDateTime()->getTimeZone();
                }
            }
            if (isset($vevent->ATTENDEE)) {
                foreach ($vevent->ATTENDEE as $attendee) {
                    if ($this->scheduleAgentServerRules &&
                        isset($attendee['SCHEDULE-AGENT']) &&
                        'CLIENT' === strtoupper($attendee['SCHEDULE-AGENT']->getValue())
                    ) {
                        continue;
                    }
                    $partStat =
                        isset($attendee['PARTSTAT']) ?
                        strtoupper($attendee['PARTSTAT']) :
                        'NEEDS-ACTION';

                    $forceSend =
                        isset($attendee['SCHEDULE-FORCE-SEND']) ?
                        strtoupper($attendee['SCHEDULE-FORCE-SEND']) :
                        null;

                    if (isset($attendees[$attendee->getNormalizedValue()])) {
                        $attendees[$attendee->getNormalizedValue()]['instances'][$recurId] = [
                            'id' => $recurId,
                            'partstat' => $partStat,
                            'forceSend' => $forceSend,
                        ];
                    } else {
                        $attendees[$attendee->getNormalizedValue()] = [
                            'href' => $attendee->getNormalizedValue(),
                            'instances' => [
                                $recurId => [
                                    'id' => $recurId,
                                    'partstat' => $partStat,
                                ],
                            ],
                            'name' => isset($attendee['CN']) ? (string) $attendee['CN'] : null,
                            'forceSend' => $forceSend,
                        ];
                    }
                }
                $instances[$recurId] = $vevent;
            }

            foreach ($this->significantChangeProperties as $prop) {
                if (isset($vevent->$prop)) {
                    $propertyValues = $vevent->select($prop);

                    $significantChangeHash .= $prop.':';

                    if ('EXDATE' === $prop) {
                        $significantChangeHash .= implode(',', $exdate).';';
                    } elseif ('RRULE' === $prop) {
                        $significantChangeHash .= implode(',', $rrule).';';
                    } else {
                        foreach ($propertyValues as $val) {
                            $significantChangeHash .= $val->getValue().';';
                        }
                    }
                }
            }
        }
        $significantChangeHash = md5($significantChangeHash);

        return compact(
            'uid',
            'organizer',
            'organizerName',
            'organizerScheduleAgent',
            'organizerForceSend',
            'instances',
            'attendees',
            'sequence',
            'exdate',
            'timezone',
            'significantChangeHash',
            'status'
        );
    }
}
