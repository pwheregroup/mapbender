<?php

namespace Mapbender\CoreBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Mapbender\CoreBundle\Mapbender;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application as CmdApplication;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;

/**
 * Class ApplicationTest
 *
 * @package Mapbender\CoreBundle\Tests
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class TestBase extends WebTestCase
{
    /** @var array options */
    public static $options;

    /** @var Client */
    protected static $client;

    /** @var CmdApplication Command application */
    protected static $application;

    /** @var Registry */
    protected static $doctrine;

    /** @var  Connection */
    protected static $connection;

    /**
     * The setUpBeforeClass() method
     * called before the first test
     * of the test case class is run respectively.
     */
    public static function setUpBeforeClass()
    {
        self::$options     = $options = array();
        self::$client      = $client = static::createClient($options);
        self::$kernel      = $kernel = $client->getKernel();
        self::$application = $application = new CmdApplication($kernel);
        self::$doctrine    = $doctrine = $client->getContainer()->get('doctrine');
        self::$connection  = $connection = $doctrine->getConnection();

        $appRootPath           = $kernel->getRootDir();
        $configPath            = $appRootPath . "/config/";
        $configurationBaseFile = $configPath . "parameters.yml.dist";
        $configurationFile     = $configPath . "parameters.yml";
        $isNewInstall          = !file_exists($configurationFile);
        $isTestEnv             = $kernel->getEnvironment() == "test";

        $application->setAutoExit(false);

        if ($isNewInstall) {
            \ComposerBootstrap::allowWriteLogs();
            copy($configurationBaseFile, $configurationFile);
            \ComposerBootstrap::clearCache();
        }

        $params      = $connection->getParams();
        $hasDatabase = file_exists($params["path"]);
        if ($params["driver"] == "pdo_sqlite" && !$hasDatabase) {
            self::runCommand('doctrine:database:drop --force');
            self::runCommand('doctrine:database:create');
            self::runCommand('doctrine:schema:create');
            self::runCommand('fom:user:resetroot --username=root --password=root --email=root@example.com --silent');
            self::runCommand('doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/ --append');
            self::runCommand('doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Application/ --append');
        }
    }

    public static function tearDownAfterClass()
    {
    }


    /**
     * Get CMD application
     *
     * @return mixed
     */
    protected function getApplication()
    {
        return self::$application;
    }

    /**
     * @param $command
     * @return mixed
     */
    protected static function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);
        return self::$application->run(new StringInput($command));
    }

    /**
     * @param array $options
     * @return Client
     */
    protected function getClient(array $options = array())
    {
        return self::$client;
    }

    /**
     * @return Mapbender
     */
    protected function getCore()
    {
        return $this->getContainer()->get("mapbender");
    }

    /**
     * @return null|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->getClient()->getContainer();
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return self::$connection;
    }

    /**
     * @return Registry
     */
    protected function getDoctrine()
    {
        return self::$doctrine;
    }

    /**
     * @param  string $method GET|POST etc.
     * @param  string $uri    URI absolute to application
     * @param array   $parameters
     * @param array   $files
     * @param array   $server
     * @param null    $content
     * @param bool    $changeHistory
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    public function requestAndGetResponse($method, $uri, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true)
    {
        $client  = $this->getClient();
        $crawler = $client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        return $client->getResponse()->getContent();
    }
}