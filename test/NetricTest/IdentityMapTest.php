<?php

namespace NetricTest\Models;

use PHPUnit_Framework_TestCase;
use Netric\Models\IdentityMap;
use Netric\Models\EntityFactory;
use Netric\Models\EntityDefinition;

class IdentityMapTest extends PHPUnit_Framework_TestCase
{
	public function testGetEntity()
	{
		$dm = new \Netric\Models\DataMapper\TestDataMapper();
		$im = new IdentityMap($dm);

		// Get the mock object: 1 is in the test mapper
		$obj = $im->get("customer", 1);
		$this->assertEquals(1, $obj->getId());
		$this->assertInstanceOf("Netric\Models\Entity\Customer", $obj);

		// Check if the object is in memory now
		$refIm = new \ReflectionObject($im);
		$mthIsLoaded = $refIm->getMethod("isLoaded");
		$mthIsLoaded->setAccessible(true);
		$this->assertTrue($mthIsLoaded->invoke($im, "customer:1"));
	}

	/**
	 * Test retrieving an object by the uname path like my/first/page
	 */
	public function testGetByUnamePath()
	{
		$dm = new \Netric\Models\DataMapper\ElasticDataMapper("localhost", "utest_dm");
		$im = new IdentityMap($dm);
        
        // Create a new object for testing
        $def = new EntityDefinition("utest_hier_type");
        $def->setField("uname", array(
            'name' => "uname",
            'title' => "Name Field",
            'type' => "text",
            'subtype' => "256",
        ));
        $def->setField("parent", array(
            'name' => "parent",
            'title' => "Parent",
            'type' => "text",
            'subtype' => "256",
        ));
		$def->setParentField("parent");
        $ret = $dm->saveDefinition($def);
        
        // Save a test object
        $obj = EntityFactory::factory("utest_hier_type");
        $obj->setValue("id", 1);
        $obj->setValue("uname", "root");
        $dm->save($obj);

		// Save second test object and make it a child
        $obj = EntityFactory::factory("utest_hier_type");
        $obj->setValue("id", 2);
        $obj->setValue("parent", 1); // set to 'root' above
        $obj->setValue("uname", "child");
        $dm->save($obj);
        
        // Get the mock object from the ideneity mapper
        $obj = $im->getByUnamePath("utest_hier_type", "root/child");
        $this->assertEquals(2, $obj->getId());

        // Get the mock object from the ideneity mapper
        $obj = $im->getByUnamePath("utest_hier_type", "root");
        $this->assertEquals(1, $obj->getId());
	}
}
