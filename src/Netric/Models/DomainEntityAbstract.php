<?php
/**
 * The entity is the base object
 */

namespace Netric\Models;

abstract class DomainEntityAbstract
{
	protected $_id;

	/**
	 * Get unique id of this object
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Synchronize remote data with local store
	 */
	public function sync()
	{
	}
}
