<?php

namespace TAS\Core\WebUI;

class InputDate extends Element
{
    public $ID;
    public $Name;

    public function __construct($args = null)
    {        
        $this->init();
    }

    private function init()
    {
        $this->TagName ='INPUT';
        $this->IsContainer= false;
        $this->Children=[];
        $this->MustClass[] = 'date';

        $this->Attributes=[
           'type'=>'date',
           'id'=>$this->ID,
           'name'=>$this->Name,           
       ];
       $this->SetAttribute('class', '');
    }

    public function SetValue($value){
        $this->Attributes['value'] = \TAS\Core\DataFormat::DBToDateTimeFormat( $value, 'Y-m-d');
    }
}
