<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Netric;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use NetricSDK\NetricApi;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $e->getApplication()->getServiceManager()->get('translator');
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

	public function getServiceConfig()
	{
		// This is an example of how a service can be called
		// from a controller with:
		// $postloader = $this->getServiceLocator()->get("PostLoader");
		return array(
			'factories' => array(
				'CmsNavigation' => 'Netric\Navigation\CmsNavigationFactory',
                
                // Return a referenec to an identitymapper for loading entities
                'EntityLoader' => function($sm) {          
                    $im = new \Netric\Models\IdentityMap($sm->get("NetricApi"));
                    return $im;
                },

                'NetricApi' => function($sm) {
                    $config = $sm->get('Config');
                    return new NetricApi(
                        $config["netric"]["server"],
                        $config["netric"]["applicationId"],
                        $config["netric"]["applicationKey"]
                    );
                },
                        
                // Return a reference to an identitymapper for loading entities
                'Searcher' => function($sm) {          
					$netricApi = $sm->get("NetricApi");
					$searcher = new \Netric\Search\Searcher($netricApi);
                    return $searcher;
                },
				
			),
		);
	}
    
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'cmsSnippet' => function($sm) {
                    $helper = new View\Helper\Snippet();
                    $helper->setServiceManager($sm->getServiceLocator());
                    return $helper;
                }
            )
        );   
   }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    "Elastica" => __DIR__ . '/src/Elastica',
                ),
            ),
        );
    }
}
