<?php
/**
 * Test the services provided by this module
 */
namespace NetricZendTest\Models;

use NetricZend\Search\Searcher;
use NetricZend\Navigation\CmsNavigation;
use NetricSDK\NetricApi;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

class ServicesTest extends TestCase
{
    /**
     * Instantiated service manager
     *
     * @var ServiceManager
     */
    private $serviceManager = null;
    
    public function setUp()
    {
        $config = require(__DIR__ . '/../../config/module.config.php');
        $this->serviceManager = new ServiceManager(new ServiceManagerConfig($config['service_manager']));
        $this->serviceManager->setService('Config', $config);
    }
    
    /**
     * Test the get entity loader service
     */
	public function testNetricApi()
	{
        $svc = $this->serviceManager->get("NetricApi");
        $this->assertInstanceOf(NetricApi::class, $svc);
    }

    /**
     * Test the get entity loader service
     */
    public function testNetricSearcher()
    {
        $svc = $this->serviceManager->get("NetricSearcher");
        $this->assertInstanceOf(Searcher::class, $svc);
    }

    /**
     * Test the get entity loader service
     *
     * TODO: Cannot run this because it actually tries to make an API call
     * so we need to setup test data in a devel instance to make it work.
     *
    public function testCmsNavigation()
    {
        $svc = $this->serviceManager->get("CmsNavigation");
        $this->assertInstanceOf(CmsNavigation::class, $svc);
    }*/
}