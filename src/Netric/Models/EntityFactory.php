<?php
/**
 * This class will be used to instantiate entities
 */

namespace Netric\Models;

/**
 * Factory class
 */
class EntityFactory
{
    /**
     * This function is responsible for loading subclasses or the base class
     * 
     * @param string $objType
     * @param string $oid
     */
    public static function factory($objType, $oid="")
    {
        switch ($objType)
        {
        case "content_feed_post":
            return new \Netric\Models\Entity\ContentFeedPost();
        case "infocenter_document":
            return new \Netric\Models\Entity\InfocenterDocument();
        case "customer":
            return new \Netric\Models\Entity\Customer();
        default:
            return new \Netric\Models\Entity\Base($objType);
        }        
        
        return false;
    }
}
