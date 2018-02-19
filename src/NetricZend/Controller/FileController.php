<?php
namespace NetricZend\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Authenticate netric file access and proxy to the user
 */
class FileController extends AbstractActionController
{
    /**
     * Post view action
     */
    public function indexAction()
    {
        $this->getResponse()->setHttpResponseCode(404);
        return $this->getResponse();
    }

    /**
     * Download a netic file and stream it to the browser
     *
     * @return void
     */
    public function downloadAction()
    {
        $fileId = $this->getEvent()->getRouteMatch()->getParam("file_id");
        $netricApi = $this->getServiceLocator()->get('NetricApi');

        if (!$fileId) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        }

        // Get file
        $fileEntity = $netricApi->getEntity("file", $fileId);
        if (!$fileEntity) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        }


    }
}
