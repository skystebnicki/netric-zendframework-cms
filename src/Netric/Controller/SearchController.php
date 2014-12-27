<?php
/**
 * Controller that handles basic blog functionality
 */

namespace Netric\Controller;

use Zend;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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

		$q = $this->params()->fromQuery("q");

		// Set objec types
		if (isset($config['netric']['search']['entities']))
		{
			foreach ($config['netric']['search']['entities'] as $objDef)
			{
				$objType = $objDef['obj_type'];
				$docType = $searcher->addType($objType);
				$docType->urlBase = $objDef['url_base'];
				
				if (isset($objDef['label']))
					$docType->typeLabel = $objDef['label'];

				if (isset($objDef['conditions']))
				{
					foreach ($objDef['conditions'] as $fldName=>$fldVal)
						$docType->addFilter("and", $fldName, "is_equal", $fldVal);
				}
				if (isset($objDef['fields']))
				{
					foreach ($objDef['fields'] as $fldName)
						$docType->addSearchField($fldName);
				}
			}
		}

		$view = new ViewModel(array("q"=>$q));

		if ($q)
		{
			$res = $searcher->query($q);
			if (count($res))
				$view->results = $res;
		}

		return $view;
	}
}
