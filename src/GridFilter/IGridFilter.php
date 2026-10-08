<?php

namespace TAS\Core\GridFilter;

/**
 * Header filter for a Grid column. Set on a column as 'filter' => new XxxFilter(...).
 * Query building stays in consumer code; filters only render the input.
 */
interface IGridFilter
{
    /**
     * @param string $name        input id/name, "{gridid}-filter-{field}"
     * @param array  $filterdata  Grid 'filterdata' option (posted filter values keyed by input name)
     * @return string HTML placed inside the filter <th>
     */
    public function Render(string $name, array $filterdata): string;
}
