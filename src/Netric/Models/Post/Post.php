<?php

namespace Netric\Models\Post;

use Netric\Models;

class Post extends Models\DomainEntityAbstract 
{
	/**
	 * @var string $title
	 */
	private $_title = "";

	/**
	 * Set the title of this post
	 *
	 * @var string $title The name of the post
	 * @return Post
	 */
	public function setTitle($title)
	{
		$this->_title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Set the id of this post
	 *
	 * @var string $id The id of the post
	 * @return Post
	 */
	public function setId($id)
	{
		$this->_id = $id;
		return $this;
	}
}
