<?php

/**
 * Ensures the delete method works correctly
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Delete_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Remove any test databases.
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
	 * Remove our test database we played witj
	 *
	 * @test
	 * @covers Mundo_Object::delete
	 *
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
		// Remove our testing database before writing tests.
		$mongo = new Mongo;

		$config = Kohana::$config->load("mundo");

		// Select our database
		$db = $mongo->{$config->database};

		// Drop it like it's hot.
		$db->drop();
	}

	public function test_model_delete_from_complete_model_data()
	{

		$model = new Model_Blogpost;

		$model->set(array(
			'_id'           => new MongoId('000000000000000000000001'),
			'post_title'    => 'test_model_delete_from_complete_model_data',
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
					'likes'        => array('Joe', 'James'),
					'like_count'   => 2,
				),
				array(
					'comment'      => 'Comment number 2',
					'author_name'  => 'Commenter Brown',
					'author_email' => 
					'commenter.brown@example.com',
				),
			),
		))->create();


		$this->assertTrue($model->loaded());

		$model->delete();

		$loading = new Model_Blogpost;

		$loading->set( '_id', new MongoId('000000000000000000000001'));

		$loading->load();

		$this->assertFalse($loading->loaded());
	}

	/**
	 * Ensures the delete method favours data supplied as as an argument
	 * over data from the get method.
	 *
	 * @test
	 * @covers Mundo_Object::delete
	 *
	 * @return void
	 */
	public function test_delete_from_supplied_arguments()
	{
		$model = new Model_Blogpost;

		$model->set(array(
			'_id'           => new MongoId('000000000000000000000001'),
			'post_title'    => 'test_delete_from_supplied_arguments',
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
					'likes'        => array('Joe', 'James'),
					'like_count'   => 2,
				),
				array(
					'comment'      => 'Comment number 2',
					'author_name'  => 'Commenter Brown',
					'author_email' => 
					'commenter.brown@example.com',
				),
			),
		))->create();

		$this->assertTrue($model->loaded()); 

		// Delete the document from a new, unloaded model using an argument
		$delete = new Model_Blogpost;

		$delete->delete(array(
			'_id'           => new MongoId('000000000000000000000001'),
		));

		// Attempt loading the just deleted document
		$loading = new Model_Blogpost;

		$loading->set( '_id', new MongoId('000000000000000000000001'));

		$loading->load();

		// This shouldn't have worked
		$this->assertFalse($loading->loaded());
	}


}
