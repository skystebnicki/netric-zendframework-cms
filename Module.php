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
			'invokables' => array(
				'PostDataMapper' => "Netric\Models\PostDataMapper",
			),
			'factories' => array(
				'CmsNavigation' => 'Netric\Navigation\CmsNavigationFactory',
                
                // Return a referenec to an identitymapper for loading entities
                'EntityLoader' => function($sm) {          
                    $im = new \Netric\Models\IdentityMap($sm->get("DataMapper"));
                    return $im;
                },
                        
                // Get the local configured datamapper
                'DataMapper' => function($sm) {
                    $config = $sm->get('Config');
                    
                    // Create datamapper based on configuration
                    switch ($config["db"]["type"])
                    {
                    case "elastic":
                        $dataMapper = new \Netric\Models\DataMapper\ElasticDataMapper($config["db"]["host"], 
                                                                                      $config["db"]["name"]);
                        break;
                    
                    default:
                        $dataMapper = $sm->get("DataMapperApi");
                        break;
                    }
            
                    return $dataMapper;
                },
                        
                // Get the api configured datamapper
                'DataMapperApi' => function($sm) {
                    $config = $sm->get('Config');

                    $dataMapper = new \Netric\Models\DataMapper\ApiDataMapper($config["netric"]["server"], 
                                                                                  $config["netric"]["username"],
                                                                                  $config["netric"]["password"]);
                    $dataMapper->https = $config['netric']['usehttps'];
                    return $dataMapper;
                },
                        
                'PostLoader' => function($sm) {
					// Create new postloader inject the data mapper
					$pl = new PostLoader($sm->get("PostDataMapper"));
					return $pl;
				},

                // Return a referenec to an identitymapper for loading entities
                'Searcher' => function($sm) {          
					$dm = $sm->get("DataMapper");

					$searcher = new \Netric\Search\Searcher($dm);

					
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
