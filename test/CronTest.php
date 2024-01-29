<?php

namespace TAS\Core\Test;

use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    public $script = 'TestScript';

    public function testCreateScriptLockSuccess()
    {
        $output = \TAS\Core\Cron::CreateScriptLock($this->script);
        $this->assertEquals(true, $output);
    }

    public function testCreateScriptLockFailure()
    {
        $output = \TAS\Core\Cron::CreateScriptLock(null);
        $this->assertEquals(false, $output);

        $output = \TAS\Core\Cron::CreateScriptLock('');
        $this->assertEquals(false, $output);
    }

    public function testUnlockScriptSuccess()
    {
        $output = \TAS\Core\Cron::UnlockScript($this->script);
        $this->assertEquals(true, $output);
    }

    public function testUnlockScriptFailure()
    {
        $output = \TAS\Core\Cron::UnlockScript('ABCD');
        $this->assertEquals(false, $output);
    }
}
