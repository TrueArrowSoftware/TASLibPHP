<?php

namespace TAS\Core\GridFilter;

use TAS\Core\DataFormat;
use TAS\Core\HTML;
use TAS\Core\UI;

/**
 * Dropdown filter. $source is either an array (value => label) or an SQL query;
 * for a query, $valueCol / $labelCol name the option value and label columns.
 */
class SelectFilter implements IGridFilter
{
    /** Pre-fetched result for a query source; Grid fills it via AsyncQuery when a DBPool is available. */
    public $rs = null;

    public function __construct(
        public array|string $source,
        public string $valueCol = '',
        public string $labelCol = '',
        public string $selectText = 'All'
    ) {
    }

    public function Render(string $name, array $filterdata): string
    {
        $v = DataFormat::DoSecure($filterdata[$name] ?? '');

        if (is_array($this->source)) {
            $options = '<option value="">' . $this->selectText . '</option>' . UI::ArrayToDropDown($this->source, $v);
        } else {
            $this->rs ??= $GLOBALS['db']->Execute($this->source);
            $options = UI::RecordSetToDropDown($this->rs, $v, $this->valueCol, $this->labelCol, true, $this->selectText);
        }

        return HTML::InputSelect($name, $options, $name, false, 'filter-textbox');
    }
}
