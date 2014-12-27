<?php
/**
 * Define common tests that will need to be run with all data mappers.
 *
 * In order to implement the unit tests, a datamapper test case just needs
 * to extend this class and create a getDataMapper class that returns the
 * datamapper to be tested
 */
namespace NetricTest\DataMapper\Collection\Aggregation;

use Netric\Models\Collection\Aggregation;
use Netric\Models\EntityDefinition;
use Netric\Models\EntityFactory;
use PHPUnit_Framework_TestCase;

abstract class AbstractAggregateTests extends PHPUnit_Framework_TestCase 
{
    /**
     * Test opportunities
     * 
     * @var Entity('opportunity')
     */
    protected $opportunities = array();
    
	/**
	 * Setup each test
	 */
	protected function setUp() 
	{
        //$this->account = \NetricTest\Bootstrap::getAccount();
        $this->createTestData();
	}
    
    /**
     * Cleanup test objects
     */
    protected function tearDown()
    {
        $this->deleteTestData();
    }
    
    /**
     * Required by all derrieved classes
     * 
     * @return \Netric\Models\DataMapper\DataMapperInterface The setup index to query
     */
    abstract protected function getDataMapper();
    
    /**
     * Create a few test objects
     */
    protected function createTestData()
    {
        $dm = $this->getDataMapper();
        if (!$dm)
            return;
        
        // Create first opportunity
        $obj = EntityFactory::factory("opportunity");
        $obj->setValue("id", 1);
        $obj->setValue("name", "Website");
        $obj->setValue("f_won", false);
        $obj->setValue("probability_per", 50);
        $obj->setValue("amount", 100);
        $oid = $dm->save($obj);
        $this->opportunities[] = $obj;
        
        // Create first opportunity
        $obj2 = EntityFactory::factory("opportunity");
        $obj->setValue("id", 2);
        $obj2->setValue("name", "Application");
        $obj2->setValue("f_won", true);
        $obj2->setValue("probability_per", 75);
        $obj2->setValue("amount", 50);
        $oid = $dm->save($obj2);
        $this->opportunities[] = $obj2;
    }  
    
    
    /**
     * Create a few test objects
     */
    protected function deleteTestData()
    {
        // Get index and fail if not setup
        $dm = $this->getDataMapper();
        if (!$dm)
            return;
        
        foreach ($this->opportunities as $opp)
        {
            $dm->deleteById("opportunity", $opp->getId());
        }
    }
    
    /**
     * Make sure the getTypeName for the abstract class works
     */
    public function testGetTypeName()
    {
        $agg = new \Netric\Models\Collection\Aggregation\Terms("test");
        $this->assertEquals("terms", $agg->getTypeName());
    }
    
    /**
     * Test terms aggregate
     */
    public function testTerms()
    {
        // Get index and fail if not setup
        $dm = $this->getDataMapper();
        if (!$dm)
            return;

        $entity = \Netric\Models\EntityFactory::factory("opportunity");
        $collection = $entity->createCollection();
        //$collection->where('name')->equals($uniName);
        
        $agg = new \Netric\Models\Collection\Aggregation\Terms("test");
        $agg->setField("name");
        $collection->addAggregation($agg);
        $dm->loadCollection($collection);
        $agg = $collection->getAggregation("test")->getData();
        $appInd = (strtolower($agg[0]["term"]) == "application") ? 0 : 1;
        $webInd = (strtolower($agg[0]["term"]) == "website") ? 0 : 1;
        
        $this->assertEquals(1, $agg[$appInd]["count"]);
        $this->assertEquals("application", $agg[$appInd]["term"]);
        $this->assertEquals(1, $agg[$webInd]["count"]);
        $this->assertEquals("website", $agg[$webInd]["term"]);
    }
    
    /**
     * Test sum aggregate
     */
    public function testSum()
    {
        // Get index and fail if not setup
        $dm = $this->getDataMapper();
        if (!$dm)
            return;

        $entity = \Netric\Models\EntityFactory::factory("opportunity");
        $collection = $entity->createCollection();
        
        $agg = new \Netric\Models\Collection\Aggregation\Sum("test");
        $agg->setField("amount");
        $collection->addAggregation($agg);
        $dm->loadCollection($collection);
        $agg = $collection->getAggregation("test");
        $this->assertEquals(150, $agg->getData()); // 2 opps one with 50 and one with 100
    }
    
    /**
     * Test stats aggregate
     */
    public function testStats()
    {
        // Get index and fail if not setup
        $dm = $this->getDataMapper();
        if (!$dm)
            return;

        $entity = \Netric\Models\EntityFactory::factory("opportunity");
        $collection = $entity->createCollection();
        
        $agg = new \Netric\Models\Collection\Aggregation\Stats("test");
        $agg->setField("amount");
        $collection->addAggregation($agg);
        $dm->loadCollection($collection);
        $agg = $collection->getAggregation("test")->getData();
        $this->assertEquals(2, $agg["count"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(50, $agg["min"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(100, $agg["max"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(((100 + 50)/2), $agg["avg"]); // 2 opps one with 50 and one with 100
        $this->assertEquals(150, $agg["sum"]); // 2 opps one with 50 and one with 100
    }
    
    /**
     * Test agv aggregate
     */
    public function testAvg()
    {
        // Get index and fail if not setup
        $dm = $this->getDataMapper();
        if (!$dm)
            return;

        $entity = \Netric\Models\EntityFactory::factory("opportunity");
        $collection = $entity->createCollection();
        
        $agg = new \Netric\Models\Collection\Aggregation\Avg("test");
        $agg->setField("amount");
        $collection->addAggregation($agg);
        $dm->loadCollection($collection);
        $agg = $collection->getAggregation("test");
        $this->assertEquals(((100 + 50)/2), $agg->getData()); // 2 opps one with 50 and one with 100
    }
    
    /**
     * Test min aggregate
     */
    public function testMin()
    {
        // Get index and fail if not setup
        $dm = $this->getDataMapper();
        if (!$dm)
            return;

        $entity = \Netric\Models\EntityFactory::factory("opportunity");
        $collection = $entity->createCollection();
        
        $agg = new \Netric\Models\Collection\Aggregation\Min("test");
        $agg->setField("amount");
        $collection->addAggregation($agg);
        $collection->addAggregation($agg);
        $dm->loadCollection($collection);
        $this->assertEquals(50, $agg->getData()); // 2 opps one with 50 and one with 100
    }
}
