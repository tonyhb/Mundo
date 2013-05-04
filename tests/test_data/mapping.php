<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Example Mundo object for field mapping
 *
 * @package Mundo
 * @category Tests
 */
class Model_Mapping extends Mundo_Object
{
	protected $_collection = 'mapping';

	protected $_fields = array(
		'id' => '_id',
		'post_title' => 't',
		'post_slug' => 's',
		'author_id' => 'ai',
		'author_name' => 'an',
		'author_email' => 'ae',
		'post_metadata.keywords' => 'pm.k',
		'post_metadata.description' => 'pm.d',
		'comments.$.comment' => 'c.$.c',
		'comments.$.author_name' => 'c.$.an',
		'comments.$.author_url' => 'c.$.au',
		'comments.$.author_email' => 'c.$.ae',
		'comments.$.likes.$' => 'c.$.l.$',
		'comments.$.like_count' => 'c.$.lc',
	);
}
