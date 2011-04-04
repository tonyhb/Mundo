<?php

/**
 * Tests the MetaMongo_Object class, which
 *
 * @package MetaMongo
 * @subpackage MetaMongo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class MetaMongo_Object_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Provides test data for test_set_and_get
	 *
	 */
	public static function set_and_get()
	{
		// $data, $expected_error (null if it should succeed)
		return array(
			// A full dataset
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
				// This should work correctly
				NULL,
			),
			// Only the "likes" array of one comment.
			array(
				array(
				
					'comments' => array(
						array(
							'likes' => array('Joe Bloggs', 'Ted Smith'),
						),
					),
				),
				// This should work correctly
				NULL,
			),
			// Two fields being set using dot path notation
			array(
				array(
					'post_metadata.keywords' => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
					'post_metadata.description' => 'An example description tag for a blog post. Google SERP me plox!',
				),
				// This should work correctly
				NULL,
				array(
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
				)
			),
			// Dot notation on an embedded object
			array(
				array(
					'comments.0.comment'      => 'Comment number 1',
					'comments.0.author_name'  => 'Commenter Smith',
					'comments.0.author_url'   => 'http://example-commenter.com/',
					'comments.0.author_email' => 'commenter.smith@example.com',
					'comments.0.likes'        => array('Joe Bloggs', 'Ted Smith'),
					'comments.1.comment'      => 'Comment number 2',
					'comments.1.author_name'  => 'Commenter Brown',
					'comments.1.author_email' => 'commenter.brown@example.com',
					'comments.1.likes.0'      => 'First like',
					'comments.1.likes.1'      => 'Second like',
				),
				NULL,
				array(
					'comments' => array(
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
							'likes'        => array('First like', 'Second like'),
						),
					),
				)
			),
			// Data with an undefined field which should fail.
			array(
				array(
					'post_title' => 'Example blog post',
					'post_slug'  => 'example-blog-post',
					'post_date'  => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'     => new MongoId('4d965966ef966f0916000000'),
					'undefined'  => 'Undefined field which should cause setting to fail.',
				),
				// MetaMongo should throw an error saying our field doesn't exist
				"Field 'undefined' does not exist", 
			),
			// Data with an undefined field in an embedded object
			array(
				array(
					'post_title' => 'Example blog post',
					'post_slug'  => 'example-blog-post',
					'post_date'  => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'     => new MongoId('4d965966ef966f0916000000'),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
							'embedded'    => 'An undefined field in an embedded object',
						),
					),
				),
				// MetaMongo should throw an error saying our field doesn't exist
				"Field 'comments.0.embedded' does not exist", 
			),
		);
	}

	/**
	 * Tests that the set and get functions work as expected
	 *
	 *
	 * @test
	 * @covers MetaMongo_Object::set
	 * @covers MetaMongo_Object::get
	 * @dataProvider set_and_get
	 * @param  array  $data             The array of data to set
	 * @param  mixed  $expected_error   Null if setting should pass, otherwise the error exception message.
	 * @param  array  $expected_result  The expected result of get() if it isn't the same as $data.
	 *
	 */
	public function test_set_and_get($data, $expected_error, $expected_result = NULL)
	{

		$metamongo = new Model_Blogpost;

		if ($expected_error)
		{
			try
			{
				// Setting our data should fail
				$metamongo->set($data);
			}
			catch (Exception $e)
			{
				// Ensure our error message is correct and it failed for the right reasons.
				$this->assertEquals($e->getMessage(), $expected_error);
			}
		}
		else
		{
			// Set our data
			$metamongo->set($data);

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($metamongo->get(), $expected_result);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($metamongo->get(), $data);
			}
		}
	}

	/**
	 * Provider for test_single_set_and_get
	 *
	 * @return array
	 */
	public static function provider_single_set_and_get()
	{
		return array(
			// Set a value in a root-level field
			array(
				'post_title',
				'Post title goes here',
				NULL
			),
			// Use dot-notation to set an array value
			array(
				'post_metadata.keywords',
				'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
				NULL,
				array(
					'post_metadata' => array(
						'keywords' => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
					)
				)
			)
		);
	}

	/**
	 * Test setting and getting single fields at once works as expected
	 *
	 * @test
	 * @covers MetaMongo_Object::get
	 * @covers MetaMongo_Object::set
	 * @dataProvider provider_single_set_and_get
	 * @param  string  $field            Name of field we are setting
	 * @param  string  $value            Value of the field
	 * @param  string  $expected_error   Expected error message, if any
	 * @param  string  $expected_result  Expected result, if different from array($field => $value)
	 */
	public function test_single_set_and_get($field, $value, $expected_error, $expected_result = NULL)
	{
		$metamongo = new Model_Blogpost;

		if ($expected_error)
		{
			try
			{
				// Setting our data should fail
				$metamongo->set($field, $value);
			}
			catch (Exception $e)
			{
				// Ensure our error message is correct and it failed for the right reasons.
				$this->assertEquals($e->getMessage(), $expected_error);
			}
		}
		else
		{
			// Set our data
			$metamongo->set($field, $value);

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($metamongo->get($field), $value);
				$this->assertSame($metamongo->get(), $expected_result);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($metamongo->get($field), $value);
				$this->assertSame($metamongo->get(), array($field => $value));
			}
		}
	}

}