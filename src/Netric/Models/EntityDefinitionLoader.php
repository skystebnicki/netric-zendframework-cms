<?php
/**
 * An identity map ensures that each object gets loaded only once per transaction
 * by keeping every loaded object in a map and look it up before loading the data in the datamapper
 */
namespace Netric\Models;
use Netric\Models\DataMapperInterface;

class EntityDefinitionLoader
{
    /**
     * The current data mapper we are using for this object
     * 
     * @var DataMapperInterface
     */
	protected $dataMapper = null;
    
    /**
     * Array of loaded entities
     * 
     * @var array
     */
    private $loadedDefinitions = array();
    
    /**
     * Setup IdentityMapper for loading objects
     * 
     * @param \Netric\Models\DataMapper\DataMapperInterface $dm
     * @return \Netric\Models\DataMapper\IdentityMap
     */
    public function __construct(DataMapperInterface $dm)
	{
		$this->dataMapper = $dm;
		return $this;
	}
    
    /**
     * Get an entity
     * 
     * @param string $objType
     * @return Entity
     */
    public function get($objType)
    {
        if ($this->isLoaded($objType)) 
        {
            return $this->loadedDefinitions[$objType];
        }
        else
        {
            $def = $this->dataMapper->getDefinition($objType);
            $this->loadedDefinitions[$objType] = $def;
            return $def;
        }
    }
    
    /**
     * Check to see if the entity has already been loaded and a reference cached
     * 
     * @param string $key The unique key of the loaded object
     * @return boolean
     */
    private function isLoaded($key)
    {
        $loaded = isset($this->loadedDefinitions[$key]);

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
}