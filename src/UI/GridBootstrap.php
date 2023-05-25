<?php

namespace TAS\Core\UI;

class GridBootstrap implements IGridUI
{
    public function GetBeforeTable()
    {
        return '<section class="content-section">
        <div class="container-fluid">
            <div class="row">
                <div class="content-area col-md-12 px-0">
                <div class="col-lg-12 col-md-12 px-0">
                <div class="card">
                    <div class="card-body">';
    }

    public function GetTableStart(string $id, string $CssClass, string $dataUrlPage)
    {
        return '<div class="table-responsive">
            <table class="table table-striped '.$CssClass.'" data-url="'.$dataUrlPage.'" id="'.$id.'">';
    }

    public function GetAtTableHeadStart()
    {
        return '<thead><tr>';
    }

    public function GetAtTableHeader()
    {
        return '';
    }

    public function GetAtTableHeadEnd()
    {
        return '</tr></thead>';
    }

    public function GetCssClassForSortingIcon()
    {
        return 'ui-state-default ui-icon-gap ui-corner-all';
    }

    public function GetAtTableBody()
    {
        return '';
    }

    public function GetAtTableFooter()
    {
        return '';
    }

    public function GetTableEnd()
    {
        return '</table></div>';
    }

    public function GetAfterTable()
    {
        return '
        </div>
    </div>
</div>
</div>
</div>
</section>';
    }

    public function GetNoRecordFound(string $norecordtext) {
        return  '<section class="content-section">
        <div class="container-fluid">
            <div class="row">
                <div class="content-area col-md-12 px-0">
                <div class="col-lg-12 col-md-12 px-0">
                  <div class="card">
                    <div class="card-body"><h6> '.$norecordtext.' </h6>
                    </div>
                  </div>
                </div>
            </div>
          </div>
         </div>
      </section>';
    }
}
