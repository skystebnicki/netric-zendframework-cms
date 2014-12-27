<?php

namespace NetricTest\Models\DataMapper;

use PHPUnit_Framework_TestCase;
use Netric\Models\EntityFactory;
use Netric\Models\EntityGrouping;
use Netric\Models\DataMapper\ElasticDataMapper;
use Netric\Models\DataMapper\ApiDataMapper;
use Netric\Models\EntityDefinition;

class ElasticDataMapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test opening stored entity
     */
	public function testFetchById()
	{
		$dm = new ElasticDataMapper("localhost", "utest_dm");
        
        // Create a new object for testing
        $def = new EntityDefinition("utest_obj_type");
        $data = array(
            'name' => "name",
            'title' => "Name Field",
            'type' => "text",
            'subtype' => "256",
        );
        $def->setField("name", $data);
        $ret = $dm->saveDefinition($def);
        
        // Save a test object
        $obj = EntityFactory::factory("utest_obj_type");
        $obj->setValue("id", 1);
        $obj->setValue("name", "Unit Test");
        $dm->save($obj);
        
        // Get the mock object: 1 is in the test mapper
        $obj = $dm->fetchById("utest_obj_type", 1);
        
        // Check if the object is in memory now
		$refIm = new \ReflectionObject($obj);
        $propId = $refIm->getProperty("id");
		$propId->setAccessible(true);
        
        $this->assertEquals(1, $propId->getValue($obj));
        $this->assertEquals("Unit Test", $obj->getValue("name"));
	}
    
    /**
     * Test save definition
     */
    public function testSaveDefinition()
    {
        // Create a tester definition
        $def = new EntityDefinition("utest_obj_type");
        $data = array(
            'name' => "name",
            'title' => "Name Field",
            'type' => "text",
            'subtype' => "256",
        );
        $def->setField("name", $data);
        $data = array(
            'name' => "parent",
            'title' => "Parent",
            'type' => "text",
            'subtype' => "256",
        );
        $def->setField("parent", $data);
		$def->setParentField("parent");
        
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        $ret = $dm->saveDefinition($def);
        $this->assertTrue($ret);
    }
    
    /**
     * Test get definition
     */
    public function testGeDefinition()
    {
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        
        // First save a definition
        $def = new EntityDefinition("utest_obj_type");
        $data = array(
            'name' => "name",
            'title' => "Name Field",
            'type' => "text",
            'subtype' => "256",
        );
        $def->setField("name", $data);
		$data = array(
            'name' => "parent",
            'title' => "Parent",
            'type' => "text",
            'subtype' => "256",
        );
        $def->setField("parent", $data);
		$def->setParentField("parent");
        $ret = $dm->saveDefinition($def);
        $this->assertTrue($ret);
        
        // Now open it and test the field
        $def = $dm->getDefinition("utest_obj_type");
        $field = $def->getField("name");
        $this->assertEquals("text", $field['type']);

        $this->assertEquals("parent", $def->getParentField());
    }
    
    /**
     * Test set value
     */
    public function testSetValue()
    {
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        $ret = $dm->setValue("my/key", "1");
        $this->assertTrue($ret);
    }
    
    /**
     * Test get value
     */
    public function testGetValue()
    {
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        $ret = $dm->setValue("my/key", "1");

        $val = $dm->getValue("my/key");
        $this->assertEquals("1", $val);
    }
    
    /**
     * Test save groupings
     */
    public function testSaveGroupings()
    {
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        
        $grp = new EntityGrouping();
        $grp->id = 1;
        $grp->title = "My Test Grouping";
        
        $ret = $dm->saveGroupings("uttest_obj_type", "groups", array($grp));
        $this->assertTrue($ret);
    }
    
    /**
     * Test get groupings
     */
    public function testGetGroupings()
    {
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        $grp = new EntityGrouping();
        $grp->id = 1;
        $grp->title = "My Test Grouping";
        $grp2 = new EntityGrouping();
        $grp2->id = 2;
        $grp2->title = "My Second Grouping";
        $grp2->filter_field = "yes";
        
        $ret = $dm->saveGroupings("uttest_obj_type", "groups", array($grp, $grp2));
        
        $groupings = $dm->getGroupings("uttest_obj_type", "groups");
        $this->assertEquals($grp->id, $groupings[0]->id);
        $this->assertEquals($grp->title, $groupings[0]->title);
        
        // Test get filtered
        $groupings = $dm->getGroupings("uttest_obj_type", "groups", array("filter_field"=>"yes"));
        $this->assertEquals($grp2->id, $groupings[0]->id);
        $this->assertEquals($grp2->title, $groupings[0]->title);
    }

	/**
	 * Test timestamp escape/unescape
	 */
	public function testTimestampEscape()
	{
        $dm = new ElasticDataMapper("localhost", "utest_dm");

		// Create a new object for testing
        $def = new EntityDefinition("utest_escape");
        $data = array(
            'name' => "name",
            'title' => "Name Field",
            'type' => "text",
            'subtype' => "256",
        );
        $def->setField("name", $data);
        $data = array(
            'name' => "last_contacted",
            'title' => "Last Contacted",
            'type' => "timestamp",
			'subtype' => "",
        );
        $def->setField("last_contacted", $data);
        $ret = $dm->saveDefinition($def);

		// Work with last contacted
		$lastContacted = time();
        
        // Save a test object
        $obj = EntityFactory::factory("utest_escape");
        $obj->setValue("id", 1);
        $obj->setValue("name", "Netric CMS Unit Test");
        $obj->setValue("last_contacted", $lastContacted);
        $dm->save($obj);
        
        // Get the newly created customer
        $obj = $dm->fetchById("utest_escape", 1);

		// Check if the timestamp was escaped and unescaped correctly
		$this->assertEquals($lastContacted, $obj->getValue("last_contacted"));
	}
}
