<?php
namespace NetricZend\Controller;

use Zend;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Handle searching for site content
 */
class SearchController extends AbstractActionController
{
	/**
	 * Show article index
	 * 
	 * @return \Zend\View\Model\ViewModel
	 */
	public function indexAction()
	{
		$config = $this->getServiceLocator()->get('Config');
		$searcher = $this->getServiceLocator()->get('Searcher');

		$queryString = $this->params()->fromQuery("q");

		// Set object types
		if (isset($config['netric']['search']['entities'])) {
			foreach ($config['netric']['search']['entities'] as $objDef) {
				$objType = $objDef['obj_type'];
				$docType = $searcher->addType($objType);
				$docType->urlBase = $objDef['url_base'];

				if (isset($objDef['label']))
					$docType->typeLabel = $objDef['label'];

				if (isset($objDef['conditions'])) {
					foreach ($objDef['conditions'] as $fldName => $fldVal)
						$docType->addFilter("and", $fldName, "is_equal", $fldVal);
				}
				if (isset($objDef['fields'])) {
					foreach ($objDef['fields'] as $fldName)
						$docType->addSearchField($fldName);
				}
			}
		}

		$view = new ViewModel(array("q" => $queryString));

		if ($queryString) {
			$res = $searcher->query($queryString);
			if (count($res))
				$view->results = $res;
		}

		return $view;
	}
}
