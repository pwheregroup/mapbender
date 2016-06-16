<?php

namespace Mapbender\CoreBundle\Tests\Controller;

use Symfony\Component\Process\Process;

/**
 * Class SeleniumPhantomJsTest
 */
class SeleniumPhantomJsTest extends \PHPUnit_Extensions_Selenium2TestCase
{
    const PHANTOM_JS_PORT = 9876;
    const PHANTOM_JS_HOST = 'localhost';

    protected static $output;
    /** @var  Process */
    protected static $webServerProcces;
    /** @var  Process */
    protected static $phantomJsProccess;

    public static function setUpBeforeClass()
    {
        /** @var Process $phantom */
        $phantom                = self::$phantomJsProccess = new Process("phantomjs --webdriver=" . self::PHANTOM_JS_PORT);
        self::$webServerProcces = new Process("app/console server:run");
        self::$webServerProcces->start();
        self::$phantomJsProccess->start();
        //$o = array();
        //$o[] = self::exec("app/console server:run");
        //$o[]          = self::exec("phantomjs --webdriver=" . self::PHANTOM_JS_PORT);
        //self::$output = $o;
        // TODO: check if phantom js is running...
    }

    public static function tearDownAfterClass()
    {
        // TODO: stop phantomjs and symfony PHP server:run
        self::$phantomJsProccess->stop(0, SIGKILL);
        self::$webServerProcces->stop();
    }

    /**
     * @param string $cmd
     * @return string
     */
    protected static function exec($cmd)
    {
        return `$cmd &`;
    }

    public function setUp()
    {
        if (PHP_MINOR_VERSION == 3) {
            $this->markTestIncomplete('This test does not run on PHP 5.3.');
            return;
        }
        $this->setHost(self::PHANTOM_JS_HOST);
        $this->setPort(self::PHANTOM_JS_PORT);
        $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
    }

    /**
     * @return \PHPUnit_Extensions_Selenium2TestCase_Session
     */
    public function prepareSession()
    {
        $res = parent::prepareSession();
        $this->url('/');
        return $res;
    }

    /**
     *
     */
    public function testIndex()
    {
        $this->url('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
        $this->assertEquals('Applications', $this->title());
    }
}
