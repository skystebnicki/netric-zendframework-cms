<?php
/**
 * This class represents a grouping field entry
 */
namespace Netric\Models;

/**
 * Base grouping entry
 * 
 * @author Sky Stebnicki <sky.stebnicki@aereus.com>
 */
class EntityGrouping
{
    /**
     * Unique id of this grouping
     * 
     * @var string
     */
    public $id = "";

    /**
     * The title of this grouping
     * 
     * @var string
     */
    public $title = "";
    
    /**
     * Unique name if exists
     * 
     * @var string
     */
    public $uname = "";
    
    /**
     * Grouping is heiarchial with a parent id
     * 
     * @var bool
     */
    public $isHeiarch = false;
    
    /**
     * If heiarchial then parent may be used to define parent-child groupings
     * 
     * @var int
     */
    public $parentId = null;

    /**
     * Optional hex color
     * 
     * @var string
     */
    public $color = "";
    
    /**
     * The sort order of this grouping if not by title
     * 
     * @var int
     */
    public $sortOrder = 0;
    
    /**
     * Children
     * 
     * @var \Netric\Models\EntityGrouping
     */
    public $children = array();
    
    /**
     * Add filtered data
     */
    public $filterFields = array();
    
    /**
     * Convert class properties to an associative array
     * 
     * @return array
     */
    public function toArray()
    {
        $data = array(
            "id" => $this->id,
            "title" => $this->title,
            "uname" => $this->uname,
            "is_heiarch" => $this->isHeiarch,
            "parent_id" => $this->parentId,
            "color" => $this->color,
            "sort_order" => $this->sortOrder,
            "filter_fields" => $this->filterFields,
            "children" => array(),
        );
        
        foreach ($this->children as $child)
        {
            $data['children'][] = $child->toArray();
        }
        
        return $data;
    }
    
    /**
     * Set a property value by name
     * 
     * @param string $fname The property or field name to set
     * @param string $fval The value of the property
     */
    public function setValue($fname, $fval)
    {
        switch ($fname)
        {
        case "id":
            $this->id = $fval;
            break;
        case "title":
            $this->title = $fval;
            break;
        case "uname":
            $this->uname = $fval;
            break;
        case "isHeiarch":
            $this->isHeiarch = $fval;
            break;
        case "parentId":
            $this->parentId = $fval;
            break;
        case "color":
            $this->color = $fval;
            break;
        case "sortOrder":
            $this->sortOrder = $fval;
            break;
        default:
            $this->filterFields[$fname] = $fval;
            break;
        }
    }
    
    /**
     * Set an undefined property in the filtered fields
     * 
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) 
    {
        $this->filterFields[$name] = $value;
    }
    
    /**
     * Get an undefined property
     * 
     * @param string $name
     * @return string|null
     */
    public function __get($name) 
    {
        if (array_key_exists($name, $this->filterFields)) 
            return $this->filterFields[$name];
        
        return null;
    }

    /**
     * Check if an undefined property is set in the filterFields propery
     * 
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->filterFields[$name]);
    }
    
    /**
     * Get filtered value
     * 
     * @param string $name
     * @return string
     */
    public function getFilteredVal($name)
    {
        if (isset($this->filterFields[$name]))
            return $this->filterFields[$name];
        else
            return "";
    }
}