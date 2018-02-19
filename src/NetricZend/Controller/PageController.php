<?php
namespace NetricZend\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Get and render CMS Page(s) based on route
 */
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
		$config = $this->getServiceLocator()->get('Config');

		// Get the page from the datastore
		if ($view->page) {
			$netricApi = $this->getServiceLocator()->get('NetricApi');
			$page = $netricApi->getEntityByUniqueName("cms_page", $view->page, ['site_id' => $config['netric']['site_id']]);

			if (!$page) {
				$this->getResponse()->setHttpResponseCode(404);
				return;
			}

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

			if ($num) {
					// First entry is this page
				$subnavPages[] = $page;

					// Add all children
				for ($i = 0; $i < $num; $i++)
					$subnavPages[] = $pageCollection->getEntity($i);
			} else if ($page->getValue("parent_id")) {
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

		// Set navigation to active root element
		$root = $view->page;

		// Check if this is hierarchical
		if (strpos($view->page, '/') !== false) {
			$names = explode("/", $view->page);
			// might have to skip over '/'
			$root = ($names[0]) ? $names[0] : $names[1];
		}

		$nav = $this->getServiceLocator()->get('CmsNavigation');
		$navPage = $nav->findOneById($page->id);
		if ($navPage) {
			$navPage->setActive();
			$view->navPage = $navPage;
		}

		return $view;
	}
}
