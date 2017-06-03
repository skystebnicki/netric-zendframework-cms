<?php
/**
 * An identity map ensures that each object gets loaded only once per transaction
 * by keeping every loaded object in a map and look it up before loading the data in the datamapper
 */
namespace Netric\Models;
use NetricSDK\NetricApi;
use NetricSDK\Entity\Entity;
use NetricSDK\EntityCollection\EntityCollection;

class IdentityMap
{
    /**
     * The current data mapper we are using for this object
     * 
     * @var NetricApi $netricApi Used to load entities from the server
     */
	protected $netricApi = null;
    
    /**
     * Array of loaded entities
     * 
     * @var array
     */
    private $loadedEntities = array();
    
    /**
     * Setup IdentityMapper for loading objects
     * 
     * @param NetricApi $netricApi
     * @return IdentityMap
     */
    public function __construct(NetricApi $netricApi)
	{
		$this->netricApi = $netricApi;
		return $this;
	}
    
    /**
     * Get an entity
     * 
     * @param string $objType
     * @param string $id
     * @return Entity
     */
    public function get($objType, $id)
    {
        $key = $objType . ":" . $id;
        
        if ($this->isLoaded($key)) 
        {
            return $this->loadedEntities[$key];
        }
        else
        {
            $entity = $this->netricApi->getEntity($objType, $id);
            $this->loadedEntities[$key] = $entity;
            return $entity;
        }
    }

	/**
	 * Get an object by a uname path - often used for pages
	 *
     * @param string $objType The unique objet type name
     * @param string $path The uname path, can be hierarchical if there is a parentField for the def and delim with '/'
	 * @param string $parentId If being called recurrsively to walk down a tree
     * @return Entity|bool Entity if found, false if not found or fail
	 */
	public function getByUnamePath($objType, $path, $parentId=null)
	{
		$entity = false;

		// Get the definition and see if we are even working with a hierarchical object type
		$def = $this->getDataMapper()->getDefWithLoader($objType);
		if (!$def->getParentField() || strpos($path, '/')===false)
			return $this->get($objType, "uname:" . $path); // get standard uname

		// Loop through names to get to the last entry
		$names = explode("/", $path);
		$lastParent = null;
		foreach ($names as $uname)
		{
			if ($uname) // skip over first which is root and could be empty string
			{
				$entity = $this->getByUnameWithParent($objType, $uname, $lastParent);
				if ($entity)
					$lastParent = $entity->getId();
				else
					return false; // not found
			}
		}

		return $entity;
	}

	/**
	 * Load an object by uname with a parent id condition
	 *
     * @param string $objType The unique objet type name
	 * @param string $name The name of the object - should be unique or just first found instance is returned
	 * @param int $parentId If hierarchial then id of the parent for this specific name
	 * @return Entity|null Object if uname is found, null if not found
	 */
	public function getByUnameWithParent($objType, $name, $parent=null)
	{
		// Setup collection
		$def = $this->getDataMapper()->getDefWithLoader($objType);
		$collection = $this->createCollection($objType);
		$collection->where('uname')->equals($name);
		if ($def->getParentField())
		{
			if ($parent)
				$collection->where($def->getParentField())->equals($parent);
			else
				$collection->where($def->getParentField())->equals("");
		}

		// Get results
        $num = $collection->load();
		if ($num == 1)
			return $collection->getEntity(0);
		else if ($num > 1)
			throw new \Exception("Data integrity problem. More than one uname found matching.");

		// By default return a null object
		return null;
	}
    
    /**
     * Check to see if the entity has already been loaded and a reference cached
     * 
     * @param string $key The unique key of the loaded object
     * @return boolean
     */
    private function isLoaded($key)
    {
        $loaded = isset($this->loadedEntities[$key]);

        // Check caching layer if available
        if (!$loaded)
            $loaded = $this->isCached($key);
        
        return $loaded;
    }
    
    /**
     * Check to see if an entity is cached
     * 
     * @param string $key The unique key of the object that might be cached
     * @return boolean
     */
    private function isCached($key)
    {
        // TODO: load the cache datamapper and put it into $this->loadedEntities
        return false;
    }
    
    /**
     * Create a collection object and inject datamapper
     * 
     * @param string $objType
     * @return EntityCollection
     */
    public function createCollection($objType)
    {
        return $this->netricApi->createEntityCollection($objType);
    }
}
