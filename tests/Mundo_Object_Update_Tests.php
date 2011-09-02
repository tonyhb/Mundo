<?php

/**
 * Tests the update method within Mundo to ensure the atomic operations work
 * as expected.
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Update_Tests extends PHPUnit_Framework_TestCase {

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

		self::$model_data = array(
			'_id'           => new MongoId('000000000000000000000001'),
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
		);

		self::$model = new Model_Blogpost;
		self::$model->set(self::$model_data)->create();
	}

	/**
	 * Remove our test database we played witj
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

	public static $model;

	public static $model_data;

	public function test_push_pop_and_set_in_series()
	{
		$comment_1 = array(
			'comment'      => 'Comment 3',
			'author_name'  => 'Joe Bloggs',
			'author_email' => 'joe@example.net',
			'likes'        => array(
				'Joe'
			),
			'like_count' => 1
		);

		$comment_2 = array(
			'comment'      => 'Comment 4',
			'author_name'  => 'Jane Doe',
			'author_email' => 'jane@example.net',
		);

		self::$model->push('comments', $comment_1, $comment_2);


		// Check the pushes were OK

		$this->assertEquals(
			array(
				'$pushAll' => array(
					'comments' => array(
						array(
							'comment'      => 'Comment 3',
							'author_name'  => 'Joe Bloggs',
							'author_email' => 'joe@example.net',
							'likes'        => array(
								'Joe'
							),
							'like_count' => 1
						),
						array(
							'comment'      => 'Comment 4',
							'author_name'  => 'Jane Doe',
							'author_email' => 'jane@example.net',
						)
					),
				),
			),
			self::$model->next_update()
		);

		/**
		 * !! NOTE !!
		 *
		 * This isn't working at the moment. You can't update an
		 * embedded collection in $pushAll using $set.
		 *
		 * Mongo will return "have conflicting mods in update"
		 *
		 */

		/*
		 
		// Change Comment 4's email address (for some reason) before saving
		self::$model->set('comments.3.comment', 'Jane Does Comment');

		// Check the model updated the embedded collection in $pushAll
		$this->assertEquals(
			array(
				'$pushAll' => array(
					'comments' => array(
						array(
							'comment'      => 'Comment 3',
							'author_name'  => 'Joe Bloggs',
							'author_email' => 'joe@example.net',
							'likes'        => array(
								'Joe'
							),
							'like_count' => 1
						),
						array(
							'comment'      => 'Jane Does Comment',
							'author_name'  => 'Jane Doe',
							'author_email' => 'jane@example.net',
						)
					),
				),
			),
			self::$model->next_update()
		);

		*/


		// Pop one of the comments

		$popped_comment_2 = self::$model->pop('comments');

		$this->assertEquals(
			array(
				'$pushAll' => array(
					'comments' => array(
						array(
							'comment'      => 'Comment 3',
							'author_name'  => 'Joe Bloggs',
							'author_email' => 'joe@example.net',
							'likes'        => array(
								'Joe'
							),
							'like_count' => 1
						),
					),
				),
			),
			self::$model->next_update()
		);
		$this->assertEquals(
			$comment_2,
			$popped_comment_2
		);


		// $set after a $pushAll and $popAll

		self::$model->post_title = 'This is an example blog post';

		$this->assertEquals(
			array(
				'$pushAll' => array(
					'comments' => array(
						array(
							'comment'      => 'Comment 3',
							'author_name'  => 'Joe Bloggs',
							'author_email' => 'joe@example.net',
							'likes'        => array(
								'Joe'
							),
							'like_count' => 1
						),
					),
				),
				'$set' => array(
					'post_title' => 'This is an example blog post',
				),
			),
			self::$model->next_update()
		);

		// Finally, update our data

		$data = self::$model->get();

		self::$model->update();

		$test_load = new Model_Blogpost;
		$test_load->_id = $data['_id'];

		$test_load->load();

		$this->assertEquals(
			self::$model->get(),
			$test_load->get()
		);
	}
}
