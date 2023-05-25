<?php

namespace TAS\Core\UI;

interface IGridUI
{
    public function GetBeforeTable();

    public function GetTableStart(string $id, string $CssClass, string $dataUrlPage);

    public function GetAtTableHeadStart();

    public function GetAtTableHeader();

    public function GetAtTableHeadEnd();

    public function GetCssClassForSortingIcon();

    public function GetAtTableBody();

    public function GetAtTableFooter();

    public function GetTableEnd();

    public function GetAfterTable();

    public function GetNoRecordFound(string $norecordtext);
}
