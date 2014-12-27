<?php
/**
 * An identity map ensures that each object gets loaded only once per transaction
 * by keeping every loaded object in a map and look it up before loading the data in the datamapper
 */
namespace Netric\Models;

abstract class IdentityMapAbstract
{
    /**
     * The current data mapper we are using for this object
     * 
     * @var DataMapperInterface
     */
	protected $dataMapper = null;
    
    /**
     * Set up the identity mapper for loading objects
     */
    public function __construct()
    {
    
    }
}
