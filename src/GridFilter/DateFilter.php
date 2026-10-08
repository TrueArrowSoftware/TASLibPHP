<?php

namespace TAS\Core\GridFilter;

use TAS\Core\DataFormat;
use TAS\Core\HTML;

/**
 * Date selector; with $range = true adds a second "{name}-end" input.
 */
class DateFilter implements IGridFilter
{
    public function __construct(public bool $range = false)
    {
    }

    public function Render(string $name, array $filterdata): string
    {
        $html = $this->DateInput($name, $filterdata);
        if ($this->range) {
            $html .= '<br />' . $this->DateInput($name . '-end', $filterdata);
        }

        return $html;
    }

    private function DateInput(string $name, array $filterdata): string
    {
        $v = DataFormat::DoSecure($filterdata[$name] ?? '');

        return HTML::InputDate($name, DataFormat::DBToDateTimeFormat($v, 'Y-m-d H:i:s'), $name, false, 'filter-textbox');
    }
}
