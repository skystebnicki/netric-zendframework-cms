<?php

namespace NetricTest\Models\DataMapper;

use PHPUnit_Framework_TestCase;
use Netric\Models\EntityFactory;
use Netric\Models\DataMapper\ApiDataMapper;

class ApiDataMapperTest extends PHPUnit_Framework_TestCase
{
	public function testFetchById()
	{
		$dm = new ApiDataMapper("localhost", "administrator", "Password1");
        $dm->https = false;
        
        // Save a test object
        $obj = EntityFactory::factory("customer");
        $obj->setValue("name", "Netric CMS Unit Test");
        $cid = $dm->save($obj);
        $this->assertEquals($obj->getId(), $cid);
        
        // Get the mock object: 1 is in the test mapper
        $obj = $dm->fetchById("customer", $cid);
        
        // Check if the object is in memory now
		$refIm = new \ReflectionObject($obj);
        $propId = $refIm->getProperty("id");
		$propId->setAccessible(true);
        
        $this->assertEquals($cid, $propId->getValue($obj));
	}
    
    /**
     * Make sure we can get a remote definition
     */
    public function testGetDefinition()
	{
		$dm = new ApiDataMapper("localhost", "administrator", "Password1");
        $dm->https = false;
        
        // Get the mock object: 1 is in the test mapper
        $def = $dm->getDefinition("cms_page");
        
        $field = $def->getField("name");
        $this->assertEquals("text", $field['type']);

		$this->assertEquals("parent_id", $def->getParentField());
	}
    
    /**
     * Test API for get groupings
     */
    public function testGetGroupings()
	{
        $dm = new ApiDataMapper("localhost", "administrator", "Password1");
        $dm->https = false;
        
        $grps = $dm->getGroupings("project_story", "status_id");
        $this->assertTrue(count($grps)>1);
    }

	/**
	 * Test create groupings
	 */
	public function testCreateGrouping()
	{
		 $dm = new ApiDataMapper("localhost", "administrator", "Password1");
        $dm->https = false;
        
        $grps = $dm->createGrouping("customer", "groups", "API TEST");
        $this->assertTrue(isset($grps->id));

	}


	/**
	 * Test timestamp escape/unescape
	 */
	public function testTimestampEscape()
	{
		$dm = new ApiDataMapper("localhost", "administrator", "Password1");
        $dm->https = false;

		$lastContacted = time();
        
        // Save a test object
        $obj = EntityFactory::factory("customer");
        $obj->setValue("name", "Netric CMS Unit Test");
        $obj->setValue("last_contacted", $lastContacted);
        $cid = $dm->save($obj);
        
        // Get the newly created customer
        $obj = $dm->fetchById("customer", $cid);

		// Check if the timestamp was escaped and unescaped correctly
		$this->assertEquals($lastContacted, $obj->getValue("last_contacted"));
	}

	/**
	 * Test addMultiValue
	 */
	public function testAddMultiValue()
	{
		$dm = new ApiDataMapper("localhost", "administrator", "Password1");
        $dm->https = false;

		$lastContacted = time();
        
        // Save a test object
        $obj = EntityFactory::factory("customer");
        $obj->setValue("name", "Netric CMS Unit Test");

		// Create some test groups
        $grp = $dm->createGrouping("customer", "groups", "API TEST");
        $grp2 = $dm->createGrouping("customer", "groups", "API TEST 2");

		// Test inserting fkey_multi
        $obj->addMultiValue("groups", $grp->id, $grp->title);
        $obj->addMultiValue("groups", $grp2->id, $grp2->title);

		$dm->debug = true;
        $cid = $dm->save($obj);
        
        // Get the newly created customer
		$dm->debug = true;
        $obj = $dm->fetchById("customer", $cid);

		// Check if the timestamp was escaped and unescaped correctly
		$this->assertEquals(2, count($obj->getValue("groups")));
		//$this->assertEquals($grp->id, ($obj->getValue("groups"))[0]);
	}
}
