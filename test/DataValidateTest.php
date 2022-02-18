<?php

namespace TAS\Core\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use TAS\Core\DataValidate;

class DataValidateTest extends TestCase
{
    public function testValidatePhoneFormatSuccess()
    {
        $output=DataValidate::ValidatePhoneFormat(7488962541);
        $this->assertEquals(true,$output);

        $output=DataValidate::ValidatePhoneFormat(1234567890);
        $this->assertEquals(true,$output);
    }

    public function testValidatePhoneFormatFailure()
    {
        $output=DataValidate::ValidatePhoneFormat("748896ABCD");
        $this->assertEquals(False,$output,"Error");

        $output=DataValidate::ValidatePhoneFormat(null);
        $this->assertEquals(False,$output,"Error");

        $output=DataValidate::ValidatePhoneFormat('');
        $this->assertEquals(False,$output,"Error");
    }

    public function testValidateEmailSuccess()
    {
        $output=DataValidate::ValidateEmail("test@gmail.com");
        $this->assertEquals(true,$output);

        $output=DataValidate::ValidateEmail("12345@gmail.com");
        $this->assertEquals(true,$output);
    }

    public function testValidateEmailFailure()
    {
        $output=DataValidate::ValidateEmail("@gmail.com");
        $this->assertEquals(false,$output);

        $output=DataValidate::ValidateEmail("test");
        $this->assertEquals(false,$output);

        $output=DataValidate::ValidateEmail("748896@ABCD");
        $this->assertEquals(false,$output);
    }

    public function testValidateUrlSuccess()
    {
        $output=DataValidate::ValidateUrl("http://example.com");
        $this->assertEquals(true,$output);

    }

    public function testValidateUrlFailure()
    {
        $output=DataValidate::ValidateUrl("example.com");
        $this->assertEquals(false,$output);

        $output=DataValidate::ValidateUrl("test");
        $this->assertEquals(false,$output);

        $output=DataValidate::ValidateUrl(null);
        $this->assertEquals(false,$output);

        $output=DataValidate::ValidateUrl(' ');
        $this->assertEquals(false,$output);
    }

    public function testValidateIpSuccess()
    {
        $output=DataValidate::ValidateIP('192.168.1.197');
        $this->assertEquals(true,$output);

        $output=DataValidate::ValidateIP('192.168.1.141');
        $this->assertEquals(true,$output);
    }

    public function testValidateIpFailure()
    {
        $output=DataValidate::ValidateIP('http://192.168.1.197');
        $this->assertEquals(false,$output,"Error");

        $output=DataValidate::ValidateIP('facebook.com');
        $this->assertEquals(false,$output,"Error");

        $output=DataValidate::ValidateIP(null);
        $this->assertEquals(false,$output,"Error");

        $output=DataValidate::ValidateIP(' ');
        $this->assertEquals(false,$output,"Error");

        $output=DataValidate::ValidateIP('');
        $this->assertEquals(false,$output,"Error");
    }

    public function testIsdateSuccess()
    {
        $output=DataValidate::IsDate("16-08-2017");
        $this->assertEquals(true,$output); 

        $output=DataValidate::IsDate("2019-12-16 14:27:00");
        $this->assertEquals(true,$output); 

        $output=DataValidate::IsDate("2019-12-16");
        $this->assertEquals(true,$output);

        $output=DataValidate::IsDate("08/16/2015");
        $this->assertEquals(true,$output,"Error");
    }

    public function testIsDateFailure()
    {
        $output=DataValidate::IsDate("21/08/2015");
        $this->assertEquals(false,$output,"Error");
        
        $output=DataValidate::IsDate("2019-18-16");
        $this->assertEquals(false,$output,"Error");

        $output=DataValidate::IsDate("45");
        $this->assertEquals(false,$output,"Error");

    }
}
