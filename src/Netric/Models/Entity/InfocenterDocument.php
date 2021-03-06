<?php
/**
 * Handle infocenter documents
 */

namespace Netric\Models\Entity;

use Netric\Models\EntityAbstract;

/**
 * Base object class
 */
class InfocenterDocument extends EntityAbstract
{
    public function __construct() {
        parent::__construct("infocenter_document");
    }
    
    /**
     * Generate a teaser for this post
     * 
     * @param string $wordLengh The maximum number of words to return
     * @return string The teaster
     */
    public function getTeaser($wordLength=25)
    {
        return implode(' ', array_slice(explode(' ', strip_tags($this->getValue("body"), "<br/>")), 0, $wordLength));
    }
}
