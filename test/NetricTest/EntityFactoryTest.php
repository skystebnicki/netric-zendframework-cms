<?php

namespace NetricTest\Models;

use PHPUnit_Framework_TestCase;
use Netric\Models\EntityFactory;

class EntityFactoryTest extends PHPUnit_Framework_TestCase
{
	public function testFactory()
	{
		$entity = EntityFactory::factory("customer");
        $this->assertInstanceOf("Netric\Models\Entity\Customer", $entity);
	}
}
