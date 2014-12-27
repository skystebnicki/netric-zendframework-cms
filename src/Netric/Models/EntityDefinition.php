<?php
/**
 * The entity definition will be used to define object fields and other attributes
 */

namespace Netric\Models;

/**
 * Base object class
 */
class EntityDefinition
{
    /**
     * Associative array of fields for this entity type
     * 
     * @var array
     */
    private $fields = array(
        "id" => array (
            "name" => "id",
            "title" => "ID",
            "type" => "number",
            "subtype" => ""
        ),
    );

	/**
	 * If this eneity type has a parent, the name of the parent field will be here
	 *
	 * @var string
	 */
	protected $parentField = "";
    
    /**
     * This is the name of the object we are defining
     * 
     * @var string
     */
    protected $objType = "";
       
    /**
     * Setup the entity definition for this object type
     * 
     * @param string $objType The type of object we are loading a definition for
     * @return \Netric\Models\EntityDefinition
     */
    public function __construct($objType)
    {
        $this->objType = $objType;
        
        return $this;
    }
    
    /**
     * Set field definition
     * 
     * @param string $fieldName
     * @param array $fieldData
     */
    public function setField($fieldName, $fieldData)
    {
        $this->fields[$fieldName] = array(
                'name' => $fieldName,    
                'title' => $fieldData['title'],
                'type' => $fieldData['type'],
                'subtype' => $fieldData['subtype'],
            );
    }
    
    /**
     * Get all fields
     * 
     * @return $array
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    /**
     * Get the object type string
     * 
     * @return string
     */
    public function getObjType()
    {
        return $this->objType;
    }
    
    /**
     * Get a field by name
     * 
     * @param string $fieldName
     */
    public function getField($fieldName)
    {
        return (isset($this->fields[$fieldName])) ? $this->fields[$fieldName] : null;
    }

	/**
	 * Get parent field if set
	 *
	 * @return string If object type as a parent field then return, otherwise empty string
	 */
	public function getParentField()
	{
		return $this->parentField;
	}

	/**
	 * Set the field used to define the parent of objects
	 *
	 * @param string $fieldName The name of the field containing a reference to the parent
	 */
	public function setParentField($fieldName)
	{
		$this->parentField = $fieldName;
	}
}
