<?php
/**
 * A data mapper is responsible for writing and reading data from a persistant store
 */
namespace Netric\Models;
use Netric\Models\EntityDefinitionLoader;

abstract class DataMapperAbstract
{
    /**
     * The type of object this data mapper is handling
     * 
     * @var string
     */
    protected $objType = "";
    
    /**
     * Identity mapper for object definitions
     * 
     * @var EntityDefinitionLoader
     */
    protected $defLoader = null;
    
	/**
	 * Open object by id
	 *
     * @var string $objType The name of the object type
     * @var string $id The Id of the object
	 * @return DomainEntity
	 */
	abstract public function fetchById($objType, $id);

	/**
	 * Open object by id
	 * 
     * @var string $objType The name of the object type
     * @var string $id The Id of the object
	 * @return bool true on success, false on failure
	 */
	abstract public function deleteById($objType, $id);
    
    /**
	 * Get a value from a key from a key-value store
	 *
     * @param string $key The unique key to get a value for
	 * @return string
	 */
	abstract public function getValue($key);
    
    /**
	 * Set the value for a key
	 *
     * @param string $key The unique key to get a value for
     * @param string $value The value of the key to store
	 * @return string
	 */
	abstract public function setValue($key, $value);
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $key The unique key to get a value for
     * @param string $value The value of the key to store
	 * @return EntityDefinition
	 */
	abstract public function getDefinition($objType);

    /**
	 * Save the object definition
	 *
     * @param EntityDefinition $def
	 * @return bool true on success, false on failure
	 */
	public function saveDefinition($def)
	{
		return false;
	}
    
	/**
	 * Save object data
	 *
	 * @return int|bool entity id on success, false on failure
	 */
	public function save($entity)
	{
		return false;
	}
    
    /**
     * Get the definition loader
     * 
     * @return \Netric\Models\EntityDefinitionLoader
     */
    public function getDefLoader()
    {
        if ($this->defLoader == null)
            $this->defLoader = new EntityDefinitionLoader($this);
            
        return $this->defLoader;
    }
    
    /**
     * Create entity from data
     * 
     * @param string $objType The unique object type to load
     * @param array $data Associative array of data to load
     * @return DomainEntity
     */
    private function createAndInitializeEntity($objType, $data)
    {
        $obj = Models\EntityFactory::factory($objType);
        //$def = $this->getDefWithLoader($objType);
        
        foreach ($data as $fname=>$fval)
            $obj->setValue($fname, $fval);
		
        return $obj;        
    }
    
    /**
     * Get definition with loader
     * 
     * @param string $objType The object type to load
     */
    public function getDefWithLoader($objType)
    {
        $loader = $this->getDefLoader();
        return $loader->get($objType);
    }
    
    /**
     * Execute a query against the backend and populate the collection with data
     * 
     * @param \Netric\Models\DataMapper\Netric\Models\EntityCollection $collection
     * @return int Numer of entities loaded
     */
    abstract public function loadCollection(\Netric\Models\EntityCollection &$collection);
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
	 * @return \Netric\Models\EntityGrouping[]
	 */
	abstract public function getGroupings($objType, $fieldName);

    /**
	 * Delete all entities of a specific type
	 *
     * @param string $objType The object type name
	 * @return bool true on success, false on failure
	 */
	abstract public function deleteAllEntities($objType);
    
    /**
	 * Get object grouping by id
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
     * @param string $id The id of the grouping to get
	 * @param \Netric\Models\EntityGrouping[] $groupings Optional array of subgroopings to check
	 * @return \Netric\Models\EntityGrouping
	 */
	public function getGroupingById($objType, $fieldName, $gid, $groupings=null)
    {
        $ret = false;

		if (!$groupings)
        	$groupings = $this->getGroupings($objType, $fieldName);

        foreach ($groupings as $grp)
        {
            if ($grp->id == $gid)
                $ret = $grp;
			else if (count($grp->children)>0) // look through children
				$ret = $this->getGroupingById($objType, $fieldName, $gid, $grp->children);

			if ($ret)
				break;
        }
        
        return $ret;
    }
}
