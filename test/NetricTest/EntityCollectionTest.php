<?php
namespace NetricTest\Models;

use PHPUnit_Framework_TestCase;
use Netric\Models\EntityFactory;
use Netric\Models\EntityCollection;

class EntityCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test creating a collection from an entity
     */
	public function testCreateCollection()
	{
		$entity = EntityFactory::factory("customer");
        $collection = $entity->createCollection();
        
        $this->assertInstanceOf("Netric\Models\EntityCollection", $collection);
	}
    
    /**
     * Test addition a filter condition to a collection
     */
    public function testWhere()
    {
        $collection = new EntityCollection("customer");        
        $collection->where('name')->equals("test");
        //$collection->orWhere('fieldname')->isGreaterThan("value");
        //$collection->andWhere('fieldname')->isLessThan("value");
        
        // Get the protected and private values
		$refColl = new \ReflectionObject($collection);
		$wheresProp = $refColl->getProperty('wheres');
		$wheresProp->setAccessible(true);

		// Test values
        $wheres = $wheresProp->getValue($collection);
		$this->assertEquals("name", $wheres[0]->fieldName, "Where name not set");
		$this->assertEquals("test", $wheres[0]->value, "Where condtiion value not set");
    }
    
    /**
     * Test addition an order by condition to a collection
     */
    public function testOrderBy()
    {
        $collection = new EntityCollection("customer");        
        $collection->orderBy("name");
        
        // Get the protected and private values
		$refColl = new \ReflectionObject($collection);
		$orderByProp = $refColl->getProperty('orderBy');
		$orderByProp->setAccessible(true);

		// Test values
        $orderBy = $orderByProp->getValue($collection);
		$this->assertEquals("name", $orderBy[0]['field'], "Order by name not set");
    }
    
    /**
     * Test automatic pagination
     */
    public function testPagination()
    {
        $collection = new EntityCollection("customer");
        $collection->setOffset(0);
        $collection->setLimit(2);
        $collection->setTotalNum(5);
        
        $ent = $collection->getEntity(3); // Should push us to the next page
        $this->assertEquals(2, $collection->getOffset());
        
        // Do it again but skip a bunch of pages this time
        $collection->setOffset(0);
        $collection->setLimit(50);
        $collection->setTotalNum(150);
        
        $ent = $collection->getEntity(149); // Should push us to the next page
        $this->assertEquals(100, $collection->getOffset());
        
        // Now test less than
        $ent = $collection->getEntity(5); // Should push us to the next page
        $this->assertEquals(0, $collection->getOffset());
    }
}