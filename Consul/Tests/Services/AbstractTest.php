<?php

namespace SensioLabs\Consul\Tests\Services;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    private static $consul;

    public static function setUpBeforeClass()
    {
        static::clean(true);

        self::$consul = new Process(__DIR__.'/../../../bin/start-consul');
        self::$consul->start();
        usleep(250000);
    }

    public static function tearDownAfterClass()
    {
        static::clean();

        self::$consul->stop(0);
    }

    private static function clean($mkdir = false)
    {
        $dataDir = __DIR__.'/../../../consul-configuration/data-dir';
        $fs = new Filesystem();
        $fs->remove($dataDir);
        if ($mkdir) {
            $fs->mkdir($dataDir);
        }
    }
}
