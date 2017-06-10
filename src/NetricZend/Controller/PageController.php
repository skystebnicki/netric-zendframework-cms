<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace NetricZend\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PageController extends AbstractActionController
{
	/**
	 * Post view action
	 */
    public function indexAction()
    {
		$view = new ViewModel();
        $view->setTemplate("cms/page");

		$view->page = $this->getEvent()->getRouteMatch()->getParam("page");

		// Get the page from the datastore
		if ($view->page)
		{
        	$netricApi = $this->getServiceLocator()->get('NetricApi');
			$page = $netricApi->getEntityByUniqueName("cms_page", $view->page);

			if ($page)
			{
				$renderer = $this->getServiceLocator()->get('Zend\View\Renderer\PhpRenderer');
				$renderer->headTitle($page->getValue("title"));
				$view->body = $page->getValue("data");
				$view->id = $page->getValue("id");
				$view->name = $page->getValue("name");

				/**
				 * Get child pages
				 */
				$subnavPages = array();
				$netricApi = $this->getServiceLocator()->get('NetricApi');
				$pageCollection = $netricApi->createEntityCollection("cms_page");
				$pageCollection->where("parent_id")->equals($page->getValue("id"));
				//$pageCollection->where("f_navmain")->equals(true);
				// TODO: status
				$pageCollection->orderBy("sort_order", "ASC");
				$num = $pageCollection->load();

				if ($num)
				{
					// First entry is this page
					$subnavPages[] = $page;

					// Add all children
					for ($i = 0; $i < $num; $i++)
						$subnavPages[] = $pageCollection->getEntity($i);
				}
				else if ($page->getValue("parent_id"))
				{
					// If there are no child pages to this page, look to the parent page for children at the same level
					$pageCollection = $netricApi->createEntityCollection("cms_page");
					$pageCollection->where("parent_id")->equals($page->getValue("parent_id"));
					$pageCollection->orderBy("sort_order", "ASC");
					$num = $pageCollection->load();

					// First entry is the parent page
					$subnavPages[] = $netricApi->getEntity("cms_page", $page->getValue("parent_id"));;

					// Add all children
					for ($i = 0; $i < $num; $i++)
						$subnavPages[] = $pageCollection->getEntity($i);
				}

				$view->subnavPages = $subnavPages;
			}
			else
			{
				// TODO: display 404 page
			}
		}

        // Set navigation to active root element
		if (strpos($view->page, '/')===false)
		{
			$root = $view->page;
		}
		else
		{
			$names = explode("/", $view->page);
			$root = ($names[0]) ? $names[0] : $names[1]; // might have to skip over '/'
		}

        $nav = $this->getServiceLocator()->get('CmsNavigation');
        $navPage = $nav->findOneById($page->getId());
		if ($navPage)
		{
        	$navPage->setActive();
			$view->navPage = $navPage;
		}
 
        return $view;
    }
}
