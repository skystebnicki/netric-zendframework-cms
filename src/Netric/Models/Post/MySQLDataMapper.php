<?php

namespace Netric\Models\Post;

class MySQLDataMapper extends DomainModelAbstract implements DataMapperInterface
{
	/**
	 * @param string $id The id of the pOst
	 * @return Post
	 */
	public function fetchPostById($id);

	/**
	 * @param string $id The id of the category
	 * @return Post[]
	 */
	public function fetchPostByCategoryId($id);
}
