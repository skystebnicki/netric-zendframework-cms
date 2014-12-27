<?php
/**
 * Test the query capability of all datamappers in this commen unit test
 */
namespace NetricTest\Models\DataMapper;

use PHPUnit_Framework_TestCase;
use Netric\Models\EntityFactory;
use Netric\Models\DataMapper\ApiDataMapper;
use Netric\Models\DataMapper\ElasticDataMapper;
use Netric\Models\EntityDefinition;

class ColllectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * The database name to use for unit tests
     * 
     * @var string
     */
    private $dbname = "utest_dm";  
    
    /**
     * API server
     * 
     * @var string
     */
    private $apiServer = "localhost";
    
    /**
     * API user name
     * 
     * @var string
     */
    private $apiUser = "administrator";
    
    /**
     * API user password
     * 
     * @var string
     */
    private $apiPass = "Password1";
    
    /**
     * Run tests for elastic search
     */
	public function testApi()
	{
		$dm = new ApiDataMapper($this->apiServer, $this->apiUser, $this->apiPass);
        $dm->https = false;
        $this->runWhereEqualsTest($dm);
    }
    
    /**
     * Run tests for elastic search
     */
	public function testElastic()
	{
		$dm = new ElasticDataMapper("localhost", $this->dbname);
        
        // Update customer definition
        $dmApi = new ApiDataMapper($this->apiServer, $this->apiUser, $this->apiPass);
        $dmApi->https = false;
        $dm->saveDefinition($dmApi->getDefinition("customer"));
        
        $this->runWhereEqualsTest($dm);
    }
    
    /**
     * Run test of is equal conditions
     * 
     * @param \Netric\Models\DataMapperInterface $dm
     */
    private function runWhereEqualsTest(\Netric\Models\DataMapperInterface $dm)
    {
        $uniName = "utestequals." . uniqid();
        
        // Save a test object
        $obj = EntityFactory::factory("customer");
        $obj->setValue("name", $uniName);
        $obj->setValue("f_nocall", true);
        $obj->setValue("type_id", 2); // Organization
        $oid = $dm->save($obj);
        
        // Query collection for string
        $collection = $obj->createCollection();
        $collection->where('name')->equals($uniName);
        $num = $dm->loadCollection($collection);
        $this->assertEquals(1, $num);
        $obj = $collection->getEntity(0);
        $this->assertEquals($oid, $obj->getId());
        
        // Query collection for number
        $collection = $obj->createCollection();
        $collection->where('type_id')->equals(2);
        $dm->debug = true;
        $num = $dm->loadCollection($collection);
        $this->assertTrue(count($num)>=1);
        $found = false;
        for ($i = 0; $i < $collection->getTotalNum(); $i++)
        {
            $ent = $collection->getEntity($i);
            if ($ent->getId() == $oid)
            {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        
        // TODO: query collection for fkey
        
        // TODO: query collection for fkey_multi
        
        // Query collection for boolean
        $collection = $obj->createCollection();
        $collection->where('f_nocall')->equals(true);
        $num = $dm->loadCollection($collection);
        $this->assertTrue(count($num)>=1);
        // Look for the entity above
        $found = false;
        for ($i = 0; $i < $collection->getTotalNum(); $i++)
        {
            $ent = $collection->getEntity($i);
            if ($ent->getId() == $oid)
                $found = true;
        }
        $this->assertTrue($found);
               
        // Cleanup
        $dm->deleteById("customer", $oid);
    }
}