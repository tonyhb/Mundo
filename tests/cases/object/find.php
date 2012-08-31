<?php

/**
 * Tests the Mundo_Object class, which
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Find_Tests extends PHPUnit_Framework_TestCase {

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
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
		// Repeat our drop function
		self::setUpBeforeClass();
	}

	public function test_find_cursor_works_as_expected()
	{
		$blogpost1 = array(
			'_id'           => new MongoId('000000000000000000000001'),
			'post_title'    => 'test_model_delete_from_complete_model_data',
			'post_slug'     => 'Blogpost-1',
			'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
			'author'        => new MongoId('4d965966ef966f0916000000'),
			'author_name'   => 'Author Jones',
			'author_email'  => 'author@example.com',
			'post_excerpt'  => '...blogpost 1...',
			'post_content'  => 'This is blogpost number 1',
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
				),
				array(
					'comment'      => 'Comment number 2',
					'author_name'  => 'Commenter Brown',
					'author_email' => 
					'commenter.brown@example.com',
				),
			),
		);
		$model = Mundo::factory('blogpost');
		$model->set($blogpost1)->create();


		$cursor = Mundo::factory('blogpost')->set('author_name', 'Ridiculous')->find();
		$this->assertEquals(0, $cursor->count());


		$cursor = Mundo::factory('blogpost')->find();
		$this->assertEquals(1, $cursor->count());


		$cursor = Mundo::factory('blogpost')->set('author_name', 'Author Jones')->find();
		$this->assertEquals(1, $cursor->count());


		$this->assertEquals($blogpost1, $cursor->getNext());


		$blogpost2 = array(
			'_id'           => new MongoId('000000000000000000000002'),
			'post_title'    => 'blog 2',
			'post_slug'     => 'Blogpost-2',
			'post_date'     => new MongoDate(strtotime("23rd March 2011, 12:37AM")),
			'author'        => new MongoId('4d965966ef966f0916000000'),
			'author_name'   => 'Author Jones',
			'author_email'  => 'author@example.com',
			'post_excerpt'  => '...blogpost 2...',
			'post_content'  => 'This is blogpost number 2',
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
				),
				array(
					'comment'      => 'Comment number 2',
					'author_name'  => 'Commenter Brown',
					'author_email' => 
					'commenter.brown@example.com',
				),
			),
		);
		$model = new Model_Blogpost;
		$model->set($blogpost2)->create();


		$cursor = Mundo::Factory('blogpost')->set('author_name', 'Author Jones')->find();
		$this->assertEquals(2, $cursor->count());
	}
}
