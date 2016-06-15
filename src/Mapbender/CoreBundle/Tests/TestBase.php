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

    public static function setUpBeforeClass()
    {
        self::$options     = $options = array();
        self::$client      = $client = static::createClient($options);
        self::$kernel      = $kernel = $client->getKernel();
        self::$application = $application = new CmdApplication($kernel);

        $application->setAutoExit(false);
    }

    public function setUp()
    {
        static $kernel = null;

        if ($kernel) {
            return;
        }

        $kernel      = $this->getContainer()->get("kernel");
        $appRootPath = $kernel->getRootDir();
        $configPath  = $appRootPath . "/config/";
        $configurationBaseFile = $configPath . "parameters.yml.dist";
        $configurationFile = $configPath . "parameters.yml";
        $isNewInstall = !file_exists($configurationFile);
        $isTestEnv = $kernel->getEnvironment() == "test";

        if ($isNewInstall) {
            \ComposerBootstrap::allowWriteLogs();
            copy($configurationBaseFile, $configurationFile);
            \ComposerBootstrap::clearCache();
        }


        $connection = $this->getConnection();
        $params      = $connection->getParams();
        $hasDatabase = file_exists($params["path"]);
        if($params["driver"] == "pdo_sqlite" && !$hasDatabase){
            //$this->runCommand('doctrine:database:drop --force');
            $this->runCommand('doctrine:database:create');
            $this->runCommand('doctrine:schema:create');
            $this->runCommand('fom:user:resetroot --username=root --password=root --email=root@example.com --silent');
            $this->runCommand('doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/ --append');
            $this->runCommand('doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Application/ --append');
        }
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
    protected function runCommand($command)
    {
        $command     = sprintf('%s --quiet', $command);
        $application = $this->getApplication();
        return $application->run(new StringInput($command));
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
    private function getConnection()
    {
        return $this->getDoctrine()->getConnection();
    }

    /**
     * @return Registry
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}