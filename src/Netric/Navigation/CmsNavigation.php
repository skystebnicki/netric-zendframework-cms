<?php
namespace Netric\Navigation;
 
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Navigation\Service\DefaultNavigationFactory;
use NetricSDK\EntityCollection\EntityCollection;

 
class CmsNavigation extends DefaultNavigationFactory
{
    protected function getPages(ServiceLocatorInterface $serviceLocator)
    {
        if (null === $this->pages) {
            $configuration['navigation'][$this->getName()] = array();
			$config = $serviceLocator->get('Config');

            // Get pages
            // ----------------------------------------------------
			$pages = $this->getCmsPages($serviceLocator);
			if (count($pages))
            	$configuration['navigation'][$this->getName()] = $pages;

			/*
            $entLoader = $serviceLocator->get('EntityLoader');
            $pageCollection = $entLoader->createCollection("cms_page");
            $pageCollection->where("site_id")->equals($config['netric']['site_id']);
            $pageCollection->where("f_navmain")->equals(true);
            // TODO: status
            $pageCollection->orderBy("sort_order", "ASC");
            //$postCollection->setLimit(3);
            $num = $pageCollection->load();
            for ($i = 0; $i < $num; $i++)
            {
                $page = $pageCollection->getEntity($i);
                $template = ($page->getValue("template_id")) ? $entLoader->get("cms_page_template", $page->getValue("template_id")) : null;
                
                // Add page
                if ($template)
                {
                    $configuration['navigation'][$this->getName()][$page->getValue("uname")] = array(
                        'label' => $page->getValue("name"),
                        'route' => $template->getValue("module"),
                        'id' => $page->getValue("uname"),
                    );
                }
                else
                {
                    $configuration['navigation'][$this->getName()][$page->getValue("uname")] = array(
                        'label' => $page->getValue("name"),
                        'uri' => '/' . $page->getValue("uname"),
                        'id' => $page->getValue("uname"),
                    );
                }
            }
			 */
            
            /*
			// Add Home
			$configuration['navigation'][$this->getName()]["home"] = array(
				'label' => 'Home',
				'route' => 'apphome',
				//'uri' => '/',
			);

			// Add Blog
			$configuration['navigation'][$this->getName()]["blog"] = array(
				'label' => 'Blog',
				'route' => 'blog',
                'id' => 'blog',
			);

			// About
			$configuration['navigation'][$this->getName()]["about"] = array(
				'label' => 'About',
				'uri' => '/about',
                'id' => '/about',
			);

			// Work With Sky
			$configuration['navigation'][$this->getName()]["work"] = array(
				'label' => 'Work With Sky',
				'uri' => '/workwithsky',
                'id' => '/workwithsky',
			);

			// Contact
			$configuration['navigation'][$this->getName()]["contact"] = array(
				'label' => 'Contact',
				'route' => 'contact',
			);
             */
             
             
            if (!isset($configuration['navigation'])) {
                throw new Exception\InvalidArgumentException('Could not find navigation configuration key');
            }
            if (!isset($configuration['navigation'][$this->getName()])) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Failed to find a navigation container by the name "%s"',
                    $this->getName()
                ));
            }
 
            $application = $serviceLocator->get('Application');
            $routeMatch  = $application->getMvcEvent()->getRouteMatch();
            $router      = $application->getMvcEvent()->getRouter();
            $pages       = $this->getPagesFromConfig($configuration['navigation'][$this->getName()]);
 
            $this->pages = $this->injectComponents($pages, $routeMatch, $router);
        }
        return $this->pages;
    }

	/**
	 * Recurrsively get pages
	 *
	 * @param ServiceLocatorInterface $serviceLocator Application service locator
	 * @param int parentId Optional parent id for recurrsively building config
	 * @return associative array of pages in config format
	 */
	private function getCmsPages(ServiceLocatorInterface $serviceLocator, $uriPre="", $parentId=null)
	{
		$config = $serviceLocator->get('Config');
		$pages = array();

		/*
		// Get pages
		// ----------------------------------------------------
		$entLoader = $serviceLocator->get('EntityLoader');
		$pageCollection = $entLoader->createCollection("cms_page");
		$pageCollection->where("site_id")->equals($config['netric']['site_id']);
		if ($parentId)
		{
			$pageCollection->where("parent_id")->equals($parentId);
		}
		else
		{
			$pageCollection->where("f_navmain")->equals(true);
		}

		// TODO: status
		$pageCollection->orderBy("sort_order", "ASC");
		$num = $pageCollection->load();
		for ($i = 0; $i < $num; $i++)
		{
			$page = $pageCollection->getEntity($i);
			$template = ($page->getValue("template_id")) ? $entLoader->get("cms_page_template", $page->getValue("template_id")) : null;
			
			// Add page
			if ($template)
			{
				$pages[$page->getValue("uname")] = array(
					'label' => $page->getValue("name"),
					'route' => $template->getValue("module"),
					'id' => $page->getValue("id"),
				);
			}
			else
			{
				$pages[$page->getValue("uname")] = array(
					'label' => $page->getValue("name"),
					'uri' => $uriPre . '/' . $page->getValue("uname"),
					'id' => $page->getValue("id"),
				);
			}

			// Check for children
			$children = $this->getCmsPages($serviceLocator, $uriPre . '/' . $page->getValue("uname"), $page->getValue("id"));
			if (count($children))
				$pages[$page->getValue("uname")]['pages'] = $children;
		}

		return $pages;
		*/

        // Get pages
        // ----------------------------------------------------
        $netricApi = $serviceLocator->get('NetricApi');
        $pageCollection = $netricApi->createEntityCollection("cms_page");
        $pageCollection->where("site_id")->equals($config['netric']['site_id']);
        if ($parentId)
        {
            $pageCollection->andWhere("parent_id")->equals($parentId);
        }
        else
        {
            $pageCollection->andWhere("f_navmain")->equals(true);
        }

        // TODO: status
        $pageCollection->orderBy("sort_order", "ASC");
        $num = $pageCollection->load();
        for ($i = 0; $i < $num; $i++)
        {
            $page = $pageCollection->getEntity($i);
            $template = ($page->getValue("template_id")) ?
                $netricApi->getEntity("cms_page_template", $page->getValue("template_id")) : null;

            // Add page
            if ($template)
            {
                $pages[$page->getValue("uname")] = array(
                    'label' => $page->getValue("name"),
                    'route' => $template->getValue("module"),
                    'id' => $page->getValue("id"),
                );
            }
            else
            {
                $pages[$page->getValue("uname")] = array(
                    'label' => $page->getValue("name"),
                    'uri' => $uriPre . '/' . $page->getValue("uname"),
                    'id' => $page->getValue("id"),
                );
            }

            // Check for children
            $children = $this->getCmsPages($serviceLocator, $uriPre . '/' . $page->getValue("uname"), $page->getValue("id"));
            if (count($children))
                $pages[$page->getValue("uname")]['pages'] = $children;
        }

        return $pages;

	}
}
