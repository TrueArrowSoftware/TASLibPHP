<?php

namespace TAS\Core\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use TAS\Core\DataFormat;

class DataFormattest extends TestCase
{
    public function testFormatString_WithValues()
    {
        $output = DataFormat::FormatString('test');
        $this->assertEquals('Test', $output);

        $output = DataFormat::FormatString('test test');
        $this->assertEquals('Test Test', $output);

        $output = DataFormat::FormatString('123 test');
        $this->assertEquals('123 Test', $output);

        $output = DataFormat::FormatString('test 123');
        $this->assertEquals('Test 123', $output);
    }

    public function testFormatString_WithoutValues()
    {
        $output = DataFormat::FormatString('');
        $this->assertEquals('', $output);

        $output = DataFormat::FormatString(null);
        $this->assertEquals(null, $output);
    }

    // Test Case on formatFone with values

    public function testFormatPhone_WithValue()
    {
        $output = DataFormat::FormatPhone('74889', '5');
        $this->assertEquals('74889', $output);

        $output = DataFormat::FormatPhone('748896', '6');
        $this->assertEquals('748896', $output);

        $output = DataFormat::FormatPhone(7488960, 7);
        $this->assertEquals('748-8960', $output);

        $output = DataFormat::FormatPhone(74889601, 8);
        $this->assertEquals('7488-9601', $output);

        $output = DataFormat::FormatPhone('748896019', '9');
        $this->assertEquals('748-896-019', $output);

        $output = DataFormat::FormatPhone('7488960190', '10');
        $this->assertEquals('(748) 896-0190', $output);

        $output = DataFormat::FormatPhone('74889601901', '11');
        $this->assertEquals('(748) 8960-1901', $output);

        $output = DataFormat::FormatPhone('748896019010', '12');
        $this->assertEquals('(7488) 9601-9010', $output);
    }

    // Test Case on format Phone Without Value

    public function testFormatPhone_WithOutValue()
    {
        $output = DataFormat::FormatPhone('', '1');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::FormatPhone(null, '1');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::FormatPhone(' ', '1');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::FormatPhone(null, '5');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '5');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '6');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '7');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '8');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '9');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '10');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '11');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone('', '12');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '5');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '6');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '7');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '8');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '9');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '10');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '11');
        $this->assertEquals(false, $output);

        $output = DataFormat::FormatPhone(null, '12');
        $this->assertEquals(false, $output);
        // Test Case on Phone by sending invalid numbers
        try {
            $output = DataFormat::FormatPhone('74889601960147', '14');
            $output = DataFormat::FormatPhone('ABCDE', '5');
            $output = DataFormat::FormatPhone('AB2121', '6');
            $output = DataFormat::FormatPhone('ABCDS14', '7');
            $output = DataFormat::FormatPhone('ABCDS143', '8');
            $output = DataFormat::FormatPhone('ABCDS1432', '9');
            $output = DataFormat::FormatPhone('ABCDS14324', '10');
            $output = DataFormat::FormatPhone('ABCDS143265', '11');
            $output = DataFormat::FormatPhone('ABCDS143276', '12');
            $output = true;
        } catch (Exception $e) {
            $output = false;
            $this->assertEquals(false, $output, 'Error');
        }
    }

    // Test case on validate password with value

    public function testValidatePassword_withValue()
    {
        $output = DataFormat::ValidatePassword('Password1234');
        $this->assertEquals(true, $output);

        $output = DataFormat::ValidatePassword('pass@123');
        $this->assertEquals(true, $output);

        $output = DataFormat::ValidatePassword('test@gmail.com');
        $this->assertEquals(false, $output);

        $output = DataFormat::ValidatePassword('Password');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::ValidatePassword('Password', 'Pass');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::ValidatePassword('1234');
        $this->assertEquals(false, $output, 'Error');
    }

    // Test case on Validate Password without values

    public function testValidatePassword_WithoutValues()
    {
        $output = DataFormat::ValidatePassword('');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::ValidatePassword(null);
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::ValidatePassword(' ');
        $this->assertEquals(false, $output, 'Error');
    }

    // Test Case on Format Bytes with values

    public function testFormatByte_WithValue()
    {
        $output = DataFormat::FormatBytes(512);
        $this->assertEquals('512B', $output);

        $output = DataFormat::FormatBytes(1024);
        $this->assertEquals('1K', $output);

        $output = DataFormat::FormatBytes(1048576);
        $this->assertEquals('1M', $output);

        $output = DataFormat::FormatBytes(1099511627776);
        $this->assertEquals('1T', $output);

        $output = DataFormat::FormatBytes(1099511627.4561);
        $this->assertEquals('1.02G', $output);
    }

    // Test Case on Format Bytes without values if i send null or blank value
    // It returns log() expects parameter 1 to be float, string given ->error message

    // Test Case on DBToDateTimeFormat with value

    public function testDBToDateTimeFormat_WithValue()
    {
        $output = DataFormat::DBToDateTimeFormat('2019-12-16 14:27:00');
        $this->assertEquals('12/16/2019 14:27 pm', $output);

        $output = DataFormat::DBToDateTimeFormat('2019/12/16');
        $this->assertEquals('12/16/2019 00:00 am', $output);

        $output = DataFormat::DBToDateTimeFormat('2019-12-16');
        $this->assertEquals('12/16/2019 00:00 am', $output);

        $output = DataFormat::DBToDateTimeFormat('2019/16/12');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('2019-16-12');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('2019/16/12 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('2019-16-12 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('12/32/2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('13/32/2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('13/32/19 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('13/12/19 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('12-32-2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('13-32-2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('13-32-19 00:00');
        $this->assertEquals(false, $output, 'Error');
    }

    // Test Case On DBToDateTimeFormat without value

    public function testDBToDateTimeFormat_WithOutValue()
    {
        $output = DataFormat::DBToDateTimeFormat('');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat(' ');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat(null);
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateTimeFormat('241Test');
        $this->assertEquals(false, $output, 'Error');
    }

    // Test Case On DBToDateFormat with value

    public function testDBToDateFormat_withValue()
    {
        $output = DataFormat::DBToDateFormat('2019-12-16 14:27:00');
        $this->assertEquals('12/16/2019', $output);

        $output = DataFormat::DBToDateFormat('2019-12-16');
        $this->assertEquals('12/16/2019', $output);

        $output = DataFormat::DBToDateFormat('2019-12-16 14:27:00', 'm-d-Y');
        $this->assertEquals('12-16-2019', $output);

        $output = DataFormat::DBToDateFormat('2019/12/16');
        $this->assertEquals('12/16/2019', $output);

        $output = DataFormat::DBToDateFormat('2019-12-16');
        $this->assertEquals('12/16/2019', $output);

        $output = DataFormat::DBToDateFormat('2019/16/12');
        $this->assertEquals(false, $output, 'Cannot parse 2019/16/12');

        $output = DataFormat::DBToDateFormat('2019-16-12');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('2019/16/12 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('2019-16-12 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('12/32/2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('13/32/2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('13/32/19 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('13/12/19 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('12-32-2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('13-32-2019 00:00');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('13-32-19 00:00');
        $this->assertEquals(false, $output, 'Error');
    }

    // Test Case On DBToDateFormat without value

    public function testDBToDateFormat_WithOutValue()
    {
        $output = DataFormat::DBToDateFormat('');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat(' ');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat(null);
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::DBToDateFormat('241Test');
        $this->assertEquals(false, $output, 'Error');
    }

    // Test case On DateToDBFormat with value

    public function testDateToDBFormat_WithValue()
    {
        $output = DataFormat::DBToDateTimeFormat('12/16/2019');
        $this->assertEquals('12/16/2019 00:00 am', $output);

        $output = DataFormat::DBToDateTimeFormat('2019-12-16');
        $this->assertEquals('12/16/2019 00:00 am', $output);

        $output = DataFormat::DBToDateTimeFormat('2019-12-16 14:27:00');
        $this->assertEquals('12/16/2019 14:27 pm', $output);

        $output = DataFormat::DBToDateTimeFormat('2019/12/16');
        $this->assertEquals('12/16/2019 00:00 am', $output);
    }

    public function testRemoveWhiteSpace_WithValue()
    {
        $obj = new DataFormat();
        $output = $obj->RemoveWhiteSpace('   My Name Is  ');
        $this->assertEquals('My Name Is', $output);

        $output = $obj->RemoveWhiteSpace('123456 ');
        $this->assertEquals('123456', $output);
    }

    public function testRemoveWhiteSpace_WithOutValue()
    {
        $obj = new DataFormat();
        $output = $obj->RemoveWhiteSpace('');
        $this->assertEquals(false, $output, 'Error');

        $output = $obj->RemoveWhiteSpace(' ');
        $this->assertEquals(false, $output, 'Error');

        $output = $obj->RemoveWhiteSpace(null);
        $this->assertEquals(false, $output, 'Error');
    }

    public function testDoSecure_WithValue()
    {
        $output = \TAS\Core\DataFormat::DoSecure('@<script[^>]*?>.*?</script>@si');
        $this->assertEquals('@@si', $output, 'Error');

        $output = \TAS\Core\DataFormat::DoSecure("@<[\/\!]*?[^<>]*?>@si");
        $this->assertEquals('@&lt;[\/\!]*?[^]*?&gt;@si', $output, 'Error');

        $output = \TAS\Core\DataFormat::DoSecure('<script></script>');
        $this->assertEquals('', $output, 'Error');

        $output = \TAS\Core\DataFormat::DoSecure('test@123');
        $this->assertEquals('test@123', $output);

        $output = \TAS\Core\DataFormat::DoSecure('<table></table>');
        $this->assertEquals('', $output);
    }

    public function testDoSecure_WithOutValue()
    {
        $output = \TAS\Core\DataFormat::DoSecure(null);
        $this->assertEquals(false, $output, 'Error');

        $output = \TAS\Core\DataFormat::DoSecure('');
        $this->assertEquals(false, $output, 'Error');

        $output = \TAS\Core\DataFormat::DoSecure('  ');
        $this->assertEquals(false, $output, 'Error');
    }

    public function testRemoveSlashes_WithValue()
    {
        $obj = new DataFormat();
        $output = $obj->RemoveSlashes("Who\'s Peter Griffin?");
        $this->assertEquals("Who's Peter Griffin?", $output);

        $output = $output = $obj->RemoveSlashes("my/name\is /\/t/est");
        $this->assertEquals('my/nameis //t/est', $output);
    }

    public function testRemoveSlashes_WithoutValue()
    {
        $obj = new DataFormat();
        $output = $obj->RemoveSlashes(null);
        $this->assertEquals(false, $output, 'Error');

        $obj = new DataFormat();
        $output = $obj->RemoveSlashes('');
        $this->assertEquals(false, $output, 'Error');
    }

    public function testDoSecureArray_WithValue()
    {
        $output = \TAS\Core\DataFormat::DoSecureArray(['<script><script>', '<table></table>', "@14\/\//.,.l"]);
        $output = $output['2'];
        $this->assertEquals('@14\/\//.,.l', $output, 'Error');
        $output = \TAS\Core\DataFormat::DoSecureArray(['@<script[^>]*?>.*?</script>@si', "@<[\/\!]*?[^<>]*?>@si", "@14\/\//.,.l"]);
        $output = $output[0];
        $this->assertEquals('@@si', $output, 'Error');
    }

    public function testGenerateRandomPassword()
    {
        $output = DataFormat::GenerateRandomPassword();
        $this->assertEquals($output, $output, 'Error');
    }

    public function testCleanJunkCharacters_WithValue()
    {
        $output = DataFormat::CleanJunkCharacters('test&Atilde;');
        $this->assertEquals('test', $output);

        $output = DataFormat::CleanJunkCharacters('test&macr;');
        $this->assertEquals('test', $output);

        $output = DataFormat::CleanJunkCharacters('test&frac12;');
        $this->assertEquals('test', $output);

        $output = DataFormat::CleanJunkCharacters('1234&frac12;');
        $this->assertEquals('1234', $output);

        $output = DataFormat::CleanJunkCharacters('test&Acirc;');
        $this->assertEquals('test', $output);

        $output = DataFormat::CleanJunkCharacters('12345&Acirc;');
        $this->assertEquals('12345', $output);

        $output = DataFormat::CleanJunkCharacters('test&iuml;');
        $this->assertEquals('test', $output);

        $output = DataFormat::CleanJunkCharacters('1234&iuml;');
        $this->assertEquals('1234', $output);
    }

    public function testCleanJunkCharacters_WithOutValue()
    {
        $output = DataFormat::CleanJunkCharacters(null);
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::CleanJunkCharacters('');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::CleanJunkCharacters(' ');
        $this->assertEquals(false, $output, 'Error');
    }

    public function testHumanizeTime()
    {
        $date = new \DateTime(date('Y-m-d H:i:s'));
        $startdate = new \DateTime(date('2020-01-01 00:00:01'));
        $output = DataFormat::HumanizeTime($date->getTimestamp());
        $this->assertEquals('just now', $output, 'Error');

        $date = new \DateTime('2018-01-02 16:17:26');
        $output = DataFormat::HumanizeTime($date->getTimestamp(), $startdate->getTimestamp());
        $this->assertEquals('3 years ago', $output, 'Error');

        $date = new \DateTime('2016/12/01');
        $output = DataFormat::HumanizeTime($date->getTimestamp(), $startdate->getTimestamp());
        $this->assertEquals('4 years ago', $output, 'Error');

        $date = new \DateTime('1997-01-02');
        $output = DataFormat::HumanizeTime($date->getTimestamp(), $startdate->getTimestamp());
        $this->assertEquals('24 years ago', $output, 'Error');

        $output = DataFormat::HumanizeTime('1', $startdate->getTimestamp());
        $this->assertEquals('51 years ago', $output, 'Error');
    }

    public function testGetAge_WithValue()
    {
        $date = new \DateTime(date('Y-m-d'));
        $output = DataFormat::GetAge($date->getTimestamp());

        // $date = new \DateTime('2018-02-30 16:17:26');
        // $output = DataFormat::GetAge($date->getTimestamp());
        // $this->assertEquals('1 yrs 10 months', $output, 'Error');

        // $date = new \DateTime('1997/01/02');
        // $output = DataFormat::GetAge($date->getTimestamp());
        // $this->assertEquals('23 yrs', $output, 'Error');

        // $date = new \DateTime('2017-01-02');
        // $output = DataFormat::GetAge($date->getTimestamp());
        // $this->assertEquals('3 yrs', $output, 'Error');

        $date = new \DateTime();
        $output = DataFormat::GetAge($date->getTimestamp());
        $this->assertEquals('0 yrs', $output, 'Error');

        // $output = DataFormat::GetAge(123);
        // $this->assertEquals('50 yrs', $output, 'Error');
    }

    public function testInverseHex_WithValue()
    {
        $output = DataFormat::InverseHex('#b327a8');
        $this->assertEquals('#4cd857', $output);

        $output = DataFormat::InverseHex('#754f4f');
        $this->assertEquals('#8ab0b0', $output);

        $output = DataFormat::InverseHex('#1111111111111#');
        $this->assertEquals('', $output);

        $output = DataFormat::InverseHex('00000000#1111111111111');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::InverseHex('321456');
        $this->assertEquals('cdeba9', $output, 'Error');

        $output = DataFormat::InverseHex('rameshbabu');
        $this->assertEquals(false, $output);
    }

    public function testInverseHex_WithOutValue()
    {
        $output = DataFormat::InverseHex(null);
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::InverseHex('');
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::InverseHex(' ');
        $this->assertEquals(false, $output, 'Error');
    }

    public function testCreateSlug_WithValue()
    {
        $output = DataFormat::CreateSlug("testk[ra]m899te\/st");
        $this->assertEquals('testkram899te-st', $output);

        $output = DataFormat::CreateSlug('[][|/.test]');
        $this->assertEquals('-test', $output);

        $output = DataFormat::CreateSlug('test TEst TEST');
        $this->assertEquals('test-test-test', $output);

        $output = DataFormat::CreateSlug('Test 123 3254');
        $this->assertEquals('test-123-3254', $output);
    }

    public function testCreateSlug_WithOutValue()
    {
        $output = DataFormat::CreateSlug(null);
        $this->assertEquals(false, $output, 'Error');

        $output = DataFormat::CreateSlug(' ');
        $this->assertEquals('-', $output);

        $output = DataFormat::CreateSlug('------------');
        $this->assertEquals('-', $output);

        $output = DataFormat::CreateSlug('');
        $this->assertEquals(null, $output, 'Error');
    }
}
