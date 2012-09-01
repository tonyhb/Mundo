<?php

/**
 * Tests the Mundo_Object class, which
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Find_Tests extends Mundo_Tests_Abstract {

	/**
	 * Provides test data for cursor tests
	 *
	 */
	public static function provider_cursor()
	{
		// $data, $expected_error (null if it should succeed), $expected_result (with dot notation)
		return array(
			// A full dataset
			array(
				array(
					array(
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
					),
					array(
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
					)
				),
			),
		);
	}

	/**
	 * This tests that Mundo returns:
	 *   + Empty cursors where necessary
	 *   + Cursor with the correct number of models in
	 *   + Cursors containing a Mundo_Object model instead of array data
	 *   + Cursors with partial fields have correctly initialised models
	 *
	 * @test
	 * @dataProvider provider_cursor
	 */
	public function test_find_cursor_works_as_expected($posts)
	{
		$model = Mundo::factory('blogpost');
		$model->set($posts[0])->create();

		// Ensure there's no results returned when we look for non-existent data
		$cursor = Mundo::factory('blogpost')->set('author_name', 'Non Existent Name')->find();
		$this->assertEquals(0, $cursor->count());

		// Ensure running Find(); with 1 document in the colleciton returns it.
		$cursor = Mundo::factory('blogpost')->find();
		$this->assertEquals(1, $cursor->count());

		// Try searching for our author
		$cursor = Mundo::factory('blogpost')->set('author_name', 'Author Jones')->find();
		$this->assertEquals(1, $cursor->count());

		// Test that moving forward using next() and then current() returns a Mundo 
		// model
		$cursor->next();
		$data = $cursor->current();
		$this->assertInstanceOf('Model_Blogpost', $data);
		$this->assertEquals($posts[0], $data->original());
		$this->assertEquals($posts[0], $data->original());
		$this->assertFalse($data->partial());

		// Try searching for our author
		$cursor = Mundo::factory('blogpost')->set('author_name', 'Author Jones')->find();
		$this->assertEquals(1, $cursor->count());

		// Test that getNext() also returns a model
		$data = $cursor->getNext();
		$this->assertInstanceOf('Model_Blogpost', $data);
		$this->assertEquals($posts[0], $data->original());
		$this->assertEquals($posts[0], $data->original());
		$this->assertFalse($data->partial());

		// Create a asecond blogpost
		$model = new Model_Blogpost;
		$model->set($posts[1])->create();

		// Ensure this loads the author's two documents
		$cursor = Mundo::Factory('blogpost')->set('author_name', 'Author Jones')->find();
		$this->assertEquals(2, $cursor->count());

		// Ensure we only load one blogpost with the right contetn, though
		$cursor = Mundo::Factory('blogpost')->set('post_content', 'This is blogpost number 1')->find();
		$this->assertEquals(1, $cursor->count());

		// Ensure loading with partial fields works OK
		$cursor = Mundo::Factory('blogpost')->find(array('author_name' => 1, 'post_content' => 1));
		$data = $cursor->getNext();
		$this->assertTrue($data->partial());
		$this->assertTrue($data->loaded());
		$this->assertNull($data->get('post_title'));

		// Ensure loading with partial fields works OK using TRUE instead of 
		// 1 for fields
		$cursor = Mundo::Factory('blogpost')->find(array('author_name' => TRUE, 'post_content' => TRUE));
		$data = $cursor->getNext();
		$this->assertTrue($data->partial());
		$this->assertTrue($data->loaded());
		$this->assertNull($data->get('post_title'));

		// Ensure loading with all but comments works OK
		$cursor = Mundo::Factory('blogpost')->set('post_slug', 'Blogpost-1')->find(array('comments' => 0));
		$data = $cursor->getNext();
		$this->assertTrue($data->partial());
		$this->assertTrue($data->loaded());
		$this->assertNull($data->get('comments'));
		$post_without_comments = $posts[0];
		unset($post_without_comments['comments']);
		$this->assertEquals($post_without_comments, $data->get());
	}
}
