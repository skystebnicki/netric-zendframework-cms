<?php

namespace Netric\Models\Post;

class IdentityMap extends IdentityMapAbstract
{
	public function __construct(DataMapperInterface $dm)
	{
		$this->_dataMapper = $dm;
		return $this;
	}

	/**
	 * Determine if a post is already cached in memory
	 *
	 * @param string $id
	 * @return bool
	 */
	private function isPostCached($id)
	{
	}


	/**
	 * Get the post by id from the datamapper
	 *
	 * @param string $id The unique id of the post
	 * @return Post
	 */
	private function fetchPostById($id)
	{
	}
}
