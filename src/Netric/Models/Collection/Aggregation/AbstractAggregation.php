<?php
/**
 * This defines the base aggregate for queries
 * 
 * @author Sky Stebnicki <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\Models\Collection\Aggregation;

/**
 * Base aggregate class
 */
abstract class AbstractAggregation 
{
    /**
     * The name of this aggregation
     * 
     * @var string
     */
    protected $_name;
    
    /**
     * If a field is used, then set it here (most aggregates use fields)
     * 
     * @var string
     */
    protected $_field;
    
    /**
     * Aggregation data
     * 
     * @var mixed
     */
    private $_data = null;
    
    /**
     * Class constructor
     * 
     * @param string $name the name of this aggregation
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Set the name of this aggregation
     * 
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Retrieve the name of this aggregation
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Set the field for this aggregation
     * 
     * @param string $field the name of the document field on which to perform this aggregation
     */
    public function setField($field)
    {
        $this->_field = $field;
    }
    
    /**
     * Get the field for this aggregation
     * 
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }
    
    /**
     * Set the field for this aggregation
     * 
     * @param mixed $data Whatever the results might be from the aggregate when queried
     */
    public function setData($data)
    {
        $this->_data = $data;
    }
    
    /**
     * Get aggregation data if set
     * 
     * @return array|string
     */
    public function getData()
    {
        return $this->_data;
    }
    
    /**
     * Tries to guess the name of the aggregate, based on its class
     * Example: \Netric\EntityQuery\Aggregations\TermsFilter => terms_filter
     *
     * @param string|object Class or Class name
     * @return string parameter name
     */
    public function getTypeName()
    {
        $class = get_class($this);
        
        $parts = explode('\\', $class);
        $last  = array_pop($parts);
        //$last  = preg_replace('/(Facet|Query|Filter)$/', '', $last);
        // Convert to snake case
        //$name = preg_replace('/([A-Z])/', '_$1', $last);

        return strtolower($last);
    }
}
