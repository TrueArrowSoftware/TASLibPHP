<?php

namespace TAS\Core\WebUI;

class InputDateTime extends Element
{
    public $ID;
    public $Name;

    public function __construct($args = null)
    {
        $this->init();
    }

    public function SetValue($value)
    {
        $this->Attributes['value'] = \TAS\Core\DataFormat::DBToDateTimeFormat($value, 'Y-m-d\TH:i:s');
    }

    private function init()
    {
        $this->TagName = 'INPUT';
        $this->IsContainer = false;
        $this->Children = [];
        $this->MustClass[] = 'datetime';

        $this->Attributes = [
            'type' => 'datetime-local',
            'id' => $this->ID,
            'name' => $this->Name,
        ];
        $this->SetAttribute('class', '');
    }
}
