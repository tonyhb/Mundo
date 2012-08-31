<?php

/**
 * An abstract method which defines class properties and providers used in 
 * multiple test classes.
 *
 * @package Mundo
 * @category Tests
 * @author Tony Holdstock-Brown
 */
abstract class Mundo_Tests_Abstract extends PHPUnit_Framework_TestCase {

	/**
	 * Remove any test databases.
	 *
	 * @todo This should be prevented by good use of mocking.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		// Remove our testing database before writing tests.
		$mongo = new Mongo;

		$config = Kohana::$config->load("mundo");

		// Select our database
		$db = $mongo->{$config->database};

		// Drop it like it's hot.
		$db->drop();
	}

	/**
	 * Remove our test database we played with.
	 *
	 * @todo This should be prevented by good use of mocking.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
		// Repeat our drop function
		self::setUpBeforeClass();
	}

	/**
	 * Provides data for test_validate_set_data, test_validate_array_data
	 * test_create_document
	 *
	 * @return array
	 */
	public static function provider_validate_and_create_data()
	{
		return array(
			// $field_data, $check_result, $expected_errors
			array(
				// Test explicitly setting an ObjectId
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
				TRUE,
				NULL
			),
			array(
				// Complete 
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
				TRUE,
				NULL
			),
			array(
				// Without required object (post_metadata)
				array(
					'post_title'    => 'Blog post inserted from ID',
					'post_slug'     => 'blog-post-from-id',
					'post_date'     => new MongoDate(strtotime("4th March  2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => 'An excerpt from a blog post added using an explicit ID',
					'post_content'  => 'Blog post content here.',
				),
				FALSE,
				array(
					'post_metadata.keywords' => 'post metadata keywords must not be empty',
					'post_metadata.description' => 'post metadata description must not be empty',
				)
			),
			array(
				// Incorrect post slug (first dimension error)
				array(
					'post_title'    => 'Another example blog post',
					'post_slug'     => 'example-blog-post !',
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
				FALSE,
				array(
					'post_slug' => 'post slug must contain only numbers, letters and dashes',
				),
			),
			array(
				// Invalid embedded object email
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
							'author_email' => 'incorrect.email@example',
						),
					),
				),
				FALSE,
				array(
					"comments.1.author_email" => "comments author email must be a email address",
				)
			),
			array(
				// Missing required field in an embedded object
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
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'incorrect.email@example.com',
						),
					),
				),
				FALSE,
				array(
					"comments.0.author_name" => "comments author name must not be empty"
				)
			),
		);
	}

	/**
	 * Provider for the save and update methods
	 */
	public function provider_update()
	{
		return array(
			array(
				// Complete data to load, copied from provider_validate_and_create_data
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
				// What we're changing data to
				array(
					'post_title'    => 'New post title',
					'post_date'     => new MongoDate(strtotime("26th March 2011, 11:24AM")),
					'post_metadata' => array(
						'keywords' => 'new keywords, updated object',
					),
					// mimic __unset
					'post_excerpt'  => NULL,
					// Remove one embedded object
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Dr. Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'my.new.email@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
					),
				),
				// Expected query
				array(
					'$set' => array(
						'post_title' => 'New post title',
						'post_date' => new MongoDate(strtotime("26th March 2011, 11:24AM")),
						'post_metadata.keywords' => 'new keywords, updated object',
						'comment.1.author_name' => 'Dr. Smith',
						'comment.1.author_email' => 'my.new.email@example.com',
						'comment.1.likes' => array('Joe Bloggs', 'Ted Smith')
					),
					'$unset' => array('post_excerpt'),
				)
			),
		);
	}

	/**
	 * Provides test data for test_set_and_get
	 *
	 */
	public static function provider_set_and_get()
	{
		// $data, $expected_error (null if it should succeed), $expected_result (with dot notation)
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
				// Mundo should throw an error saying our field doesn't exist
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
				// Mundo should throw an error saying our field doesn't exist
				"Field 'comments.0.embedded' does not exist", 
			),
			// Dot notation within normal keys
			array(
				array(
					'post_title' => 'Example blog post',
					'post_metadata.keywords' => 'keyword, another',
					'post_metadata.description' => 'This is a post description keyword tag',
					'author_name' => 'Author name',
				),
				NULL,
				array(
					'post_title' => 'Example blog post',
					'post_metadata' => array(
						'keywords' => 'keyword, another',
						'description' => 'This is a post description keyword tag'
					),
					'author_name' => 'Author name'
				)
			),
		);
	}

}
