<?php

namespace Sabre\VObject;

use Sabre\Xml;


class Component extends Node
{
    
    public $name;

    
    protected $children = [];

    
    public function __construct(Document $root, $name, array $children = [], $defaults = true)
    {
        $this->name = strtoupper($name);
        $this->root = $root;

        if ($defaults) {
                                                            $list = $this->getDefaults();
            $nodes = [];
            foreach ($children as $key => $value) {
                if ($value instanceof Node) {
                    if (isset($list[$value->name])) {
                        unset($list[$value->name]);
                    }
                    $nodes[] = $value;
                } else {
                    $list[$key] = $value;
                }
            }
            foreach ($list as $key => $value) {
                $this->add($key, $value);
            }
            foreach ($nodes as $node) {
                $this->add($node);
            }
        } else {
            foreach ($children as $k => $child) {
                if ($child instanceof Node) {
                                        $this->add($child);
                } else {
                                        $this->add($k, $child);
                }
            }
        }
    }

    
    public function add()
    {
        $arguments = func_get_args();

        if ($arguments[0] instanceof Node) {
            if (isset($arguments[1])) {
                throw new \InvalidArgumentException('The second argument must not be specified, when passing a VObject Node');
            }
            $arguments[0]->parent = $this;
            $newNode = $arguments[0];
        } elseif (is_string($arguments[0])) {
            $newNode = call_user_func_array([$this->root, 'create'], $arguments);
        } else {
            throw new \InvalidArgumentException('The first argument must either be a \\Sabre\\VObject\\Node or a string');
        }

        $name = $newNode->name;
        if (isset($this->children[$name])) {
            $this->children[$name][] = $newNode;
        } else {
            $this->children[$name] = [$newNode];
        }

        return $newNode;
    }

    
    public function remove($item)
    {
        if (is_string($item)) {
                                                if (false === strpos($item, '.')) {
                unset($this->children[strtoupper($item)]);

                return;
            }
                                    foreach ($this->select($item) as $child) {
                $this->remove($child);
            }
        } else {
            foreach ($this->select($item->name) as $k => $child) {
                if ($child === $item) {
                    unset($this->children[$item->name][$k]);

                    return;
                }
            }
        }

        throw new \InvalidArgumentException('The item you passed to remove() was not a child of this component');
    }

    
    public function children()
    {
        $result = [];
        foreach ($this->children as $childGroup) {
            $result = array_merge($result, $childGroup);
        }

        return $result;
    }

    
    public function getComponents()
    {
        $result = [];

        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof self) {
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    
    public function select($name)
    {
        $group = null;
        $name = strtoupper($name);
        if (false !== strpos($name, '.')) {
            list($group, $name) = explode('.', $name, 2);
        }
        if ('' === $name) {
            $name = null;
        }

        if (!is_null($name)) {
            $result = isset($this->children[$name]) ? $this->children[$name] : [];

            if (is_null($group)) {
                return $result;
            } else {
                                                return array_filter(
                    $result,
                    function ($child) use ($group) {
                        return $child instanceof Property && strtoupper($child->group) === $group;
                    }
                );
            }
        }

                        $result = [];
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof Property && strtoupper($child->group) === $group) {
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    
    public function serialize()
    {
        $str = 'BEGIN:'.$this->name."\r\n";

        
        $sortScore = function ($key, $array) {
            if ($array[$key] instanceof Component) {
                                                if ('VTIMEZONE' === $array[$key]->name) {
                    $score = 300000000;

                    return $score + $key;
                } else {
                    $score = 400000000;

                    return $score + $key;
                }
            } else {
                                                if ($array[$key] instanceof Property) {
                    if ('VERSION' === $array[$key]->name) {
                        $score = 100000000;

                        return $score + $key;
                    } else {
                                                $score = 200000000;

                        return $score + $key;
                    }
                }
            }
        };

        $children = $this->children();
        $tmp = $children;
        uksort(
            $children,
            function ($a, $b) use ($sortScore, $tmp) {
                $sA = $sortScore($a, $tmp);
                $sB = $sortScore($b, $tmp);

                return $sA - $sB;
            }
        );

        foreach ($children as $child) {
            $str .= $child->serialize();
        }
        $str .= 'END:'.$this->name."\r\n";

        return $str;
    }

    
    public function jsonSerialize()
    {
        $components = [];
        $properties = [];

        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof self) {
                    $components[] = $child->jsonSerialize();
                } else {
                    $properties[] = $child->jsonSerialize();
                }
            }
        }

        return [
            strtolower($this->name),
            $properties,
            $components,
        ];
    }

    
    public function xmlSerialize(Xml\Writer $writer)
    {
        $components = [];
        $properties = [];

        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof self) {
                    $components[] = $child;
                } else {
                    $properties[] = $child;
                }
            }
        }

        $writer->startElement(strtolower($this->name));

        if (!empty($properties)) {
            $writer->startElement('properties');

            foreach ($properties as $property) {
                $property->xmlSerialize($writer);
            }

            $writer->endElement();
        }

        if (!empty($components)) {
            $writer->startElement('components');

            foreach ($components as $component) {
                $component->xmlSerialize($writer);
            }

            $writer->endElement();
        }

        $writer->endElement();
    }

    
    protected function getDefaults()
    {
        return [];
    }

    

    
    public function __get($name)
    {
        if ('children' === $name) {
            throw new \RuntimeException('Starting sabre/vobject 4.0 the children property is now protected. You should use the children() method instead');
        }

        $matches = $this->select($name);
        if (0 === count($matches)) {
            return;
        } else {
            $firstMatch = current($matches);
            
            $firstMatch->setIterator(new ElementList(array_values($matches)));

            return $firstMatch;
        }
    }

    
    public function __isset($name)
    {
        $matches = $this->select($name);

        return count($matches) > 0;
    }

    
    public function __set($name, $value)
    {
        $name = strtoupper($name);
        $this->remove($name);
        if ($value instanceof self || $value instanceof Property) {
            $this->add($value);
        } else {
            $this->add($name, $value);
        }
    }

    
    public function __unset($name)
    {
        $this->remove($name);
    }

    

    
    public function __clone()
    {
        foreach ($this->children as $childName => $childGroup) {
            foreach ($childGroup as $key => $child) {
                $clonedChild = clone $child;
                $clonedChild->parent = $this;
                $clonedChild->root = $this->root;
                $this->children[$childName][$key] = $clonedChild;
            }
        }
    }

    
    public function getValidationRules()
    {
        return [];
    }

    
    public function validate($options = 0)
    {
        $rules = $this->getValidationRules();
        $defaults = $this->getDefaults();

        $propertyCounters = [];

        $messages = [];

        foreach ($this->children() as $child) {
            $name = strtoupper($child->name);
            if (!isset($propertyCounters[$name])) {
                $propertyCounters[$name] = 1;
            } else {
                ++$propertyCounters[$name];
            }
            $messages = array_merge($messages, $child->validate($options));
        }

        foreach ($rules as $propName => $rule) {
            switch ($rule) {
                case '0':
                    if (isset($propertyCounters[$propName])) {
                        $messages[] = [
                            'level' => 3,
                            'message' => $propName.' MUST NOT appear in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
                case '1':
                    if (!isset($propertyCounters[$propName]) || 1 !== $propertyCounters[$propName]) {
                        $repaired = false;
                        if ($options & self::REPAIR && isset($defaults[$propName])) {
                            $this->add($propName, $defaults[$propName]);
                            $repaired = true;
                        }
                        $messages[] = [
                            'level' => $repaired ? 1 : 3,
                            'message' => $propName.' MUST appear exactly once in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
                case '+':
                    if (!isset($propertyCounters[$propName]) || $propertyCounters[$propName] < 1) {
                        $messages[] = [
                            'level' => 3,
                            'message' => $propName.' MUST appear at least once in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
                case '*':
                    break;
                case '?':
                    if (isset($propertyCounters[$propName]) && $propertyCounters[$propName] > 1) {
                        $level = 3;

                                                                        if ($options & self::REPAIR) {
                            $properties = array_unique($this->select($propName), SORT_REGULAR);

                            if (1 === count($properties)) {
                                $this->remove($propName);
                                $this->add($properties[0]);

                                $level = 1;
                            }
                        }

                        $messages[] = [
                            'level' => $level,
                            'message' => $propName.' MUST NOT appear more than once in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
            }
        }

        return $messages;
    }

    
    public function destroy()
    {
        parent::destroy();
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                $child->destroy();
            }
        }
        $this->children = [];
    }
}
