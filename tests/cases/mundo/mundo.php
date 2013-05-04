<?php

include dirname(dirname(__DIR__)).'/test_data/blogpost.php';
include dirname(dirname(__DIR__)).'/test_data/page.php';
include dirname(dirname(__DIR__)).'/test_data/resource.php';
include dirname(dirname(__DIR__)).'/test_data/mapping.php';

/**
 * Tests the Mundo class
 *
 * @package Mundo
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Tests extends PHPUnit_Framework_TestCase
{

	/**
	 * Ensures the factory method assigns the variables passed and returns a
	 * Mundo instance.
	 *
	 *
	 * @covers Mundo::factory
	 * @covers Mundo_Object::__construct
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

		$object = Mundo::factory('blogpost', $values);

		$this->assertSame($object->get(), $values);
	}

	/**
	 * Ensures that an error is thrown when the second factory parameter is not
	 * an array.
	 *
	 * @expectedException Mundo_Exception
	 */
	public function test_factory_throws_error_when_assigning_non_array()
	{
		$object = Mundo::factory('blogpost', 'error');
	}

	/**
	 * Ensures that the flatten method always returns an empty array when
	 * alternate data is supplied.
	 *
	 */
	public function test_flatten_returns_empty_array_when_passed_non_array()
	{
		$this->assertEquals(Mundo::flatten('test'), array());
		$this->assertEmpty(Mundo::flatten('test'));
	}

	/**
	 * Provider for test_flatten_and_inflate_method() 
	 *
	 */
	public static function provider_flatten()
	{
		return array(
			// $field_data, $check_result, $expected_errors
			array(
				array(
					'$pushAll' => array(), // This takes care of $push
					'$pullAll' => array(), // This takes care of $pull
					'$addToSet' => array(),
					'$pop' => array(),
					'$bit' => array(),
					'$inc' => array(),
					'$set' => array(),
					'$unset' => array(),
				),
				array(
					'$pushAll' => array(), // This takes care of $push
					'$pullAll' => array(), // This takes care of $pull
					'$addToSet' => array(),
					'$pop' => array(),
					'$bit' => array(),
					'$inc' => array(),
					'$set' => array(),
					'$unset' => array(),
				),
				FALSE,
			),
			array(
				array(
					'$pushAll' => array(
						"comments" => array(
							array(
								"comment" => "Comment number 2",
								"author_name" => "Commenter Brown",
								"author_email" => "commenter.brown@example.com",
							),
							array(
								"author_name" => "Commenter Smith",
								"author_email" => "commenter.smith@example.com"
							),
						),
					), // This takes care of $push
					'$pullAll' => array(),
					'$addToSet' => array(),
					'$pop' => array(),
					'$bit' => array(),
					'$inc' => array(),
					'$set' => array(
						'post_title' => 'New post title',
					),
					'$unset' => array(),
				),
				array(
					'$pushAll.comments.0.comment' => 'Comment number 2',
					'$pushAll.comments.0.author_name' => 'Commenter Brown',
					'$pushAll.comments.0.author_email' => 'commenter.brown@example.com',
					'$pushAll.comments.1.author_name' => 'Commenter Smith',
					'$pushAll.comments.1.author_email' => 'commenter.smith@example.com',
					'$pullAll' => array(), // This takes care of $pull
					'$addToSet' => array(),
					'$pop' => array(),
					'$bit' => array(),
					'$inc' => array(),
					'$set.post_title' => 'New post title',
					'$unset' => array(),
				),
				FALSE,
			),
			array(
				array(
					'_id'           => new MongoId('4d9b16c8ef966fff00000006'),
					'post_title'    => 'Blog post inserted from ID',
					'post_slug'     => 'blog-post-from-id',
					'post_date'     => new MongoDate(strtotime("4th March  2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => 'An excerpt from a blog post added using an explicit ID',
					'post_content'  => 'Blog post content here.',
					'post_metadata' => array(
						'keywords'    => 'specific id, mongoid',
						'description' => 'Description tag here',
					)
				),
				array(
					'_id'           => new MongoId('4d9b16c8ef966fff00000006'),
					'post_title'    => 'Blog post inserted from ID',
					'post_slug'     => 'blog-post-from-id',
					'post_date'     => new MongoDate(strtotime("4th March  2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => 'An excerpt from a blog post added using an explicit ID',
					'post_content'  => 'Blog post content here.',
					'post_metadata.keywords'    => 'specific id, mongoid',
					'post_metadata.description' => 'Description tag here',
				)
			),
			array(
				array(
					array(
						'title' => 'title',
						'content' => 'content',
						'post_metadata' => array(
							'keywords'    => 'specific id, mongoid',
							'description' => 'Description tag here',
						),
					),
				),
				array(
					'0.title' => 'title',
					'0.content' => 'content',
					'0.post_metadata.keywords' => 'specific id, mongoid',
					'0.post_metadata.description' => 'Description tag here',
				)
			),
			array(
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'commenter.brown@example.com',
						),
					),
				),
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata.keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
					'post_metadata.description' => 'An example description tag for a blog post. Google SERP me plox!',
					'comments.0.comment'      => 'Comment number 1',
					'comments.0.author_name'  => 'Commenter Smith',
					'comments.0.author_url'   => 'http://example-commenter.com/',
					'comments.0.author_email' => 'commenter.smith@example.com',
					'comments.0.likes.0'      => 'Joe Bloggs',
					'comments.0.likes.1'      => 'Ted Smith',
					'comments.1.comment'      => 'Comment number 2',
					'comments.1.author_name'  => 'Commenter Brown',
					'comments.1.author_email' => 'commenter.brown@example.com',
				),
			),
		);
	}

	/**
	 * Ensures the flatten() and inflate() methods works as expected
	 *
	 * @covers Mundo_Core::flatten
	 * @covers Mundo_Core::_flatten
	 * @covers Mundo_Core::inflate
	 * @dataProvider provider_flatten
	 * @param  array  $argument  Array to pass to flatten
	 * @param  array  $expected  Expected return from flatten
	 */
	public function test_flatten_and_inflate_method($argument, $expected)
	{
		$flattened = Mundo::flatten($argument);

		$inflated = Mundo::inflate($flattened);

		$this->assertEquals($flattened, $expected);
		$this->assertEquals($inflated, $argument);
	}

	/**
	 * Provider for test_instance_of()
	 *
	 */
	public static function provider_instance_of()
	{
		return array(
			// $object, $instance_name, $expected_result
			array(
				new MongoId(),
				'MongoId',
				TRUE
			),
			array(
				new MongoDate(),
				'MongoDate',
				TRUE
			),
			array(
				new StdClass(),
				'MongoId',
				False
			),
			array(
				'hello',
				'MongoId',
				False,
			)
		);
	}

	/**
	 * Ensure the instance_of() method returns the correct results
	 *
	 * @covers Mundo_Core::instance_of
	 * @dataProvider provider_instance_of
	 * @param string $object 
	 * @param string $instance_name 
	 * @param string $expected_result 
	 * @return void
	 * @author Tony Holdstock-Brown
	 */
	public function test_instance_of($object, $instance_name, $expected_result)
	{
		$this->assertEquals(Mundo::instance_of($object, $instance_name), $expected_result);
	}
}
