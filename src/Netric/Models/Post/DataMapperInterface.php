<?php

namespace Netric\Models\Post;

interface DataMapperInterface
{
	/**
	 * @param string $id The id of the pOst
	 * @return Post
	 */
	public function fetchPostById($id);
}
