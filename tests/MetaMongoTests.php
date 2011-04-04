<?php

include __DIR__.'/test_data/blogpost.php';

/**
 * Tests the MetaMongo class
 *
 * @package MetaMongo
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class MetaMongoTests extends PHPUnit_Framework_TestCase
{

	/**
	 * Ensures the factory method assigns the variables passed and returns a
	 * MetaMongo instance.
	 *
	 *
	 * @covers MetaMongo::factory
	 * @covers MetaMongo_Object::__construct
	 */
	public function test_factory_returns_instance_with_values()
	{
		$values = array(
			'post_title' => 'Example blog post',
			'post_slug'  => 'example-blog-post.html',
			'post_date'  => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
			// Skip a few
			'comments'   => array(
				array(
					'comment'     => 'This is a comment',
					'author_name' => 'Example User',
					'author_url'  => 'http://www.example.to/',
				)
			)
		);

		$object = MetaMongo::factory('blogpost', $values);

		$this->assertSame($object->get(), $values);
	}

	/**
	 * Ensures that an error is thrown when the second factory parameter is not
	 * an array.
	 *
	 * @expectedException MetaMongo_Exception
	 */
	public function test_factory_throws_error_when_assigning_non_array()
	{
		$object = MetaMongo::factory('blogpost', 'error');
	}

}