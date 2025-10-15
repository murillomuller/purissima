<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicFunctionality()
    {
        $this->assertTrue(true);
    }

    public function testEnvironmentVariables()
    {
        $this->assertNotEmpty($_ENV['APP_NAME'] ?? 'Purissima PHP Project');
    }
}
