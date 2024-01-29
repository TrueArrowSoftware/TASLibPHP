<?php

namespace TAS\Core\Test;

use PHPUnit\Framework\TestCase;
use TAS\Core\HTML;

class HTMLtest extends TestCase
{
    public function testInputBox_AllDefault()
    {
        $output = HTML::InputBox('test');
        $expected = '<input type="text" id="test" name="test" class="form-control" size="30" maxlength="50" value=""  />';
        $this->assertEquals($expected, $output);
    }

    public function testInputBox_AllDefault_ButRequired()
    {
        $output = HTML::InputBox('test', '', '', true);
        $expected = '<input type="text" id="test" name="test" class="form-control required" size="30" maxlength="50" value=""  />';
        $this->assertEquals($expected, $output);
    }
}
