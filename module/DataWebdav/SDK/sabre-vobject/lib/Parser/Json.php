<?php

namespace Sabre\VObject\Parser;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
use Sabre\VObject\EofException;
use Sabre\VObject\ParseException;


class Json extends Parser
{
    
    protected $input;

    
    protected $root;

    
    public function parse($input = null, $options = 0)
    {
        if (!is_null($input)) {
            $this->setInput($input);
        }
        if (is_null($this->input)) {
            throw new EofException('End of input stream, or no input supplied');
        }

        if (0 !== $options) {
            $this->options = $options;
        }

        switch ($this->input[0]) {
            case 'vcalendar':
                $this->root = new VCalendar([], false);
                break;
            case 'vcard':
                $this->root = new VCard([], false);
                break;
            default:
                throw new ParseException('The root component must either be a vcalendar, or a vcard');
        }
        foreach ($this->input[1] as $prop) {
            $this->root->add($this->parseProperty($prop));
        }
        if (isset($this->input[2])) {
            foreach ($this->input[2] as $comp) {
                $this->root->add($this->parseComponent($comp));
            }
        }

                $this->input = null;

        return $this->root;
    }

    
    public function parseComponent(array $jComp)
    {
                $self = $this;

        $properties = array_map(
            function ($jProp) use ($self) {
                return $self->parseProperty($jProp);
            },
            $jComp[1]
        );

        if (isset($jComp[2])) {
            $components = array_map(
                function ($jComp) use ($self) {
                    return $self->parseComponent($jComp);
                },
                $jComp[2]
            );
        } else {
            $components = [];
        }

        return $this->root->createComponent(
            $jComp[0],
            array_merge($properties, $components),
            $defaults = false
        );
    }

    
    public function parseProperty(array $jProp)
    {
        list(
            $propertyName,
            $parameters,
            $valueType
        ) = $jProp;

        $propertyName = strtoupper($propertyName);

                        $defaultPropertyClass = $this->root->getClassNameForPropertyName($propertyName);

        $parameters = (array) $parameters;

        $value = array_slice($jProp, 3);

        $valueType = strtoupper($valueType);

        if (isset($parameters['group'])) {
            $propertyName = $parameters['group'].'.'.$propertyName;
            unset($parameters['group']);
        }

        $prop = $this->root->createProperty($propertyName, null, $parameters, $valueType);
        $prop->setJsonValue($value);

                                        if ('Sabre\VObject\Property\FlatText' === $defaultPropertyClass) {
            $defaultPropertyClass = 'Sabre\VObject\Property\Text';
        }

                                if ($defaultPropertyClass !== get_class($prop)) {
            $prop['VALUE'] = $valueType;
        }

        return $prop;
    }

    
    public function setInput($input)
    {
        if (is_resource($input)) {
            $input = stream_get_contents($input);
        }
        if (is_string($input)) {
            $input = json_decode($input);
        }
        $this->input = $input;
    }
}
