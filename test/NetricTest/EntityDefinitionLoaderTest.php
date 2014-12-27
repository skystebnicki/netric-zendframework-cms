<?php

namespace NetricTest\Models;

use PHPUnit_Framework_TestCase;
use Netric\Models\EntityDefinitionLoader;

class EntityDefinitionLoaderTest extends PHPUnit_Framework_TestCase
{
	public function testGet()
	{
        	$dm = new \Netric\Models\DataMapper\TestDataMapper();
                $im = new EntityDefinitionLoader($dm);
                
                // Get the mock object: 1 is in the test mapper
                $def = $im->get("utestobj");
                $field = $def->getField("name");
                $this->assertEquals("text", $field["type"]);
                
                // Check if the object is in memory now
        	$refIm = new \ReflectionObject($im);
                $mthIsLoaded = $refIm->getMethod("isLoaded");
        	$mthIsLoaded->setAccessible(true);
                $this->assertTrue($mthIsLoaded->invoke($im, "utestobj"));
	}
}