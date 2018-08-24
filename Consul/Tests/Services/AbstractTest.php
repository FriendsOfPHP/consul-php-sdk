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
        $os = PHP_OS === 'Darwin' ? 'mac' : 'linux';
        $dir = __DIR__;
        self::$consul = new Process("exec {$dir}/../../../bin/consul/consul_{$os} agent -dev -bind=127.0.0.1");
        self::$consul->start();

        // This would be really better with https://github.com/symfony/symfony/pull/27742
        while (true) {
            self::$consul->checkTimeout();
            $output = self::$consul->getOutput();

            if (strpos($output, 'Synced node info') !== false) {
                break;
            }
        }
    }

    public static function tearDownAfterClass()
    {
        static::clean();
        self::$consul->stop();
    }

    private static function clean($mkdir = false)
    {
        $dataDir = __DIR__ . '/../../bin/consul/config/data-dir';

        $fs = new Filesystem();
        $fs->remove($dataDir);

        if ($mkdir) {
            $fs->mkdir($dataDir);
        }
    }
}
