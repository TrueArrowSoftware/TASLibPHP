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
        if (is_array($value) && isset($value['date'])){
            $value = $value['date'];
        }
        $this->Attributes['value'] = \TAS\Core\DataFormat::DBToDateTimeFormat( $value, 'Y-m-d');
    }
}
