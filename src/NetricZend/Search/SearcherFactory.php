<?php
namespace NetricZend\Search;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearcherFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $netricApi = $serviceLocator->get("NetricApi");
        $searcher = new Searcher($netricApi);
        return $searcher;
    }
}
