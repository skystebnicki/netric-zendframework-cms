<?php
/**
 * Search over multiple object types
 */
namespace NetricZend\Search;

use NetricSDK\NetricApi;

class Searcher
{
	/**
	 * Array of datamappers for each type to search for
	 *
	 * @var NetricApi
	 */
	private $netricApi = null;

	/**
	 * Object types to query
	 *
	 * @var array
	 */
	private $docTypes = array();

	/**
	 * Constructor
	 *
	 * @param NetricApi $netricApi Instance of API to make calls to the server
	 */
	public function __construct(NetricApi $netricApi)
	{
		$this->netricApi = $netricApi;
	}

	/**
	 * Add a type to search for using the datamapper
	 *
	 * @param string $objType The unique name of the type of object to query
     * @return DocType
	 */
	public function addType($objType)
	{
		$objType = new DocType($objType);
		$this->docTypes[] = $objType;
		return $objType;
	}

	/**
	 * Search and aggregage the results
	 *
	 * @param string $text The fulltext query
	 * @param int $offset The offset page number to load
	 * @param int $limit The maximum number of items to load per page
     * @return array [
     *  "title"=>"doc title",
     *  "type" => 'objectType',
     *  "url" => $url,
     *  "teaser" => $ent->getTeaser(25),
     *  "date" => $ent->getValue("time_entered"),
     *  "type_label" => "Customer"
     * }
	 */
	public function query($text, $offset = 0, $limit=1000)
	{
		$ret = array();
		if (!$text)
			return $ret;

        foreach($this->docTypes as $obj) // Loop all doctypes that have been set
        {
			$type = $obj->type;
			$collection = $this->netricApi->createEntityCollection($type);

            foreach($obj->conditions as $key=>$cond) // Add conditions to the object list
			{
				if ($cond['blogic'] == "and")
				{
					$where = $collection->where($cond['field']);
				}
				else
				{
					$where = $collection->orWhere($cond['field']);
				}

				// Set referenced properties
				$where->operator = $cond['operator'];
				$where->value = $cond['value'];
			}

			if (count($obj->searchFields) > 0)
			{
				for ($i = 0; $i < count($obj->searchFields); $i++)
				{
					if (0 == $i)
						$collection->where($obj->searchFields[$i])->contains($text);
					else
						$collection->orWhere($obj->searchFields[$i])->contains($text);
				}
			}

			// Default condition if none set
			if (count($obj->conditions) == 0)
				$collection->where("id")->doesNotEqual("");

			//$postCollection->orderBy("time_entered", "DESC");
			$collection->setLimit($limit);
			$num = $collection->load();
			$posts = array();
			for ($i = 0; $i < $num; $i++)
			{
				$ent = $collection->getEntity($i);
            
                $url = $ent->getValue("uname");
                
                if($obj->urlBase)
                    $url = $obj->urlBase . "/$url";
                
				$doc = array(
					"title" => $ent->getName(), 
					"type" => $type, 
					"url" => $url, 
					"teaser" => $ent->getTeaser(25), 
					"date" => $ent->getValue("time_entered"),
					"type_label" => ($obj->typeLabel) ? $obj->typeLabel : $type,
				);
                
                // get additional fields
                foreach($obj->fields as $fldKey=>$field)
                {
                    $doc[$field['label']] = $ent->getValue($field['name'], $field['foreign']);
                }
				
				$ret[] = $doc;
            }
        }
        
        return $ret;
	}
}
