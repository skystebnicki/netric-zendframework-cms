<?php
/**
 * This is the default entity/object that will be instantiated if there is no subclassed entity
 */


namespace Netric\Models\Entity;

use Netric\Models\EntityAbstract;

/**
 * Base object class
 */
class Customer extends EntityAbstract
{
    public function __construct() {
        parent::__construct("customer");
    }

	/**
	 * Load customer id by email address
	 *
	 * Will load the values for this entity from the datamapper if a customer is found with
	 * the passed email address.
	 *
	 * @param string $email The email address to lookup
	 * @param \Netric\Models\DataMapperInterface $dm The datamapper to query - usually the API
	 * @return \Netric\Models\Entity\Customer If a match is found
	 */
	public static function loadByEmail($email, \Netric\Models\DataMapperInterface $dm)
	{
		$collection = new \Netric\Models\EntityCollection("customer");
		$collection->where("email")->equals($email);
		$collection->orWhere("email2")->equals($email);
        $num = $dm->loadCollection($collection);
		if ($collection->getTotalNum() > 0)
        {
            $entity = $collection->getEntity(0); // pull the first
            if ($entity->getId())
            {
				return $entity;
            }
        }

		return null;
	}
}
