<?php
/*
 * This is the api data mapper that will gather object data from netric through the REST api
 */

namespace Netric\Models\DataMapper;
use Netric\Models;

class PgsqlDataMapper extends Models\DataMapperAbstract  implements Models\DataMapperInterface
{
	/**
	 * Get object data from local database
     * 
	 * @var string $objType The name of the object type
     * @var string $id The Id of the object
	 * @return Entity
	 */
	public function fetchById($objType, $id)
    {
    }
    
    /**
     * Delete an object by id
     * 
     * @var string $objType The name of the object type
     * @var string $id The Id of the object
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
	 * Get object definition based on an object type
	 *
     * @param string $key The unique key to get a value for
     * @param string $value The value of the key to store
	 * @return EntityDefinition
	 */
	public function getDefinition($objType)
    {
        return "";
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
