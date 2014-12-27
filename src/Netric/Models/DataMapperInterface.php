<?php

namespace Netric\Models;

interface DataMapperInterface
{
	/**
     * #param string $objType The type of object we are loading
	 * @param string $id The id of the object to get
	 * @return Post
	public function fetchById($objType, $id);
	 */
    
    /**
	 * Get object definition based on an object type
	 *
     * @param string $key The unique key to get a value for
     * @param string $value The value of the key to store
	 * @return EntityDefinition
	public function getDefinition($objType);
	 */
}
