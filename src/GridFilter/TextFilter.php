<?php

namespace TAS\Core\GridFilter;

use TAS\Core\DataFormat;

class TextFilter implements IGridFilter
{
    public function Render(string $name, array $filterdata): string
    {
        $v = DataFormat::DoSecure($filterdata[$name] ?? '');

        return '<input type="text" class="filter-textbox" id="' . $name . '" name="' . $name . '" value="' . $v . '">';
    }
}
