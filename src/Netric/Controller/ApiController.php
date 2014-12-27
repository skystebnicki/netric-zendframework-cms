<?php
/**
 * Controller used as an api callback
 *
 * @copyright Copyright (c) 2005-2013 Aereus Coproration
 * @author Sky Stebnicki (sky.stebnicki@aereus.com)
 */

namespace Netric\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class ApiController extends AbstractActionController
{
    public function indexAction()
    {
        $config = $this->getServiceLocator()->get('Config');
        
        $response = $this->getResponse();
        $response->setContent("Index"); 
        return $response;
        //return new ViewModel();
    }
    
    /**
     * Synchronize one object by grabbing from remote store and saving locally
     * 
     * Netric can call http://<siteurl>/netric/api/sync-object?obj_type=<obj_type>&oid=<object_id>
     * to push object updates in real time.
     */
    public function syncObjectAction()
    {
        $otype = $this->getRequest()->getQuery("obj_type");
        $oid = $this->getRequest()->getQuery("oid");
        
        $ret = 0;
        
        if ($otype && $oid)
        {
            $dmLocal = $this->getServiceLocator()->get("DataMapper");
            $dmApi = $this->getServiceLocator()->get('DataMapperApi');
            $ret = $dmApi->syncOne($otype, $oid, $dmLocal);
        }
        
        if (false === $ret)
            $ret = 0;
        
        // Return the id of the saved object, or 0 on failure
        $response = $this->getResponse();
        $response->setContent($ret); 
        return $response;
    }


    /**
     * Enter edit mode by setting a cookie
     */
    public function entereditAction()
    {
		// Creates cookies to allow user enter edit mode
        $expireTime = time() + 3600;
		setcookie("cms_edit", "1", $expireTime, "/");

		return $this->redirect()->toRoute('apphome');
    }
}
