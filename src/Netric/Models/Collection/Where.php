<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Netric\Models\Collection;

/**
 * Description of Where
 *
 * @author Sky Stebnicki
 */
class Where 
{
    /**
     * Combiner logic
     * 
     * @var string
     */
    public $bLogic = "and";
    
    /**
     * The field name
     * 
     * If the field name is "*" then conduct a full text query
     * 
     * @var string
     */
    public $fieldName = "";
    
    /**
     * The operator to use with this condition
     * 
     * @var string
     */
    public $operator = "";
    
    /**
     * The value to query against
     * 
     * @var string
     */
    public $value = "";
    
    /**
     * Create a where condition
     * 
     * @param string $fieldName
     * @return \Netric\Models\Collection\Where
     */
    public function __construct($fieldName="*") 
    {
        $this->fieldName = $fieldName;
        return $this;
    }
    
    /**
     * Set condition to match where field equals value
     * 
     * @param string $value
     */
    public function equals($value)
    {
        $this->operator = "is_equal";
        $this->value = $value;
    }

    /**
     * Set condition to match where field does not equal value
     * 
     * @param string $value
     */
    public function doesNotEqual($value)
    {
        $this->operator = "is_not_equal";
        $this->value = $value;
    }

	/**
	 * Check if terms are included in a string - full text
	 *
	 * @param string $value
	 */
	public function contains($value)
	{
        $this->operator = "contains";
        $this->value = $value;
	}

	/**
	 * Check if terms are included in a string - full text
	 *
	 * @param string $value
	 */
	public function isGreaterThan($value)
	{
        $this->operator = "is_greater";
        $this->value = $value;
	}
}
