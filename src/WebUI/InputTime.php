<?php

namespace TAS\Core\WebUI;

class InputTime extends Element
{
    public $ID;
    public $Name;

    public function __construct($args = null)
    {
        $this->init();
    }

    private function init()
    {
        $this->TagName = 'INPUT';
        $this->IsContainer = false;
        $this->Children = [];
        $this->MustClass[] = 'time';

        $this->Attributes = [
            'type' => 'time',
            'id' => $this->ID,
            'name' => $this->Name,
        ];
        $this->SetAttribute('class', '');
    }
}
