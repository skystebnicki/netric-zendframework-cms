<?php
/*
 * This is a simple test data mapper used for unit tests
 */

namespace Netric\Models\DataMapper;
use Netric\Models;

class TestDataMapper extends Models\DataMapperAbstract  implements Models\DataMapperInterface
{
	/**
     * Get object data from netric api
     * 
     * @param string $objType The object type we are openeing
	 * @param string $id The id of the object
	 * @return Entity
	 */
	public function fetchById($objType, $id)
    {
        $obj = Models\EntityFactory::factory($objType);
        $obj->setId(1);
        return $obj;
    }
    
    /**
     * Delete an object by id
     * 
     * @@param string $objType The object type we are openeing
	 * @param string $id The id of the object
     * @return boolean
     */
    public function deleteById($objType, $id)
    {
        return false;
    }
    
    /**
	 * Get a value from a key from a key-value store
	 *
     * @param string $key The unique key to get a value for
	 * @return string
	 */
	public function getValue($key)
    {
        return "";
    }
    
    /**
	 * Set the value for a key
	 *
     * @param string $key The unique key to get a value for
     * @param string $value The value of the key to store
	 * @return boolean
	 */
	public function setValue($key, $value)
    {
        return false;
    }
    
    /**
	 * Test of get definition always returns same definition object statically for unit tests
	 *
     * @param string $key The unique key to get a value for
     * @param string $value The value of the key to store
	 * @return EntityDefinition
	 */
	public function getDefinition($objType)
    {
        $def = new Models\EntityDefinition($objType);

        $data = array(
            'name' => "name",
            'title' => "Name Field",
            'type' => "text",
            'subtype' => "256",
            'system' => true,
            'required' => true,
        );
        $def->setField($data['name'], $data);
        
        return $def;
    }
    
    /**
     * Execute a query against the backend and populate the collection with data
     * 
     * @param \Netric\Models\EntityCollection &$collection
     * @return int Numer of entities loaded
     */
    public function loadCollection(\Netric\Models\EntityCollection &$collection)
    {
        return 0;
    }
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
	 * @return \Netric\Models\EntityGrouping[]
	 */
	public function getGroupings($objType, $fieldName)
    {
        return array();
    }

    /**
	 * Delete all entities of a specific type
	 *
     * @param string $objType The object type name
	 * @return bool true on success, false on failure
	 */
	public function deleteAllEntities($objType)
	{
		return true;
	}
}
