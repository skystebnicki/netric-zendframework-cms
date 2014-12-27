<?php
/**
 * Handle cms pages
 */

namespace Netric\Models\Entity;

use Netric\Models\EntityAbstract;

/**
 * Base object class
 */
class CmsPage extends EntityAbstract
{
    public function __construct() {
        parent::__construct("cms_page");
    }
    
	/**
	 * Generate navigation data for sub-pages
	 *
	 */
	public function getNavData() {
	}
}
