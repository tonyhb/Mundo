<?php

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
	 * Bitch-ass PHPUnit doesn't like me including my test data in construct
	 * without this.
	 *
	 * @var boolean
	 */
	static $_included = FALSE;

	public function __construct()
	{
		parent::__construct();

		if ( ! $this::$_included)
		{
			// Ensure we include our MetaMongo instance without Kohana's help. Note that __DIR__ is equivalent to dirname(__FILE__) and was added in PHP 5.3.0
			include __DIR__.'/test_data/blogpost.php';

			// We've already included it.
			$this::$_included = TRUE;
		}
	}

	/**
	 * Ensures the factory method assigns the variables passed and returns a
	 * MetaMongo instance.
	 *
	 *
	 * @covers MetaMongo::factory
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
	 * Provides test data for test_set_and_get
	 *
	 * @return array
	 */
	public function provider_set_and_get()
	{
		// $data, $expected_error (null if it should succeed)
		return array(
			// A full dataset
			array(
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post.html',
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
			// Data with an undefined field which should fail.
			array(
				array(
					'post_title' => 'Example blog post',
					'post_slug'  => 'example-blog-post.html',
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
					'post_slug'  => 'example-blog-post.html',
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
				"Field 'embedded' does not exist", 
			),
		);
	}


	/**
	 * Tests that the set and get functions work as expected
	 *
	 *
	 * @test
	 * @covers MetaMongo::set
	 * @covers MetaMongo::get
	 * @dataProvider provider_set_and_get
	 * @param array   $data   The array of data to set
	 * @param mixed   $expected_error   Null if setting should pass, otherwise the error exception message.
	 *
	 */
	public function test_set_and_get($data, $expected_error)
	{
		$metamongo = new MetaMongo_Object;

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

			// Ensure it sets fine.
			$this->assertSame($metamongo->get(), $data);
		}
	}

}