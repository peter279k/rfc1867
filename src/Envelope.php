<?php

namespace lewiscowles\Rfc;

use lewiscowles\Rfc\NodeInterface;


Final class Envelope implements NodeInterface {

    const TYPE_FORM_DATA = 'multipart/form-data';
    const TYPE_MIXED = 'multipart/mixed';

    const ATTRIB_BOUNDARY = "boundary";

    private $items = [];
    private $boundary = '';
    private $type = '';
    private $name = '';

    public function __construct(string $name, string $type='', $items = []) {
        $this->items = $items;
        $this->name = $name;
        $this->type = $type !== '' ? $type : self::TYPE_FORM_DATA;
        $this->boundary = sprintf(
            "%04X%04X%04X%04X",
            rand(0,65535),
            rand(0,65535),
            rand(0,65535),
            rand(0,65535)
        );
    }

    public function exists($name) {
        return isset($this->items[$name]);
    }

    public function add(NodeInterface $node) {

        $name = $node->getName();
        if(!isset($this->items[$name])) {
            $this->items[$name] = $node;
        } else {
            if(get_class($this->items[$name]) === get_class($this)) {
                $this->items[$name]->add($node);
            } else {
                $this->items[$name] = $node->add($this->items[$name]);
            }
        }
        return $this->items[$name];
    }

    public function __toString() {
        return sprintf(
            "%s%s: %s, %s=--%s\n\n--%s\n%s--%s--%s",
            $this->getPrefix(),
            self::HEADER_CONTENT_TYPE,
            $this->type,
            self::ATTRIB_BOUNDARY,
            $this->boundary,
            $this->boundary,
            $this->getItemsAsString(),
            $this->boundary,
            $this->type == self::TYPE_MIXED ? '' : "\n"
        );
    }

    private function getPrefix() {
        if($this->type == self::TYPE_MIXED) {
            return sprintf(
                "%s: %s; %s=\"%s\"\n",
                self::HEADER_DISPOSITION,
                self::DISPOSITION_FORMDATA,
                self::ATTRIB_NAME,
                $this->name
            );
        }
        return "";
    }

    private function getItems() {
        return $this->items;
    }

    private function getItemsAsString() {
        return implode(
            sprintf("--%s\n", $this->boundary),
            array_map([$this, 'getItemString'], $this->items)
        );
    }

    private function getItemString($item) {
        return "{$item}\n";
    }

    public function getName() {
        return $this->name;
    }

    public function getNested() {
        return new Envelope($this->name, self::TYPE_MIXED, $this->getItems());
    }
}