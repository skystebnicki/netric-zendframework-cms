<?php
/**
 * Test aggregates against pgsql index
 *
 * Most tests are inherited from AggregateTestsAbstract.php.
 * Only define index specific tests here and try to avoid name collision with the tests
 * in the parent class. For the most part, the parent class tests all public functions
 * so private functions should be tested below.
 */
namespace NetricTest\DataMapper\Collection\Aggregation;

use Netric\Models\DataMapper\ElasticDataMapper;
use Netric\Models\EntityDefinition;
use Netric\Models\DataMapper\ApiDataMapper;

class ElasticTest extends AbstractAggregateTests
{
	/**
	 * Use this funciton in all the indexes to construct the datamapper
	 *
	 * @return EntityDefinition_DataMapperInterface
	 */
	protected function getDataMapper()
	{
        $dm = new ElasticDataMapper("localhost", "utest_dm");
        
        // Update customer definition
        $dmApi = new ApiDataMapper("localhost", "administrator", "Password1");
        $dmApi->https = false;
        $dm->saveDefinition($dmApi->getDefinition("opportunity"));

        return $dm;
	}
    
    /**
     * Dummy test
     */
    public function testDummy()
    {
        $this->assertTrue(true);
    }
    
    
}