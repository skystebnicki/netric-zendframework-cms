<?php
/**
 * Search over multiple object types
 */
namespace Netric\Search;

use Netric\Models\Collection;

class Searcher
{
	/**
	 * Array of datamappers for each type to search for
	 *
	 * @var Netric\Models\DataMapperInterface
	 */
	private $dataMapper = null;

	/**
	 * Object types to query
	 *
	 * @var array
	 */
	private $docTypes = array();

	/**
	 * Constructor
	 *
	 * @param Netric\Models\DataMapperInterface $dm DataMapper for this type
	 */
	public function __construct(\Netric\Models\DataMapperInterface $dm)
	{
		$this->dataMapper = $dm;
	}

	/**
	 * Add a type to search for using the datamapper
	 *
	 * @param string $objType The unique name of the type of object to query
	 */
	public function addType($objType)
	{
		$objType = new \Netric\Search\DocType($objType);
		$this->docTypes[] = $objType;
		return $objType;
	}

	/**
	 * Search and aggregage the results
	 *
	 * @param string $text The fulltext query
	 * @param int $pageNum The offset page number to load
	 * @param int $limit The maximum number of items to load per page
	 */
	public function query($text, $offset = 0, $limit=1000)
	{
		$ret = array();
		if (!$text)
			return $ret;

        foreach($this->docTypes as $obj) // Loop all doctypes that have been set
        {
			$type = $obj->type;
			$collection = new \Netric\Models\EntityCollection($type);
			$collection->setDataMapper($this->dataMapper);

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
