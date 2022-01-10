<?php

namespace TAS\Core\WebUI;

class Element implements IElement
{
    public $IsContainer = false;
    public $TagName = '';
    public $Attributes = [];
    public $Children = [];
    public $MustClass = [];

    /**
     * Render the Element by default method.
     */
    public function Render(): string
    {
        $callback = fn (?string $k, ?string $v): string => $k.'="'.\htmlentities($v ?? '').'"';
        $attributes = array_map($callback, \array_keys($this->Attributes), \array_values($this->Attributes));

        $children = '';
        if (count($this->Children) > 0) {
            $this->IsContainer = true;
            $children = implode('', $this->Children);
        }

        return '<'.strtolower($this->TagName).' '.implode(' ', $attributes).($this->IsContainer ? '' : '/').'>'.$children.($this->IsContainer ? '</'.\strtolower($this->TagName).'>' : '');
    }

    /**
     * Add Child HTML as container content.
     */
    public function AddChild(string $childHTML)
    {
        $this->Children[] = $childHTML;
    }

    /**
     * Add IElement based Class object, so we can call Render function ourselves.
     */
    public function AddChildElement(IElement $child)
    {
        $this->Children[] = $child->Render();
    }

    public function SetAttribute(string $key, ?string $value)
    {
        if (null == $key) {
            return;
        }
        $value ??= '';
        $key = strtolower($key);
        if ('class' == $key) {
            $_tclasses = array_merge($this->MustClass, explode(' ', $value));
            array_unique($_tclasses);
            $value = implode(' ', $_tclasses);
        }
        $this->Attributes[strtolower($key)] = $value;
    }
}
