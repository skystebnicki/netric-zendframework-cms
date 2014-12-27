<?php

namespace NetricTest\Models\Post;

use PHPUnit_Framework_TestCase;
use Netric\Models\Post\Post;

class PostTest extends PHPUnit_Framework_TestCase
{
	public function testObjectPropertiesSet()
	{
		// Setting up testing values
		$id = 1;
		$title = 'tested title';

		// Setting up sample post
		$post = new Post();
		$post->setId($id);
		$post->setTitle($title);

		// Get the protected and private values
		$refPost = new \ReflectionObject($post);
		$idProp = $refPost->getProperty('_id');
		$idProp->setAccessible(true);
		$titleProp = $refPost->getProperty('_title');
		$titleProp->setAccessible(true);

		// Test values
		$this->assertEquals($id, $idProp->getValue($post), "ID not set");
		$this->assertEquals($title, $titleProp->getValue($post), "Title not set");
	}
}
