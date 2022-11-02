<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;
use Sabre\Xml;


class VCard extends VObject\Document
{
    
    public static $defaultName = 'VCARD';

    
    private $version = null;

    
    public static $componentMap = [
        'VCARD' => 'Sabre\\VObject\\Component\\VCard',
    ];

    
    public static $valueMap = [
        'BINARY' => 'Sabre\\VObject\\Property\\Binary',
        'BOOLEAN' => 'Sabre\\VObject\\Property\\Boolean',
        'CONTENT-ID' => 'Sabre\\VObject\\Property\\FlatText',           'DATE' => 'Sabre\\VObject\\Property\\VCard\\Date',
        'DATE-TIME' => 'Sabre\\VObject\\Property\\VCard\\DateTime',
        'DATE-AND-OR-TIME' => 'Sabre\\VObject\\Property\\VCard\\DateAndOrTime',         'FLOAT' => 'Sabre\\VObject\\Property\\FloatValue',
        'INTEGER' => 'Sabre\\VObject\\Property\\IntegerValue',
        'LANGUAGE-TAG' => 'Sabre\\VObject\\Property\\VCard\\LanguageTag',
        'PHONE-NUMBER' => 'Sabre\\VObject\\Property\\VCard\\PhoneNumber',         'TIMESTAMP' => 'Sabre\\VObject\\Property\\VCard\\TimeStamp',
        'TEXT' => 'Sabre\\VObject\\Property\\Text',
        'TIME' => 'Sabre\\VObject\\Property\\Time',
        'UNKNOWN' => 'Sabre\\VObject\\Property\\Unknown',         'URI' => 'Sabre\\VObject\\Property\\Uri',
        'URL' => 'Sabre\\VObject\\Property\\Uri',         'UTC-OFFSET' => 'Sabre\\VObject\\Property\\UtcOffset',
    ];

    
    public static $propertyMap = [
                'N' => 'Sabre\\VObject\\Property\\Text',
        'FN' => 'Sabre\\VObject\\Property\\FlatText',
        'PHOTO' => 'Sabre\\VObject\\Property\\Binary',
        'BDAY' => 'Sabre\\VObject\\Property\\VCard\\DateAndOrTime',
        'ADR' => 'Sabre\\VObject\\Property\\Text',
        'LABEL' => 'Sabre\\VObject\\Property\\FlatText',         'TEL' => 'Sabre\\VObject\\Property\\FlatText',
        'EMAIL' => 'Sabre\\VObject\\Property\\FlatText',
        'MAILER' => 'Sabre\\VObject\\Property\\FlatText',         'GEO' => 'Sabre\\VObject\\Property\\FlatText',
        'TITLE' => 'Sabre\\VObject\\Property\\FlatText',
        'ROLE' => 'Sabre\\VObject\\Property\\FlatText',
        'LOGO' => 'Sabre\\VObject\\Property\\Binary',
                                                 'ORG' => 'Sabre\\VObject\\Property\\Text',
        'NOTE' => 'Sabre\\VObject\\Property\\FlatText',
        'REV' => 'Sabre\\VObject\\Property\\VCard\\TimeStamp',
        'SOUND' => 'Sabre\\VObject\\Property\\FlatText',
        'URL' => 'Sabre\\VObject\\Property\\Uri',
        'UID' => 'Sabre\\VObject\\Property\\FlatText',
        'VERSION' => 'Sabre\\VObject\\Property\\FlatText',
        'KEY' => 'Sabre\\VObject\\Property\\FlatText',
        'TZ' => 'Sabre\\VObject\\Property\\Text',

                'CATEGORIES' => 'Sabre\\VObject\\Property\\Text',
        'SORT-STRING' => 'Sabre\\VObject\\Property\\FlatText',
        'PRODID' => 'Sabre\\VObject\\Property\\FlatText',
        'NICKNAME' => 'Sabre\\VObject\\Property\\Text',
        'CLASS' => 'Sabre\\VObject\\Property\\FlatText', 
                'FBURL' => 'Sabre\\VObject\\Property\\Uri',
        'CAPURI' => 'Sabre\\VObject\\Property\\Uri',
        'CALURI' => 'Sabre\\VObject\\Property\\Uri',
        'CALADRURI' => 'Sabre\\VObject\\Property\\Uri',

                'IMPP' => 'Sabre\\VObject\\Property\\Uri',

                'SOURCE' => 'Sabre\\VObject\\Property\\Uri',
        'XML' => 'Sabre\\VObject\\Property\\FlatText',
        'ANNIVERSARY' => 'Sabre\\VObject\\Property\\VCard\\DateAndOrTime',
        'CLIENTPIDMAP' => 'Sabre\\VObject\\Property\\Text',
        'LANG' => 'Sabre\\VObject\\Property\\VCard\\LanguageTag',
        'GENDER' => 'Sabre\\VObject\\Property\\Text',
        'KIND' => 'Sabre\\VObject\\Property\\FlatText',
        'MEMBER' => 'Sabre\\VObject\\Property\\Uri',
        'RELATED' => 'Sabre\\VObject\\Property\\Uri',

                'BIRTHPLACE' => 'Sabre\\VObject\\Property\\FlatText',
        'DEATHPLACE' => 'Sabre\\VObject\\Property\\FlatText',
        'DEATHDATE' => 'Sabre\\VObject\\Property\\VCard\\DateAndOrTime',

                'EXPERTISE' => 'Sabre\\VObject\\Property\\FlatText',
        'HOBBY' => 'Sabre\\VObject\\Property\\FlatText',
        'INTEREST' => 'Sabre\\VObject\\Property\\FlatText',
        'ORG-DIRECTORY' => 'Sabre\\VObject\\Property\\FlatText',
    ];

    
    public function getDocumentType()
    {
        if (!$this->version) {
            $version = (string) $this->VERSION;

            switch ($version) {
                case '2.1':
                    $this->version = self::VCARD21;
                    break;
                case '3.0':
                    $this->version = self::VCARD30;
                    break;
                case '4.0':
                    $this->version = self::VCARD40;
                    break;
                default:
                                                            return self::UNKNOWN;
            }
        }

        return $this->version;
    }

    
    public function convert($target)
    {
        $converter = new VObject\VCardConverter();

        return $converter->convert($this, $target);
    }

    
    const DEFAULT_VERSION = self::VCARD21;

    
    public function validate($options = 0)
    {
        $warnings = [];

        $versionMap = [
            self::VCARD21 => '2.1',
            self::VCARD30 => '3.0',
            self::VCARD40 => '4.0',
        ];

        $version = $this->select('VERSION');
        if (1 === count($version)) {
            $version = (string) $this->VERSION;
            if ('2.1' !== $version && '3.0' !== $version && '4.0' !== $version) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'Only vcard version 4.0 (RFC6350), version 3.0 (RFC2426) or version 2.1 (icm-vcard-2.1) are supported.',
                    'node' => $this,
                ];
                if ($options & self::REPAIR) {
                    $this->VERSION = $versionMap[self::DEFAULT_VERSION];
                }
            }
            if ('2.1' === $version && ($options & self::PROFILE_CARDDAV)) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'CardDAV servers are not allowed to accept vCard 2.1.',
                    'node' => $this,
                ];
            }
        }
        $uid = $this->select('UID');
        if (0 === count($uid)) {
            if ($options & self::PROFILE_CARDDAV) {
                                $warningLevel = 3;
                $message = 'vCards on CardDAV servers MUST have a UID property.';
            } else {
                                $warningLevel = 2;
                $message = 'Adding a UID to a vCard property is recommended.';
            }
            if ($options & self::REPAIR) {
                $this->UID = VObject\UUIDUtil::getUUID();
                $warningLevel = 1;
            }
            $warnings[] = [
                'level' => $warningLevel,
                'message' => $message,
                'node' => $this,
            ];
        }

        $fn = $this->select('FN');
        if (1 !== count($fn)) {
            $repaired = false;
            if (($options & self::REPAIR) && 0 === count($fn)) {
                                                if (isset($this->N)) {
                    $value = explode(';', (string) $this->N);
                    if (isset($value[1]) && $value[1]) {
                        $this->FN = $value[1].' '.$value[0];
                    } else {
                        $this->FN = $value[0];
                    }
                    $repaired = true;

                                } elseif (isset($this->ORG)) {
                    $this->FN = (string) $this->ORG;
                    $repaired = true;

                                } elseif (isset($this->EMAIL)) {
                    $this->FN = (string) $this->EMAIL;
                    $repaired = true;
                }
            }
            $warnings[] = [
                'level' => $repaired ? 1 : 3,
                'message' => 'The FN property must appear in the VCARD component exactly 1 time',
                'node' => $this,
            ];
        }

        return array_merge(
            parent::validate($options),
            $warnings
        );
    }

    
    public function getValidationRules()
    {
        return [
            'ADR' => '*',
            'ANNIVERSARY' => '?',
            'BDAY' => '?',
            'CALADRURI' => '*',
            'CALURI' => '*',
            'CATEGORIES' => '*',
            'CLIENTPIDMAP' => '*',
            'EMAIL' => '*',
            'FBURL' => '*',
            'IMPP' => '*',
            'GENDER' => '?',
            'GEO' => '*',
            'KEY' => '*',
            'KIND' => '?',
            'LANG' => '*',
            'LOGO' => '*',
            'MEMBER' => '*',
            'N' => '?',
            'NICKNAME' => '*',
            'NOTE' => '*',
            'ORG' => '*',
            'PHOTO' => '*',
            'PRODID' => '?',
            'RELATED' => '*',
            'REV' => '?',
            'ROLE' => '*',
            'SOUND' => '*',
            'SOURCE' => '*',
            'TEL' => '*',
            'TITLE' => '*',
            'TZ' => '*',
            'URL' => '*',
            'VERSION' => '1',
            'XML' => '*',

                                                'UID' => '?',
        ];
    }

    
    public function preferred($propertyName)
    {
        $preferred = null;
        $lastPref = 101;
        foreach ($this->select($propertyName) as $field) {
            $pref = 101;
            if (isset($field['TYPE']) && $field['TYPE']->has('PREF')) {
                $pref = 1;
            } elseif (isset($field['PREF'])) {
                $pref = $field['PREF']->getValue();
            }

            if ($pref < $lastPref || is_null($preferred)) {
                $preferred = $field;
                $lastPref = $pref;
            }
        }

        return $preferred;
    }

    
    public function getByType($propertyName, $type)
    {
        foreach ($this->select($propertyName) as $field) {
            if (isset($field['TYPE']) && $field['TYPE']->has($type)) {
                return $field;
            }
        }
    }

    
    protected function getDefaults()
    {
        return [
            'VERSION' => '4.0',
            'PRODID' => '-//Sabre//Sabre VObject '.VObject\Version::VERSION.'//EN',
            'UID' => 'sabre-vobject-'.VObject\UUIDUtil::getUUID(),
        ];
    }

    
    public function jsonSerialize()
    {
                        $properties = [];

        foreach ($this->children() as $child) {
            $properties[] = $child->jsonSerialize();
        }

        return [
            strtolower($this->name),
            $properties,
        ];
    }

    
    public function xmlSerialize(Xml\Writer $writer)
    {
        $propertiesByGroup = [];

        foreach ($this->children() as $property) {
            $group = $property->group;

            if (!isset($propertiesByGroup[$group])) {
                $propertiesByGroup[$group] = [];
            }

            $propertiesByGroup[$group][] = $property;
        }

        $writer->startElement(strtolower($this->name));

        foreach ($propertiesByGroup as $group => $properties) {
            if (!empty($group)) {
                $writer->startElement('group');
                $writer->writeAttribute('name', strtolower($group));
            }

            foreach ($properties as $property) {
                switch ($property->name) {
                    case 'VERSION':
                        break;

                    case 'XML':
                        $value = $property->getParts();
                        $fragment = new Xml\Element\XmlFragment($value[0]);
                        $writer->write($fragment);
                        break;

                    default:
                        $property->xmlSerialize($writer);
                        break;
                }
            }

            if (!empty($group)) {
                $writer->endElement();
            }
        }

        $writer->endElement();
    }

    
    public function getClassNameForPropertyName($propertyName)
    {
        $className = parent::getClassNameForPropertyName($propertyName);

                if ('Sabre\\VObject\\Property\\Binary' == $className && self::VCARD40 === $this->getDocumentType()) {
            return 'Sabre\\VObject\\Property\\Uri';
        }

        return $className;
    }
}
