<?php
namespace NetricZend\Navigation;
 
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
 
class CmsNavigationFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $navigation =  new CmsNavigation();
        return $navigation->createService($serviceLocator);
    }
}
