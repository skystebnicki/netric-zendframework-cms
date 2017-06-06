<?php
/**
 * Test the services provided by this module
 */
namespace NetricTest\Models;

use PHPUnit_Framework_TestCase;
use Netric\Models\IdentityMap;

class ServicesTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var ServiceManager
     */
    private $sm = null;
    
    public function setUp()
    {
        $this->sm = \NetricTest\Bootstrap::getServiceManager();
    }
    
    /**
     * Test the get entity loader service
     */
	public function testNetricApi()
	{
        $sm = \NetricTest\Bootstrap::getServiceManager();
        $this->assertInstanceOf("Zend\ServiceManager\ServiceManager", $sm);
        
        $loader = $sm->get("NetricApi");
        $this->assertInstanceOf("NetricSDK\NetricApi", $loader);
    }
}