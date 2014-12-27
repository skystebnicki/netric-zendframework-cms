<?php
/*
 * This is the api data mapper that will gather object data from netric through the REST api
 */
namespace Netric\Models\DataMapper;
use Netric\Models;
use Elastica;

class ElasticDataMapper extends Models\DataMapperAbstract  implements Models\DataMapperInterface
{
    /**
     * The server name where netric is running
     * 
     * @var string
     */
    protected $server = "";
    
    /**
     * The name of the index we are using for this datastore
     * 
     * @var string
     */
    protected $indexName = "";
    
    /**
     * Elastic client
     * 
     * @var Elastica\Client
     */
    protected $client = null;
    
    /**
     * Elastic client
     * 
     * @var Elastica\Index
     */
    protected $index = null;
    
    /**
     * Elastic type
     * 
     * @var Elastica\Type
     */
    protected $type = null;

    /**
     * Construct connection to backend
     * 
     * @param string $server
     * @param string $indexName
     */
    public function __construct($server, $indexName) 
    {
        $this->server = $server;
        $this->indexName = $indexName;
        
        if (!$this->server)
            throw new Exception("Server param is reuqired for ElasticSearch");
        
        if (!$this->indexName)
            throw new Exception("Index name param is reuqired for ElasticSearch");
        
        // Get and create index
        $this->client = new Elastica\Client(array('connections' => array(array('host' => $this->server, 'port' => 9200))));
        $this->index = $this->client->getIndex($this->indexName);
        $this->createIndex();
    }
	/**
     * Get object data from netric api
     * 
	 * @var string $objType The name of the object type
     * @var string $id The Id of the object
	 * @return Entity
	 */
	public function fetchById($objType, $id)
    {
        $obj = Models\EntityFactory::factory($objType);
        $def = $this->getDefWithLoader($objType);
        
         // Check for uname
		$pos = strpos($id, "uname:");
		if ($pos !== false)
		{
            $parts = explode(":", $id);
			$id = $parts[1];
			$resultSet = $this->query($objType, "uname_s:$id");

			// Check to see if ID was passed as a uname
			// This should not be a problem but check just to be sure
			if (0 == $resultSet->getTotalHits())
				$resultSet = $this->query($objType, "_id:$id");
		}
		else
		{
			$resultSet = $this->query($objType, "_id:$id");
		}
		$num = $resultSet->getTotalHits();
		if ($num)
		{
			$results = $resultSet->getResults();
			if (count($results))
			{
				$data = $results[0]->getData();
				foreach ($data as $fname=>$fval)
                {
					$field = $def->getField($this->unescapeField($fname));
					if ($field)
						$fval = $this->unescapeFieldValue($field, $fval, $data);

                    $obj->setValue($this->unescapeField($fname), $fval);
                }
			}
		}
        
        // There appears to be a bug where the uname is not set until subsequent syncs, so for now
        // just set manually. - skys
        if ($obj->getId() && !$obj->getValue("uname"))
            $obj->setValue("uname", $obj->getId ());
        
        return $obj;        
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
		try 
		{
			$this->client->deleteIds(array($id), $this->indexName, $objType);
		}
		catch (\Exception $ex)
		{
			return false;
		}
        
        return true;
    }
    
    /**
     * Save an object to the data store
     * 
     * @param \Netric\Models\Entity $obj
     * @return int|boolean Id of saved entity on success, false on failure
     */
    public function save($obj)
    {
		$type = $this->getType($obj->getObjType());
		
        // If there is no id set then generate a new unique id
        if (!$obj->getId())
            $obj->setValue("id", time());
        
		$obj_data = array();
        $def = $this->getDefWithLoader($obj->getObjType());
		$fields = $def->getFields();
		if (is_array($fields) && count($fields))
		{
			foreach ($fields as $field=>$fdef)
			{
				switch ($fdef['type'])
				{
				case 'fkey_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						$obj_data[$this->escapeField($fdef)] = $vals;
						$obj_data[$field."_tsort"] = $vals[0];
					}
					break;
				case 'object_multi':
					$vals = $obj->getValue($field);
					if (is_array($vals) && count($vals))
					{
						$obj_data[$this->escapeField($fdef)] = $vals;
					}
					break;
				case 'date':
				case 'timestamp':
					$val = $obj->getValue($field);
					
					if ($val)
					{
						// Convert to UTC
						if ($val == "now")
						{
							$val = gmdate("Ymd\\TG:i:s", time());
							$val_s = gmdate("Y-m-d\\TG:i:s\\Z", time());
						}
						else
						{
							//$time = strtotime($val);
							$time = $val;
							$val = gmdate("Ymd\\TG:i:s", $time);
							$val_s = gmdate("Y-m-d\\TG:i:s\\Z", $time);
						}

						$obj_data[$this->escapeField($fdef)] = $val;
						// Add string version of field for wildcard queries
						$obj_data[$this->escapeField($fdef)."_s"] = $val_s;
					}
					break;
				case 'fkey':
				case 'number':
				//case 'integer':
					$val = $obj->getValue($field);
					if (is_numeric($val))
						$obj_data[$this->escapeField($fdef)] = $val;
					break;
				case 'object':
					$val = $obj->getValue($field);
					if ($val)
					{
						$obj_data[$this->escapeField($fdef)] = $val;
						$obj_data[$field."_tsort"] = $val;
					}

					break;
				case 'boolean':
				case 'bool':
					$val = $obj->getValue($field);
					if ($val=='t' || $val == 1 || $val === true)
						$val = 'true';
					else
						$val = 'false';

					$obj_data[$this->escapeField($fdef)] = $val;
					break;
				case 'text':
				default:
					$val = $obj->getValue($field);
					if ($val)
					{
						// Trim really long strings (if it exists)
						if (strlen($val) > 32766)
							$val = substr($val, 0, 32765);

						$obj_data[$this->escapeField($fdef)] = $val;
						$obj_data[$field."_tsort"] = $val;
						if ($fdef['subtype']) // save original for facets
							$obj_data[$field."_s"] = $val;
					}
					break;
				}

				// Now set foreign values
				switch ($fdef['type'])
				{
				case 'fkey_multi':
				case 'fkey':
				case 'object':
				case 'object_multi':
				case 'obj_reference':
				case 'alias':
					$fval = $obj->getFVals($field);
                    $obj_data[$field."_fval_s"] = json_encode($fval);
					break;
				}
			}
            
			try
			{
				$doc = new Elastica\Document($obj->getId(), $obj_data);
				$resp = $type->addDocument($doc);
                $ret = $obj->getId();

				$this->getIndex()->refresh();

				if ($resp->hasError())
					$ret = false;
			}
			catch (\Elastica\Exception\ResponseException $ex)
			{
                // TODO: log
				$ret = false;
			}
		}
        
		return $ret;
    }
    
    /**
	 * Get a value from a key from a key-value store
	 *
     * @param string $key The unique key to get a value for
	 * @return string
	 */
	public function getValue($key)
    {
        $ret = "";
        
        $type = $this->getType("settings");
        $resultSet = $type->search("_id:" . str_replace("/", "_", $key));
        if ($resultSet->getTotalHits())
        {
            $results = $resultSet->getResults();
            if (count($results))
            {
                $arr = $results[0]->getData();
                $ret = $arr['value'];
            }
        }
        
        return $ret;
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
        $type = $this->getType("settings");
        
        try
        {
            $doc = new Elastica\Document(str_replace("/", "_", $key), array("value"=>$value));
            $resp = $type->addDocument($doc);
            $this->getIndex()->refresh();

            if ($resp->hasError())
                return false;
        }
        catch (\Elastica\Exception\ResponseException $ex)
        {
            return false;
        }
            
        return true;
    }
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
     * @param array $filters Optional associative array with fieldName=>filterValue
	 * @return \Netric\Models\EntityGrouping[]
	 */
	public function getGroupings($objType, $fieldName, $filters=array())
    {
        if (!$objType || !$fieldName)
            return array();
            
        $params = array("obj_type"=>$objType, "field"=>$fieldName);
		$ret = $this->getValue("object/groupings/$objType/$fieldName");
        $groupsData = ($ret) ? json_decode($ret) : null;

        // Initialize heiarachial array of groupings
        $groupings = $this->setGroupingAray($groupsData, $filters);
        
        return $groupings;
    }
    
    /**
     * Initialize heiarachial array of groupings
     * 
     * @param type $groupsData
     * @param array $filters Optional associative array with fieldName=>filterValue
     * @return \Netric\Models\EntityGrouping[]
     */
    private function setGroupingAray($groupsData, $filters=array())
    {
        $groupings = array();

        if ($groupsData && !isset($groupsData->error))
        {
            foreach ($groupsData as $grpData)
            {
                $filterFieldsArray = array();
                if (count($grpData->filter_fields))
                {
                    foreach ($grpData->filter_fields as $fname=>$fval)
                        $filterFieldsArray[$fname] = $fval;
                }
                
                $grp = new Models\EntityGrouping();
                $grp->id = $grpData->id;
                $grp->uname = $grpData->uname;
                $grp->title = $grpData->title;
                $grp->isHeiarch = $grpData->is_heiarch;
                $grp->parantId = $grpData->parent_id;
                $grp->color = $grpData->color;
                $grp->sortOrder = $grpData->sort_order;
                $grp->filterFields = $filterFieldsArray;
                $grp->children = $this->setGroupingAray($grpData->children);
                
                $add = true;
                if (count($filters))
                {
                    foreach ($filters as $fname=>$fval)
                    {
                        if ($grp->getFilteredVal($fname)!=$fval)
                            $add = false;
                    }
                }
                
                if ($add)
                    $groupings[] = $grp;
            }
        }
        
        return $groupings;
    }
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
	 * @return bool true on success, false on failure
	 */
	public function saveGroupings($objType, $fieldName, $groupings)
    {
        if (!$objType || !$fieldName)
            return array();
            
        $groupingData = array();
        
        foreach ($groupings as $grp)
            $groupingData[] = $grp->toArray();
        
        return $this->setValue("object/groupings/$objType/$fieldName", json_encode($groupingData));
    }
    
    /**
	 * Take data array with escaped names and unescape
	 *
	 * @param array $field Assoc definition of array
	 * @param mixed $val The value to unescape after being returned from the elastic store
	 */
	protected function unescapeFieldValue($field, $val, $data)
	{
		if (empty($val))
			return $val;

		switch ($field['type'])
		{
		case 'time':
		case 'timestamp':
		case 'date':
			// Get string representation with timezone
			if (isset($data[$field['name'] . "_s"]))
				$val = strtotime($data[$field['name'] . "_s"]);
			else
				$val = strtotime($val . " UTC");
			break;
		case 'bool':
		case 'boolean':
			$val = ($val == "true" || $val === true || $val == "t") ? true : false;
			break;
		}

		return $val;
	}

    /**
	 * @depricated ? I don't think this is used any longer
	 * Take data array with escaped names and unescape
	 *
	 * @param array $data Associateive array with field_name=>value to be escaped
	 */
	protected function unescapeData($data)
	{
		if (!$data)
			return array();

		$ret = array();
		foreach($data as $var=>$value) 
		{
			$fname = $this->unescapeField($var);
			$fdef = $this->obj->fields->getField($fname);
			switch ($fdef['type'])
			{
			case 'time':
			case 'timestamp':
			case 'date':
				// Get string representation with timezone
				$value = $data[$var."_s"];
				break;
			case 'bool':
			case 'boolean':
				$value = ($value == "true" || $value === true || $value == "t") ? 't' : 'f';
				break;
			}
			$ret[$fname] = $value;
    	}

		return $ret;
	}
    

    /**
     * Add a postfic to a field depending on the type for dynamic types
     * 
     * @param string $fname
     * @param bool $sortable
     * @param bool $facet
     * @return string
     */
	protected function escapeField($field, $sortable=false, $facet=false)
	{
		$ret = "";
        
        if (!$field)
			return $ret;

		$fname = $field['name'];
		

		if ($fname == "id")
			return "oid";

		// Return raw system or reserved fields
		if ($fname == "database" ||  $fname == "f_deleted" || $fname == "revision" || $fname == "idx_private_owner_id") // $fname == "type" ||
			return $fname;

		switch ($field['type'])
		{
		case 'integer':
		case 'fkey':
			$ret = $fname."_i";
			break;
		case 'fkey_multi':
			$ret = $fname."_imv";
			break;
		case 'date':
		case 'timestamp':
			$ret = $fname."_dt";
			break;
		case 'number':
			switch ($field['subtype'])
			{
			case 'double':
			case 'double precision':
			case 'float':
			case 'real':
				$ret = $fname."_d";
				break;
			case 'integer':
				$ret = $fname."_i";
				break;
			default: // long
				$ret = $fname."_d";
				break;
			}
			break;
		case 'boolean':
		case 'bool':
			$ret = $fname."_b";
			break;
		case 'object_multi':
			$ret = $fname."_smv";
			break;
		case 'object':
			if ($field['subtype'])
				$ret = $fname."_i";
			else
				$ret = $fname."_s";
			break;
		case 'text':
		default:
			//if ($field['subtype']) // limited size
				//$ret = $fname."_s";
			//else
			if ($sortable) // looking for a sortable version
				$ret = $fname."_tsort";
			else if  ($facet && $field['subtype'])
				$ret = $fname."_tsort"; // $ret = $fname."_s";
			else
				$ret = $fname."_t";
			break;
		}

		return $ret;
	}


    /**
     * Remove postfix from an escaped field. Example: myint_i will ret myint
     * 
     * @param string $fname
     * @return string
     */
	protected function unescapeField($fname)
	{
		$ret = $fname;

		if ($fname == "oid")
			$ret = "id";

		// Return raw system or reserved fields
		if ($fname == "database" || $fname == "type" || $fname == "f_deleted" || $fname == "revision" || $fname == "idx_private_owner_id")
		{
			return $fname;
		}

		$pos = strrpos($fname, "_");
		if ($pos)
		{
			$ret = substr($fname, 0, $pos);
		}

		return $ret;
	}

	/**
     * Escape query values
     * 
     * @param string $string
     * @return string
     */
    protected function escapeValue($string)
    {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
        $string = str_replace($match, $replace, $string);
 
        return $string;
    }

	/**
	 * Create index and set mapping
	 *
	 * @param Elastica_Index $index A newly created index to set mapping for
	 */
	protected function createIndex()
	{
        // Create active index
        $index = $this->client->getIndex($this->indexName);
        if ($index->exists())
            return true;
            
		$mapping = array(
			"mappings" => array(
				"_default_" => array(
					"dynamic_templates" => array(
						array(
							"ant_int"	=> array(
								"match" => "*_i",
								"mapping" => array(
									"type" => "integer",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_dbl"	=> array(
								"match" => "*_d",
								"mapping" => array(
									"type" => "double",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_imv"	=> array(
								"match" => "*_imv",
								"mapping" => array(
									"type" => "integer",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_long"	=> array(
								"match" => "*_l",
								"mapping" => array(
									"type" => "long",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_string"	=> array(
								"match" => "*_s",
								"mapping" => array(
									"type" => "string",
									"analyzer" => "string_lowercase",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_t"	=> array(
								"match" => "*_t",
								"mapping" => array(
									"type" => "string",
									"index" => "analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_tsort"	=> array(
								"match" => "*_tsort",
								"mapping" => array(
									"type" => "string",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_smv"	=> array(
								"match" => "*_smv",
								"mapping" => array(
									"type" => "string",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_date"	=> array(
								"match" => "*_dt",
								"mapping" => array(
									"type" => "date",
									"index" => "analyzed",
									"store" => "yes",
								),
							),
						),
						array(
							"ant_b"	=> array(
								"match" => "*_b",
								"mapping" => array(
									"type" => "boolean",
									"index" => "not_analyzed",
									"store" => "yes",
								),
							),
						),
					),
				),
			),
		);

        try 
		{
            $ret = $index->create($mapping);
			//$ret = $this->client->request($this->indexName, Elastica\Request::PUT, $mapping);
		} 
		catch(Elastica\Exception\ResponseException $e) 
		{
            // TODO: log here
			//AntLog::getInstance()->error("ERROR CREATING INDEX $indexname: " . $e->getMessage());
		}
	}

	/**
	 * Get index
	 *
	 * @param bool $deleted If true, then pull from deleted archived index
	 */
	protected function getIndex()
	{
        if (!$this->index)
            $this->indexName = $this->client->getIndex($this->dbh->dbname . "_act");

		return $this->index;
	}
    
    /**
     * Get the type based on the object type
     * 
     * @param string $objType The unique name of the object type to get
     */
    protected function getType($objType)
    {
        $index = $this->getIndex();
        $this->type = $index->getType($objType);
        return $this->type;
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
        $def = new Models\EntityDefinition($objType);
        
		$resultSet = $this->query("obj_definitions", "_id:" . $objType);
		$num = $resultSet->getTotalHits();
		if ($num)
		{
			$results = $resultSet->getResults();
			if (count($results))
			{
				$ret = array();

				$data= $results[0]->getData();
                if (isset($data['fields']))
                {
                    foreach ($data['fields'] as $fname=>$fdef)
                        $def->setField($fname, $fdef);
                }

                if (isset($data['parentField']))
					$def->setParentField($data['parentField']);
			}
		}

		return $def;
    }
    
    /**
	 * Save the object definition
	 *
     * @param EntityDefinition $def
	 * @return bool true on success, false on failure
	 */
	public function saveDefinition($def)
	{
        $index = $this->getIndex();
        $type = $this->getType("obj_definitions");
        
        $fields = $def->getFields();
        $fieldData = array();
        foreach ($fields as $fname=>$field)
            $fieldData[$fname] = $field;
        
        $doc = new Elastica\Document($def->getObjType(),
			array(
				'fields' => $fieldData,
				'parentField' => $def->getParentField(),
			)
        );
        
        try
        {
            $resp = $type->addDocument($doc);
            if ($resp->hasError())
                $ret = false;
            else
                $ret = true;
            
            $index->refresh();
        }
        catch (\Exception $ex)
        {
            $ret = false;
        }

		return $ret;
	}
    
    /**
     * Execute a query against the backend and populate the collection with data
     * 
     * @param \Netric\Models\EntityCollection &$collection
     * @return int Numer of entities loaded
     */
    public function loadCollection(\Netric\Models\EntityCollection &$collection)
    {
        $collection->setDataMapper($this);
        $collection->clearEntities();
        
        $objDef = $this->getDefWithLoader($collection->getObjType());
		$conditions = $collection->getWheres();
		$orderBy = $collection->getOrderBy();
		$query = "";

		// Build condition string
		if (count($conditions))
			$query = $this->buildConditionString($objDef, $conditions);
		
        // Add order by
		$order_cnd = array();
		if (is_array($orderBy) && count($orderBy))
		{
			foreach ($orderBy as $order)
			{
				$fld = $objDef->getField($order['field']);
				if ($fld)
				{
					// Use non-analyzed field for sorting
					if ($fld['type'] == "text" || $fld['type'] == "object" || $fld['type'] == "object_multi")
						$fld_name = $order['field']."_idxsort";
					else	
						$fld_name = $order['field'];

					$order_cnd[$this->escapeField($fld, true)] = strtolower($order['direction']);
				}
			}
		}

        $queryString = new \Elastica\Query\QueryString($query);
        
        $queryObject = new \Elastica\Query();
        if (count($order_cnd))
            $queryObject->setSort($order_cnd);
        $queryObject->setFrom($collection->getOffset());
        $queryObject->setLimit($collection->getLimit());
        if ($query)
            $queryObject->setQuery($queryString);
        
        
        // Set aggregations / facets
        // ----------------------------------------
        if ($collection->hasAggregations())
        {
            $aggregations = $collection->getAggregations();
            foreach ($aggregations as $name=>$agg)
            {
                $aggregate = null;
                
                switch ($agg->getTypeName())
                {
                case 'avg':
                     $aggregate = new \Elastica\Aggregation\Avg($agg->getName());
                    break;
                /** Using facets below until we upgrade all to > 1
                case 'terms':
                    $aggregate = new \Elastica\Aggregation\Terms($agg->getName());
                    break;*/
                case 'stats':
                    $aggregate = new \Elastica\Aggregation\Stats($agg->getName());
                    break;
                case 'sum':
                    $aggregate = new \Elastica\Aggregation\Sum($agg->getName());
                    break;
                case 'min':
                    $aggregate = new \Elastica\Aggregation\Min($agg->getName());
                    break;
                case 'min':
                    $aggregate = new \Elastica\Aggregation\Max($agg->getName());
                    break;
                
                }
               
                $fld = $objDef->getField($agg->getField());
				if ($fld && $aggregate)
				{
                    $aggregate->setField($this->escapeField($fld, false, true));
                    $queryObject->addAggregation($aggregate);
                }
            }
        }
        
        // TODO: Facets are only to be used until we upgrade to ES V>1
        // Sky Stebnicki, July 25 2014
        if ($collection->hasAggregations())
        {
            $aggregations = $collection->getAggregations();
            foreach ($aggregations as $name=>$agg)
            {
                $aggregate = null;
                
                switch ($agg->getTypeName())
                {
                case 'terms':
                    $aggregate = new \Elastica\Facet\Terms($agg->getName());
                    break;
                }
               
                $fld = $objDef->getField($agg->getField());
				if ($fld && $aggregate)
				{
                    $aggregate->setField($this->escapeField($fld, false, true));
                    $queryObject->addFacet($aggregate);
                }
            }
        }
        
        $resultSet = $this->query($collection->getObjType(), $queryObject);
        if ($resultSet == false)
            return -1;
        /*
        try
        {
            
            //echo "<pre>".var_export($resultSet, true)."</pre>";
        }
        catch (\Elastica\Exception\Response $ex)
        {
            if ($ex->getCode() == 0) // type missing
            {
                //$obj_typeateObjectTypeIndexElastic();
                $resultSet = $indType->search($queryObject);
            }
            else
            {
                //echo "<pre>".$ex->getCode()." - ".$ex->getMessage()."</pre>";
                return -1;
            }
        }
         */

        $collection->setTotalNum($resultSet->getTotalHits());
        
        // Initialize entities and put into collection
        $results = $resultSet->getResults();
        $num = count($results);
        for ($i = 0; $i < $num; $i++)
        {
            $doc = $results[$i];
            //$id = $doc->getId();

            $obj = Models\EntityFactory::factory($collection->getObjType());
            
            // Initailize entity data
            $data = $doc->getData();
            foreach ($data as $fn=>$fv)
            {
				$field = $objDef->getField($this->unescapeField($fn));
				if ($field)
					$fv = $this->unescapeFieldValue($field, $fv, $data);

                $obj->setValue($this->unescapeField($fn), $fv);
            }
            
            // There appears to be a bug where the uname is not set until subsequent syncs, so for now
            // just set manually. - skys
            if ($obj->getId() && !$obj->getValue("uname"))
                $obj->setValue("uname", $obj->getId());
            
            // Add entity to the collection
            $collection->addEntity($obj);
        }

        // Get aggregate values - in ES >= 1 aggregates are replacing facets
        $aggs = $resultSet->getAggregations();
        foreach($aggs as $name=>$data) 
        {
            $agg = $collection->getAggregation($name);
            // Translate data associative labels
            $dataToSet = array();
            switch($agg->getTypeName())
            {
            case 'terms':
                foreach ($data['buckets'] as $termdata) {
                    $dataToSet[] = array("count"=>$termdata['doc_count'], "term"=>$termdata['key']);
                }
                break;
            case 'avg':
            case 'sum':
            case 'min':
            case 'min':
                $dataToSet = $data['value'];
                break;
            case 'stats':
                // Fall through for 'count', 'min', 'max', 'avg', 'sum'
            default:
                $dataToSet = $data;
                break;
            }
            
            $agg->setData($dataToSet);
        }
        
        // LEGACY: Get facets
        $facets = $resultSet->getFacets();
        foreach($facets as $name=>$data) 
        {
            $agg = $collection->getAggregation($name);
            // Translate data associative labels
            $dataToSet = array();
            switch($agg->getTypeName())
            {
            case 'terms':
                foreach ($data['terms'] as $termdata) {
                    $dataToSet[] = array("count"=>$termdata['count'], "term"=>$termdata['term']);
                }
                break;
            case 'avg':
            case 'sum':
            case 'min':
            case 'min':
                $dataToSet = $data['value'];
                break;
            case 'stats':
                // Fall through for 'count', 'min', 'max', 'avg', 'sum'
            default:
                $dataToSet = $data;
                break;
            }
            
            $agg->setData($dataToSet);
        }

        return $num;
    }

	/**
	 * Create query condition string
	 *
	 * @param \Netric\Models\EntityDefinition $objDef The object definition to work with
	 * @param \Netric\Models\Collection\Where[] $conditions Array of where objects
	 * @return string The prepared condition string to use with this query
	 */
	private function buildConditionString($objDef, $conditions)
	{
		$cond_str = "";
		$inOrGroup = false;

        // Return if no conditions
		if (count($conditions)==0)
            return $cond_str;
        
        for ($i = 0; $i < count($conditions); $i++)
        {
            $cond = $conditions[$i];
            $blogic = $cond->bLogic;
            // If next condition is available, pull the boolean logic for query grouping at the end
            if ($i+1 < count($conditions))
                $next_blogic = $conditions[$i+1]->bLogic;
            else
                $next_blogic = "";
            $fieldName = $cond->fieldName;
            $fieldNameEs = $this->escapeField($objDef->getField($fieldName));
            $operator = $cond->operator;
            $condValue = $cond->value;
            $buf = "";

            // Look for associated object conditions
            if (strpos($fieldName, '.'))
            {
                $parts = explode(".", $fieldName);
            }
            else
            {
                $parts[0] = $fieldName;
            }

            $field = $objDef->getField($parts[0]);
            if (count($parts) > 1)
            {
                $fieldName = $parts[0];
                $ref_field = $parts[1];
                $field['type'] = "object_reference";
            }
            else
            {
                $ref_field = "";
            }


            if (!$field)
                continue; // Skip non-existant field

            // Build Query String
            // -----------------------------------------------------
            switch ($operator)
            {
            case 'is_equal':
                switch ($field["type"])
                {
                case 'object_reference':
                    /*
                    // TODO: review
                    $tmp_cond_str = "";
                    if ($field['subtype'] && $ref_field)
                    {
                        $tmpobj = new CAntObject($this->dbh, $field['subtype']);
                        $ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user, $conds);
                        $ol->addCondition("and", $ref_field, $operator, $condValue);
                        $tmp_obj_cnd_str = $ol->buildConditionString($conds);

                        if ($condValue == "" || $condValue == "NULL")
                        {
                            $buf .= " ".$this->obj->object_table.".$fieldNameEs not in (select id from ".$tmpobj->object_table."
                                                                                        where $tmp_obj_cnd_str) ";
                        }
                        else
                        {
                            $buf .= " ".$this->obj->object_table.".$fieldNameEs in (select id from ".$tmpobj->object_table."
                                                                                        where $tmp_obj_cnd_str) ";
                        }
                    }
                    */
                    break;
                case 'object_multi':
                case 'object':
                    if ($condValue == "" || $condValue == "NULL")
                    {
                        $buf .= "-$fieldNameEs:[* TO *] ";
                    }
                    else
                    {
                        if ($field['subtype'])
                        {
                                $buf .= "$fieldNameEs:\"$condValue\" "; // Numeric id
                        }
                        else
                        {
                            $parts = explode(":", $condValue); // obj_type:object_id

                            if (count($parts)==2)
                            {
                                $buf .= "$fieldNameEs:\"".$parts[0].":".$parts[1]."\" ";
                            }
                            else if (count($parts)==1) // only query assocaited type
                            {
                                $buf .= "$fieldNameEs:".$condValue."\:* ";
                            }
                        }
                    }
                    break;
                case 'fkey_multi':
                case 'fkey':
                    $tmp_cond_str = "";

                    if (is_numeric($condValue))
                    {
                        $tmp_cond_str = "$fieldNameEs:\"$condValue\"";

						$netricGroup = $this->getGroupingById($objDef->getObjType(), $field["name"], $condValue);
						foreach ($netricGroup->children as $grp)
						{
                            $tmp_cond_str .= " OR $fieldNameEs:\"" . $grp->id . "\" ";
						}

                        $tmp_cond_str = "($tmp_cond_str)";
                    }
					else if ($condValue && $condValue!="NULL")
                    {
                        $tmp_cond_str = "$fieldNameEs:\"$condValue\"";
                    }

                    if ($condValue == "" || $condValue == "NULL")
                    {
                        $buf .= "-$fieldNameEs:[* TO *] ";
                    }
                    else if ($tmp_cond_str)
                    {
                        $buf .= "$tmp_cond_str ";
                    }
                    break;

                case 'bool':
                case 'boolean':
                    if (true === $condValue || 1 == $condValue || 't' == $condValue)
                    {
                        $buf .= "$fieldNameEs:true ";
                    }
                    else
                    {
                        $buf .= "$fieldNameEs:false ";
                    }
                    //if ("" == $condValue || "f" == $condValue || "NULL" == $condValue || false === $condValue)
                    break;

                case 'date':
                case 'timestamp':
                    if ($condValue == "" || $condValue == "NULL")
                    {
                        $buf .= "-$fieldNameEs:[* TO *] ";
                    }
                    else
                    {
                        $time = @strtotime($condValue);
                        if ($time !== false)
                            $buf .= "$fieldNameEs:".gmdate("Ymd\\TG:i:s", $time)." ";
                    }
                    break;

                case 'number':
                case 'text':
                default:
                    if ($condValue == "" || $condValue == "NULL")
                        $buf .= "-$fieldNameEs:[* TO *] ";
                    else
                        $buf .= "$fieldNameEs:\"".$this->escapeValue($condValue)."\"";
                    break;
                }
                break;

            case 'is_not_equal':
                switch ($field["type"])
                {
                case 'object_reference':
                    /*
                    // TODO: review
                    $tmp_cond_str = "";
                    if ($field['subtype'] && $ref_field)
                    {
                        $tmpobj = new CAntObject($this->dbh, $field['subtype']);
                        $ol = new CAntObjectList($this->dbh, $field['subtype'], $this->objList->user, $conds);
                        $ol->addCondition("and", $ref_field, $operator, $condValue);
                        $tmp_obj_cnd_str = $ol->buildConditionString($conds);

                        if ($condValue == "" || $condValue == "NULL")
                        {
                            $buf .= " ".$this->obj->object_table.".$fieldNameEs not in (select id from ".$tmpobj->object_table."
                                                                                        where $tmp_obj_cnd_str) ";
                        }
                        else
                        {
                            $buf .= " ".$this->obj->object_table.".$fieldNameEs in (select id from ".$tmpobj->object_table."
                                                                                        where $tmp_obj_cnd_str) ";
                        }
                    }
                    */
                    break;
                case 'object_multi':
                case 'object':
                    if ($condValue == "" || $condValue == "NULL")
                    {
                        $buf .= "$fieldNameEs:[* TO *] ";
                    }
                    else
                    {
                        if ($field['subtype'])
                        {
                                $buf .= "-$fieldNameEs:\"$condValue\" "; // Numeric id
                        }
                        else
                        {
                            $parts = explode(":", $condValue); // obj_type:object_id

                            if (count($parts)==2)
                            {
                                $buf .= "-$fieldNameEs:\"".$parts[0].":".$parts[1]."\" ";
                            }
                            else if (count($parts)==1) // only query assocaited type
                            {
                                $buf .= "-$fieldNameEs:".$objDef->obj_type."\:* ";
                            }
                        }
                    }
                    break;
                case 'fkey_multi':
                case 'fkey':
                    $tmp_cond_str = "";
                    /*
                    if (isset($field['fkey_table']["parent"]) && is_numeric($condValue))
                    {
                        $children = $this->objList->getHeiarchyDown($field["subtype"], $field['fkey_table']["parent"], $condValue);
                        $tmp_cond_str = "";
                        foreach ($children as $child)
                        {
                            if ($tmp_cond_str) $tmp_cond_str .= " OR ";
                            $tmp_cond_str .= "$fieldNameEs:\"$child\"";
                        }
                        if ($tmp_cond_str)
                            $tmp_cond_str = "($tmp_cond_str)";
                    }
                    else */
                    if ($condValue && $condValue!="NULL")
                    {
                        $tmp_cond_str = "$fieldNameEs:\"$condValue\"";
                    }

                    if ($condValue == "" || $condValue == "NULL")
                    {
                        $buf .= "$fieldNameEs:[* TO *] ";
                    }
                    else if ($tmp_cond_str)
                    {
                        $buf .= "-$tmp_cond_str ";
                    }
                    break;

                case 'bool':
                    if ($condValue == "" || $condValue == "f" || $condValue == "NULL")
                    {
                        $buf .= "$fieldNameEs:true ";
                    }
                    else
                    {
                        $buf .= "$fieldNameEs:false ";
                    }
                    break;

                case 'number':
                case 'text':
                default:
                    if ($condValue == "" || $condValue == "NULL")
                        $buf .= "$fieldNameEs:[* TO *] ";
                    else
                        $buf .= "-$fieldNameEs:".$this->escapeValue($condValue);
                    break;
                }
                break;

            case 'is_greater':
                switch ($field["type"])
                {
                case 'object_multi':
                case 'object':
                case 'fkey_multi':
                    break;
                case 'text':
                    break;
                case 'number':
                    $buf .= "$fieldNameEs:{".$condValue." TO *} ";
                    break;
                case 'date':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:{".gmdate("Ymd\\TG:i:s", $time)." TO *} ";
                    break;
                case 'timestamp':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:{".gmdate("Ymd\\TG:i:s", $time)." TO *} ";
                    break;
                default:
                    break;
                }
                break;
            case 'is_less':
                switch ($field["type"])
                {
                case 'object_multi':
                case 'object':
                case 'fkey_multi':
                    break;
                case 'text':
                    break;
                case 'number':
                    $buf .= "$fieldNameEs:{* TO $condValue} ";
                    break;
                case 'date':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:{* TO ".gmdate("Ymd\\TG:i:s", $time)."} ";
                    break;
                case 'timestamp':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:{* TO ".gmdate("Ymd\\TG:i:s", $time)."} ";
                    break;
                default:
                    break;
                }
                break;
            case 'is_greater_or_equal':
                switch ($field["type"])
                {
                case 'object_multi':
                case 'object':
                case 'fkey_multi':
                    break;
                case 'text':
                    break;
                case 'number':
                    $buf .= "$fieldNameEs:[$condValue TO *] "; // square brackets are inclusive
                    break;
                case 'date':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", $time)." TO *] ";
                    break;
                case 'timestamp':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:[".gmdate("Ymd\\TG:i:s", $time)." TO *] ";
                    break;
                default:
                    break;
                }
                break;
            case 'is_less_or_equal':
                switch ($field["type"])
                {
                case 'object_multi':
                case 'object':
                case 'fkey_multi':
                    break;
                case 'text':
                    break;
                case 'number':
                    $buf .= "$fieldNameEs:[* TO $condValue] "; // square brackets are inclusive
                    break;
                case 'date':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:[* TO ".gmdate("Ymd\\TG:i:s", $time)."] ";
                    break;
                case 'timestamp':
                    $time = @strtotime($condValue);
                    if ($time !== false)
                        $buf .= "$fieldNameEs:[* TO ".gmdate("Ymd\\TG:i:s", $time)."] ";
                    break;
                default:
                    break;
                }
                break;
            case 'begins':
            case 'begins_with':
                switch ($field["type"])
                {
                case 'text':
                    $buf .= "$fieldNameEs:".strtolower($this->escapeValue($condValue))."* ";
                    break;
                default:
                    break;
                }
                break;
            case 'contains':
                switch ($field["type"])
                {
                case 'text':
                    $buf .= "$fieldNameEs:*".strtolower($this->escapeValue($condValue))."* ";
                    break;
                default:
                    break;
                }
                break;
            }

            // New system to added to group "or" statements
            if ($blogic == "and")
            {
                if ($buf)
                {
                    if ($cond_str) 
                        $cond_str .= ") ".strtoupper($blogic)." (";
                    else
                        $cond_str .= " ( ";
                    $inOrGroup = true;
                }

            }
            else if ($cond_str && $buf) 
            {
                // or
                $cond_str .= " ".strtoupper($blogic)." ";
            }

            // Fix problem with lucene not being able to interpret pure negative queries - change (-field) to (*:* -field)
            if (substr($buf, 0, 1) == "-")
                $buf = "*:* ".$buf;
            $cond_str .= $buf;
        }

        // Close condtion grouping
        if ($inOrGroup)
            $cond_str .= ")";

		return $cond_str;
	}
    
    /**
     * 
     * @param type $objType
     * @param type $query
     * @return \Elastica\ResultSet
     */
    private function query($objType, $query)
    {
        $indType = $this->getType($objType);
        
        try
        {
            $resultSet = $indType->search($query);
        }
        catch (\Elastica\Exception\ResponseException $ex)
        {
            // Make sure the index exists
            $this->createIndex();
            
            
            if ($ex->getCode() == 0) // type missing
            {
                //$obj_typeateObjectTypeIndexElastic();
                $resultSet = $indType->search($query);
            }
            else
            {
                
                //echo "<pre>".$ex->getCode()." - ".$ex->getMessage()."</pre>";
                return false;
            }
        }
        
        return $resultSet;
    }

    /**
	 * Delete all entities of a specific type
	 *
     * @param string $objType The object type name
	 * @return bool true on success, false on failure
	 */
	public function deleteAllEntities($objType)
	{
        $indType = $this->getType($objType);
        
        try {
            $indType->delete();
        }
        catch (\Exception $ex) {
            
        }
		return true;
	}
}
