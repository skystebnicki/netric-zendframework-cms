<?php
/*
 * This is the api data mapper that will gather object data from netric through the REST api
 */
namespace Netric\Models\DataMapper;
use Netric\Models;

class ApiDataMapper extends Models\DataMapperAbstract  implements Models\DataMapperInterface
{
    /**
     * The server name where netric is running
     * 
     * @var string
     */
    protected $server = "";

    /**
     * Username of an active netric user
     *
     * @var string
     */
    protected $username = "";
    
    /**
     * Password for the user account
     *
     * @var string
     */
    protected $password = "";
    
    /**
     * Use https
     * 
     * @param bool
     */
    public $https = true;
    
    /**
     * Debug flag used for printing progress
     * 
     * @param bool
     */
    public $debug = false;
    
    /**
     * Construct connection to backend
     * 
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $objType
     */
    public function __construct($server, $username, $password) 
    {
        $this->server = $server;
        $this->username  = $username;
        $this->password = $password;
        
        if (!$this->server)
            throw new Exception("Server param is reuqired for the netric API");
        
        if (!$this->username)
            throw new Exception("Username param is reuqired for the netric API");
        
        if (!$this->server)
            throw new Exception("Password param is reuqired for the netric API");
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
        $def = $this->getDefWithLoader($obj->getObjType());
        
        $params = array("obj_type"=>$objType, "oid"=>$id);
		$ret = $this->sendRequest("Object", "getObject", $params);

        if ($ret!=-1)
        {
            $data = json_decode($ret, true);
               
            foreach ($data as $fname=>$value)
            {
                if ($fname == "security")
                    continue;
                
                if (substr($fname, -5) != "_fval")
                {
					$field = $def->getField($fname);

                    if (is_array($value))
                    {
						foreach ($value as $mval)
						{
							$mval = $this->unescapeFieldValue($field, $mval);

							// Get foreign value if set
							$fval = (isset($data[$fname . "_fval"])) ? $data[$fname . "_fval"][$mval] : null;

							// Set multi value in entity
							$obj->addMultiValue($fname, $mval, $fval);
						}        
                    }
                    else
                    {
						if ($field)
							$value = $this->unescapeFieldValue($field, $value);

						// Get foreign value if set
						$fval = (isset($data[$fname . "_fval"])) ? $data[$fname . "_fval"][$value] : null;

						// Set entity
                        $obj->setValue($fname, $value, $fval);
                    }
                }
            }
        }
        
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
        $data = array("obj_type"=>$objType, "oid"=>$id);
        $resp = $this->sendRequest("Object", "deleteObject", $data);
        return ($resp) ? json_decode($resp, true) : false;
    }
    
    /**
	 * Save object data
	 *
	 * @return int|boolean Id of saved entity on success, false on failure
	 */
	public function save($entity)
	{
		$data = array("obj_type"=>$entity->getObjType());

		if ($entity->getId())
            $data["oid"] = $entity->getId();

        $def = $this->getDefWithLoader($entity->getObjType());
		$fields = $def->getFields();
		if (is_array($fields) && count($fields))
		{
			foreach ($fields as $field=>$fdef)
			{
                $value = $entity->getValue($field);
			
				/*
                // Handle multi-values
                if (is_array($value))
                {
                    $data[$field] = array();
                    foreach ($value as $subval)
                        $data[$field][] = $subval;
                }
                else
                {
                    if (true === $value)
                        $value = 't';
                    if (false === $value)
                        $value = 'f';
                    
                    $data[$field] = $value;
                }
				*/

				$data[$field] = $this->escapeFieldValue($fdef, $value);
            }
        }
		
		$resp = $this->sendRequest("Object", "saveObject", $data);

        $ret = ($resp) ? json_decode($resp, true) : 0;

        if(!is_array($ret) && $ret > 0)
        {
            $entity->setValue("id", $ret);
            // Refresh to get default values
            //$this->open($this->id, true);
        }

		return ($ret) ? $ret : false;
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
     * Send a request using the php api for netric
     * 
     * @param string $controller Controller name to call
     * @param string $action The name of the action to call in the selected controller
     * @param array $data Params (assoc) to be sent to the controller
     * @param string $server Valid netric server
     * @param string $user Valid netric username
     * @param string $pass Valid netric password
     * @return mixed -1 on falure, string resonse on success
     * @throws Exception
     */
    protected function sendRequest($controller, $action, $data, $server=null, $user=null, $pass=null)
	{
		if (!$server && isset($this))
			$server = $this->server;

		if (!$user && isset($this))
			$user = $this->username;

		if (!$pass && isset($this))
			$pass = $this->password;

		$url = ($this->https) ? "https://" : "http://";

		if (!$server)
			throw new Exception("Server is a required param to send requests through the AntApi");
        
        if (!$user)
			throw new Exception("User  is a required param to send requests through the AntApi");
        
        if (!$pass)
			throw new Exception("Password is a required param to send requests through the AntApi");

		$url .= $server . "/api/php/$controller/$action";
		$url .= "?auth=".base64_encode($user).":".md5($pass);

        $ret = -1; // Assume fail
           
		$fields = "";
		foreach ($data as $fname=>$fval)
		{

			if (is_array($fval))
			{
				foreach ($fval as $subval)
				{
					if ($fields) $fields .= "&";

					$fields .= $fname . "[]=" . urlencode($subval);
				}
			}
			else
			{
				if ($fields) $fields .= "&";

				$fields .= $fname . "=" . urlencode($fval);
			}
		}

		$ch = curl_init($url); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		return $resp;
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
        $params = array("obj_type"=>$objType);
		$ret = $this->sendRequest("Object", "getDefinition", $params);
        $defData = ($ret) ? json_decode($ret) : null;

        $def = new Models\EntityDefinition($objType);
        
        if ($defData && !isset($defData->error))
        {
            foreach ($defData->fields as $field)
            {
                $data = array(
                    'name' => $field->name,
                    'title' => $field->title,
                    'type' => $field->type,
                    'subtype' => $field->subtype,
                    'system' => $field->system,
                    'required' => $field->required,
                );
                $def->setField($field->name, $data);
            }

			if (isset($defData->parent_field))
				$def->setParentField($defData->parent_field);
        }
        
        return $def;
    }
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $objType The object type name
     * @param string $fieldName The field name to get grouping data for
	 * @return \Netric\Models\EntityGrouping[]
	 */
	public function getGroupings($objType, $fieldName)
    {
        if (!$objType || !$fieldName)
            return array();
            
        $params = array("obj_type"=>$objType, "field"=>$fieldName);
		$ret = $this->sendRequest("Object", "getGroupings", $params);
        $groupsData = ($ret) ? json_decode($ret) : null;

        // Initialize heiarachial array of groupings
        $groupings = $this->setGroupingAray($groupsData);
        
        return $groupings;
    }

	/**
	 * Add a grouping
	 *
	 * @param string $objType The type name of the object we are adding a group for
	 * @param string $fieldName The name of the field that references the group
	 * @param string $sName The name of the new group
	 * @param string $iParent Optional parent group
	 */
	public function createGrouping($objType, $fieldName, $sName, $iParent=null)
	{
        $params = array("obj_type"=>$objType, "field"=>$fieldName, "title"=>$sName, "parent_id"=>$iParent);
		$ret = $this->sendRequest("Object", "createGrouping", $params);
        $groupData = ($ret) ? json_decode($ret) : null;
		$aGroups = $this->setGroupingAray(array($groupData));
		return $aGroups[0];
	}
    
    /**
     * Initialize heiarachial array of groupings
     * 
     * @param type $groupsData
     * @return \Netric\Models\EntityGrouping[]
     */
    private function setGroupingAray($groupsData)
    {
        $groupings = array();

        if ($groupsData && !isset($groupsData->error))
        {
            foreach ($groupsData as $grpData)
            {
                $grp = new Models\EntityGrouping();
                
                foreach ($grpData as $fname=>$fval)
                {
                    switch($fname)
                    {
                    case "heiarch":
                        $fname = "isHeiarch";
                        break;
                    case "parent_id":
                        $fname = "parantId";
                        break;
                    case "sort_order":
                        $fname = "sortOrder";
                        break;
                    default:
                        break;
                    }
                    
                    $grp->setValue($fname, $fval);
                }
                
                /*
                $grp->id = $grpData->id;
                $grp->uname = $grpData->uname;
                $grp->title = $grpData->title;
                $grp->isHeiarch = $grpData->heiarch;
                $grp->parantId = $grpData->parent_id;
                $grp->color = $grpData->color;
                $grp->sortOrder = $grpData->sort_order;
                
                 */
                
				if (isset($grpData->children))
                	$grp->children = $this->setGroupingAray($grpData->children);
                
                $groupings[] = $grp;
            }
        }
        
        return $groupings;
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
        $def = $this->getDefWithLoader($collection->getObjType());
        
        /*
		if ($this->resultType)
			$url .= "&type=".$this->resultType;
        */
        $totalNum = 0;
		$conditions = $collection->getWheres();
		$orderBy = $collection->getOrderBy();
        $fullTextSearch = "";

        $data = array(
            "obj_type" => $collection->getObjType(),
            "offset" => $collection->getOffset(),
            "limit" => $collection->getLimit(),
         );
		for ($i = 0; $i < count($conditions); $i++)
		{
            // Get full-text search if set
            if ($conditions[$i]->fieldName == "*")
            {
                $fullTextSearch = $conditions[$i]->value;
                continue;
            }

            $ind = $i+1;
                        
            if (!isset($data['collections']) || !is_array($data['collections']))
                $data['conditions'] = array();
            
            // Convert boolean
            if ($conditions[$i]->value === true)
                $conditions[$i]->value = 't';
            if ($conditions[$i]->value === false)
                $conditions[$i]->value = 'f';
            
            // Add condtions to array
            $data['conditions'][] = $ind;
            $data["condition_blogic_" . $ind] = $conditions[$i]->bLogic;
            $data["condition_fieldname_" . $ind] = $conditions[$i]->fieldName;
            $data["condition_operator_" . $ind] = $conditions[$i]->operator;
            $data["condition_condvalue_" . $ind] = $conditions[$i]->value;
		}

        /*
		foreach ($this->facetFields as $fname=>$mincount)
		{
			$fields .= "&facet[]=".rawurlencode($fname);
		}
        */
        
        $data['order_by'] = array();
		foreach ($orderBy as $order)
            $data['order_by'][] = $order['field'] . " " . $order['direction'];
            
        // Set Full Text Search
        if($fullTextSearch!="")
            $data['cond_search'] = $fullTextSearch;

        $resp = $this->sendRequest("ObjectList", "query", $data);
        $ret = ($resp) ? json_decode($resp, true) : null;
        
        // Check if facet is available
        if(isset($ret['facets']))
        {
            // Loop to get facet names
            foreach($ret['facets'] as $key=>$facet)
            {
                $facetName = $facet['name'];
                
                // Loop to get facet fields
                foreach($facet['terms'] as $key=>$term)
                {
                    $collection->addFacetCount($facetName, $term['term'], $term['count']);
                }
            }
        }
        
        if(isset($ret['totalNum']))
            $collection->setTotalNum($ret['totalNum']);
        
        /*
        if(isset($ret['paginate']))
        {
            if(isset($ret['paginate']['nextPage']))
                $collection->setNextPageOffset($ret['paginate']['nextPage']);
            
            if(isset($ret['paginate']['prevPage']))
                $collection->setPrevPageOffset($ret['paginate']['prevPage']);
        }
         */
        
        // Get Returned Objects
        $num = 0;
        if(isset($ret['objects']))
        {
            // Lets clear the objects list before assigning objects
            $this->m_objectList = array();
            
            for ($i = 0; $i < count($ret['objects']); $i++)
            {
                $obj = Models\EntityFactory::factory($collection->getObjType());

                // Initailize entity data
                $data = $ret['objects'][$i];
                foreach ($data as $fn=>$fv)
                {                   
                    if (is_array($fv))
                    {
                        if (isset($fv['key']))
                        {
                            $obj->setValue($fn, $fv['key'], $fv['value']);
                        }
                        else
                        {
                            foreach ($fv as $mval)
                                $obj->addMultiValue($fn, $mval['key'], $mval['value']);
                        }
                    }
                    else
                    {
						$field = $def->getField($fn);
						if ($field)
							$fv = $this->unescapeFieldValue($field, $fv);

                        $obj->setValue($fn, $fv);
                    }
                }

                // Add entity to the collection
                $collection->addEntity($obj);
            }
            
            $num = count($ret["objects"]);
        }
        
        return $num;
    }
    
    /**
     * Sync a single entity/object from the remote api to a local datamapper
     * 
     * @param string $objType The name of the objec type to get
     * @param string $id The id of the object to get
     * @param \Netric\Models\DataMapper\DataMapperInterface $dm The local datamapper to sync to
     */
    public function syncOne($objType, $id, \Netric\Models\DataMapperInterface $dm)
    {
        // 1 Get from remote api
        $entity = $this->fetchById($objType, $id);
        
        if ($entity->getId())
        {
            if (true === $entity->getValue("f_deleted"))
            {
                $dm->deleteById($objType, $id);
                return $id;
            }
            else
            {
                return $dm->save($entity);
            }
        }
        
        return false; // fail if not saved above
    }
    
    /**
     * Sync an entity/object collection from the remote api to a local datamapper
     * 
     * This is often used to get all of a single type of object and store it locally
     * 
     * @param string $objType The name of the objec type to get
     * @param string $id The id of the object to get
     * @param \Netric\Models\DataMapperInterface $dm The local datamapper to sync to
     */
    public function syncCollection($objType, \Netric\Models\DataMapperInterface $dm, $printProgress=false)
    {
		// Empty current local collection
        try {
            $dm->deleteAllEntities($objType);
        }
        catch (\Exception $ex)
		{
            // TODO: ignore, index may not exist
        }
        
		// Populate collection 
        // TODO: should we add conditions here?
        $collection = new Models\EntityCollection($objType);
        $num = $this->loadCollection($collection);
        if ($printProgress)
            echo "Found " . $collection->getTotalNum() ." $objType\n";
        
        // Update the definition
        if ($num)
            $this->syncDefAndGroupings($objType, $dm);
        
        for ($i = 0; $i < $collection->getTotalNum(); $i++)
        {
            $entity = $collection->getEntity($i);
            if ($entity->getId())
            {
                if ($printProgress)
                    echo "Synchronized:\t " . $objType . "[" . $entity->getId() . "]\n";
                $dm->save($entity);
            }
        }
    }

    /**
     * Syncrhonze a definition and all groupings from the remote server to the local datastore
     * 
     * @param string $objType
     * @param \Netric\Models\DataMapperInterface $dm Local datastore
     */
    public function syncDefAndGroupings($objType, \Netric\Models\DataMapperInterface $dm)
    {
        $def = $this->getDefinition($objType);
        $dm->saveDefinition($def);
            
        // Get grouping data and save it locally
        $fields = $def->getFields();
        foreach ($fields as $field)
        {
            if ("fkey" == $field['type'] || "fkey_multi" == $field['type'])
            {
                $groupings = $this->getGroupings($objType, $field['name']);
                $dm->saveGroupings($objType, $field['name'], $groupings);
                
            }
        }
    }

	/**
	 * Take data array with escaped names and unescape
	 *
	 * @param array $field Assoc definition of array
	 * @param mixed $val The value to unescape after being returned from the elastic store
	 */
	protected function unescapeFieldValue($field, $val)
	{
		if (empty($val))
			return $val;

		switch ($field['type'])
		{
		case 'time':
		case 'timestamp':
		case 'date':
			$val = strtotime($val);
			break;
		case 'bool':
		case 'boolean':
			$val = ($val == "true" || $val === true || $val == "t") ? true : false;
			break;
		}

		return $val;
	}

	/**
	 * Escape entity values to send to netric api
	 *
	 * @param array $field assoc definition of field
	 * @param mixed $value The value of the entity field
	 * @return netric api escaped value
	 */
	protected function escapeFieldValue($field, $value)
	{
		$ret = $value;

		// Handle multi-values
		if (!is_array($value))
		{
			switch ($field['type'])
			{
			case 'bool':
			case 'boolean':
				if (true === $value)
					$ret = 't';
				if (false === $value)
					$ret = 'f';
				break;
			case 'time':
			case 'timestamp':
				$ret = date("m/d/Y h:i:s A T", $value);
				break;
			case 'date':
				$ret = date("m/d/Y T", $value);
				break;
			}
			
		}

		return $ret;
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
