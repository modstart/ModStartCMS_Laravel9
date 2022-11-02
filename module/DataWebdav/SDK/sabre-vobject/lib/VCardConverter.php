<?php

namespace Sabre\VObject;


class VCardConverter
{
    
    public function convert(Component\VCard $input, $targetVersion)
    {
        $inputVersion = $input->getDocumentType();
        if ($inputVersion === $targetVersion) {
            return clone $input;
        }

        if (!in_array($inputVersion, [Document::VCARD21, Document::VCARD30, Document::VCARD40])) {
            throw new \InvalidArgumentException('Only vCard 2.1, 3.0 and 4.0 are supported for the input data');
        }
        if (!in_array($targetVersion, [Document::VCARD30, Document::VCARD40])) {
            throw new \InvalidArgumentException('You can only use vCard 3.0 or 4.0 for the target version');
        }

        $newVersion = Document::VCARD40 === $targetVersion ? '4.0' : '3.0';

        $output = new Component\VCard([
            'VERSION' => $newVersion,
        ]);

                unset($output->UID);

        foreach ($input->children() as $property) {
            $this->convertProperty($input, $output, $property, $targetVersion);
        }

        return $output;
    }

    
    protected function convertProperty(Component\VCard $input, Component\VCard $output, Property $property, $targetVersion)
    {
                if (in_array($property->name, ['VERSION', 'PRODID'])) {
            return;
        }

        $parameters = $property->parameters();
        $valueType = null;
        if (isset($parameters['VALUE'])) {
            $valueType = $parameters['VALUE']->getValue();
            unset($parameters['VALUE']);
        }
        if (!$valueType) {
            $valueType = $property->getValueType();
        }
        if (Document::VCARD30 !== $targetVersion && 'PHONE-NUMBER' === $valueType) {
            $valueType = null;
        }
        $newProperty = $output->createProperty(
            $property->name,
            $property->getParts(),
            [],             $valueType
        );

        if (Document::VCARD30 === $targetVersion) {
            if ($property instanceof Property\Uri && in_array($property->name, ['PHOTO', 'LOGO', 'SOUND'])) {
                $newProperty = $this->convertUriToBinary($output, $newProperty);
            } elseif ($property instanceof Property\VCard\DateAndOrTime) {
                                                                                                                                $parts = DateTimeParser::parseVCardDateTime($property->getValue());
                if (is_null($parts['year'])) {
                    $newValue = '1604-'.$parts['month'].'-'.$parts['date'];
                    $newProperty->setValue($newValue);
                    $newProperty['X-APPLE-OMIT-YEAR'] = '1604';
                }

                if ('ANNIVERSARY' == $newProperty->name) {
                                        $newProperty->name = 'X-ANNIVERSARY';

                                                                                                    $x = 1;
                    while ($output->select('ITEM'.$x.'.')) {
                        ++$x;
                    }
                    $output->add('ITEM'.$x.'.X-ABDATE', $newProperty->getValue(), ['VALUE' => 'DATE-AND-OR-TIME']);
                    $output->add('ITEM'.$x.'.X-ABLABEL', '_$!<Anniversary>!$_');
                }
            } elseif ('KIND' === $property->name) {
                switch (strtolower($property->getValue())) {
                    case 'org':
                                                                                                $newProperty = $output->createProperty('X-ABSHOWAS', 'COMPANY');
                        break;

                    case 'individual':
                                                return;

                    case 'group':
                                                $newProperty = $output->createProperty('X-ADDRESSBOOKSERVER-KIND', 'GROUP');
                        break;
                }
            }
        } elseif (Document::VCARD40 === $targetVersion) {
                        if (in_array($property->name, ['NAME', 'MAILER', 'LABEL', 'CLASS'])) {
                return;
            }

            if ($property instanceof Property\Binary) {
                $newProperty = $this->convertBinaryToUri($output, $newProperty, $parameters);
            } elseif ($property instanceof Property\VCard\DateAndOrTime && isset($parameters['X-APPLE-OMIT-YEAR'])) {
                                                $parts = DateTimeParser::parseVCardDateTime($property->getValue());
                if ($parts['year'] === $property['X-APPLE-OMIT-YEAR']->getValue()) {
                    $newValue = '--'.$parts['month'].'-'.$parts['date'];
                    $newProperty->setValue($newValue);
                }

                                                unset($parameters['X-APPLE-OMIT-YEAR']);
            }
            switch ($property->name) {
                case 'X-ABSHOWAS':
                    if ('COMPANY' === strtoupper($property->getValue())) {
                        $newProperty = $output->createProperty('KIND', 'ORG');
                    }
                    break;
                case 'X-ADDRESSBOOKSERVER-KIND':
                    if ('GROUP' === strtoupper($property->getValue())) {
                        $newProperty = $output->createProperty('KIND', 'GROUP');
                    }
                    break;
                case 'X-ANNIVERSARY':
                    $newProperty->name = 'ANNIVERSARY';
                                                            foreach ($output->select('ANNIVERSARY') as $anniversary) {
                        if ($anniversary->getValue() === $newProperty->getValue()) {
                            return;
                        }
                    }
                    break;
                case 'X-ABDATE':
                                        if (!$property->group) {
                        break;
                    }
                    $label = $input->{$property->group.'.X-ABLABEL'};

                                        if (!$label || '_$!<Anniversary>!$_' !== $label->getValue()) {
                        break;
                    }

                                                            foreach ($output->select('ANNIVERSARY') as $anniversary) {
                        if ($anniversary->getValue() === $newProperty->getValue()) {
                            return;
                        }
                    }
                    $newProperty->name = 'ANNIVERSARY';
                    break;
                                case 'X-ABLABEL':
                    if ('_$!<Anniversary>!$_' === $newProperty->getValue()) {
                                                                        return;
                    }
                    break;
            }
        }

                $newProperty->group = $property->group;

        if (Document::VCARD40 === $targetVersion) {
            $this->convertParameters40($newProperty, $parameters);
        } else {
            $this->convertParameters30($newProperty, $parameters);
        }

                                        $tempProperty = $output->createProperty($newProperty->name);
        if ($tempProperty->getValueType() !== $newProperty->getValueType()) {
            $newProperty['VALUE'] = $newProperty->getValueType();
        }

        $output->add($newProperty);
    }

    
    protected function convertBinaryToUri(Component\VCard $output, Property\Binary $newProperty, array &$parameters)
    {
        $value = $newProperty->getValue();
        $newProperty = $output->createProperty(
            $newProperty->name,
            null,             [],             'URI'         );

        $mimeType = 'application/octet-stream';

                if (isset($parameters['TYPE'])) {
            $newTypes = [];
            foreach ($parameters['TYPE']->getParts() as $typePart) {
                if (in_array(
                    strtoupper($typePart),
                    ['JPEG', 'PNG', 'GIF']
                )) {
                    $mimeType = 'image/'.strtolower($typePart);
                } else {
                    $newTypes[] = $typePart;
                }
            }

                                    if ($newTypes) {
                $parameters['TYPE']->setParts($newTypes);
            } else {
                unset($parameters['TYPE']);
            }
        }

        $newProperty->setValue('data:'.$mimeType.';base64,'.base64_encode($value));

        return $newProperty;
    }

    
    protected function convertUriToBinary(Component\VCard $output, Property\Uri $newProperty)
    {
        $value = $newProperty->getValue();

                if ('data:' !== substr($value, 0, 5)) {
            return $newProperty;
        }

        $newProperty = $output->createProperty(
            $newProperty->name,
            null,             [],             'BINARY'
        );

        $mimeType = substr($value, 5, strpos($value, ',') - 5);
        if (strpos($mimeType, ';')) {
            $mimeType = substr($mimeType, 0, strpos($mimeType, ';'));
            $newProperty->setValue(base64_decode(substr($value, strpos($value, ',') + 1)));
        } else {
            $newProperty->setValue(substr($value, strpos($value, ',') + 1));
        }
        unset($value);

        $newProperty['ENCODING'] = 'b';
        switch ($mimeType) {
            case 'image/jpeg':
                $newProperty['TYPE'] = 'JPEG';
                break;
            case 'image/png':
                $newProperty['TYPE'] = 'PNG';
                break;
            case 'image/gif':
                $newProperty['TYPE'] = 'GIF';
                break;
        }

        return $newProperty;
    }

    
    protected function convertParameters40(Property $newProperty, array $parameters)
    {
                foreach ($parameters as $param) {
                        if ($param->noName) {
                $param->noName = false;
            }

            switch ($param->name) {
                                                case 'TYPE':
                    foreach ($param->getParts() as $paramPart) {
                        if ('PREF' === strtoupper($paramPart)) {
                            $newProperty->add('PREF', '1');
                        } else {
                            $newProperty->add($param->name, $paramPart);
                        }
                    }
                    break;
                                case 'ENCODING':
                case 'CHARSET':
                    break;

                default:
                    $newProperty->add($param->name, $param->getParts());
                    break;
            }
        }
    }

    
    protected function convertParameters30(Property $newProperty, array $parameters)
    {
                foreach ($parameters as $param) {
                        if ($param->noName) {
                $param->noName = false;
            }

            switch ($param->name) {
                case 'ENCODING':
                                                            if ('QUOTED-PRINTABLE' !== strtoupper($param->getValue())) {
                        $newProperty->add($param->name, $param->getParts());
                    }
                    break;

                
                case 'PREF':
                    if ('1' == $param->getValue()) {
                        $newProperty->add('TYPE', 'PREF');
                    }
                    break;

                default:
                    $newProperty->add($param->name, $param->getParts());
                    break;
            }
        }
    }
}
