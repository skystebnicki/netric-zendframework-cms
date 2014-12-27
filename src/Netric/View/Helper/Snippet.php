<?php

/**
 * Short description for file
 * 
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * @author Sky Stebnicki <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\View\Helper;
use Zend\View\Helper\AbstractHelper;

/**
 * Hanlde getting a snippet by id and loading
 */
class Snippet extends AbstractHelper
{
    /**
     * The service manager
     * 
     * @param Zend\Services\ServiceManager $sm
     */
    protected $serviceManager = null;
    
    /**
     * Inject service manager dependency
     * 
     * @param Zend\Services\ServiceManager $sm
     */
    public function setServiceManager($sm)
    {
        $this->serviceManager = $sm;
    }
    
    /**
     * Invoke this helper
     * 
     * @param string $id The unique id of the snippet to load
     * @return string
     */
    public function __invoke($id)
    {
        if (!$id){
            return 'ERROR: id must be string';
        }
 
        if ($this->serviceManager == null){
            return 'ERROR: service manager not set';
        }
        
        $entLoader = $this->serviceManager->get('EntityLoader');
        
        // Get the ministry by the uname
        $snippet = $entLoader->get("cms_snippet", $id);
 
        return $snippet->getValue("data");
    }
}