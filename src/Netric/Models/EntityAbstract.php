<?php
/**
 * This is the default entity/object that will be instantiated if there is no subclassed entity
 */
namespace Netric\Models;

/**
 * Base object class
 * 
 * @author Sky Stebnicki <sky.stebnicki@aereus.com>
 */
abstract class EntityAbstract
{
    /**
     * The unique id of this object/entity
     * 
     * @var string
     */
    protected $id;
    
    /**
     * The values for the fields of this entity
     * 
     * @var array
     */
    protected $values = array();
    
    /**
     * Set object type
     * 
     * @var string
     */
    protected $objType = "";
    
    /**
     * The values for the fkey or object keys
     * 
     * @var array
     */
    protected $fkeysValues = array();
    
    /**
     * Class constructor
     * 
     * @param string $objType Unique name of the objec type
     */
    public function __construct($objType) 
    {
        $this->objType = $objType;
    }
    
    /**
     * Get the object type of this object
     * 
     * @return string
     */
    public function getObjType()
    {
        return $this->objType;
    }

	/**
	 * Get unique id of this object
	 */
	public function getId()
	{
		return $this->id;
	}
    
    /**
	 * Set the unique id of this object
     * 
     * @param string $id The unique id of this object instance
	 */
	public function setId($id)
	{
		$this->id = $id;
	}
    
    /**
     * Return either the string or an array of values if *_multi
     * 
     * @param string $strname
     * @return string|array
     */
    public function getValue($strname)
    {       
        return (isset($this->values[$strname])) ? $this->values[$strname] : null;
    }
    
    /**
     * Get fkey values for key/value field types like fkey and fkeyMulti
     * 
     * @param string $strName
     * @return string
     */
    public function getFvals($strName)
    {
        return (isset($this->fkeysValues[$strName])) ? $this->fkeysValues[$strName] : null;
    }
    
    /**
     * Set a field value for this object
     * 
     * @param string $strName
     * @param mixed $value
     * @param string $valueName If this is an object or fkey then cache the foreign value
     */
    public function setValue($strName, $value, $valueName=null)
    {
        $this->values[$strName] = $value;
        
        if ($strName == "id")
            $this->setId($value);
        
        if ($valueName)
            $this->fkeysValues[$strName] = $valueName;
    }
    
    /**
     * Add a multi-value entry to the *_multi type field
     * 
     * @param string $strName
     * @param string|int $value
     * @param string $valueName Optional value name if $value is a key
     */
    public function addMultiValue($strName, $value, $valueName="")
    {
        if (!isset($this->values[$strName]))
            $this->values[$strName] = array();
        
        $this->values[$strName][] = $value;
        
        if ($valueName)
            $this->fkeysValues[$strName][$value] = $valueName;
    }
    
    /**
     * Remove a value from a *_multi type field
     * 
     * @param string $strName
     * @param string|int $value
     */
    public function removeMultiValue($strName, $value)
    {
        // TODO: remove the value from the multi-value array
    }
    
    /**
     * Create a new collection and return ti
     * 
     * @return \Netric\Models\EntityCollection
     */
    public function createCollection()
    {
        return new EntityCollection($this->objType);
    }

	/**
	 * Get display name for this entity based on common name fields
	 */
	public function getName()
	{
		$fields = array(
			"name",
			"title",
			"subject",
		);

		foreach ($fields as $fname)
		{
			if ($this->getValue($fname))
				return $this->getValue($fname);
		}

		return $this->getId();
	}

	/**
     * Generate a teaser text for this entity
     * 
     * @param string $wordLengh The maximum number of words to return
     * @return string The teaster
     */
    public function getTeaser($wordLength=25)
    {
		$val = "";
		$fields = array(
			"data",
			"description",
			"body",
			"notes",
		);

		foreach ($fields as $fname)
		{
			if ($this->getValue($fname))
				$val = $this->getValue($fname);
		}

		if ($val)
        	return implode(' ', array_slice(explode(' ', strip_tags($val, "<br/>")), 0, $wordLength));
		else
			return "";
    }
}
