<?php

if ( ! class_exists('Mundo_Tests_Abstract')) {
	include dirname(__DIR__).'/abstract.php';
}

/**
 * Ensures updates are atomic where necessary (and where possible)
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Atomicity_Tests extends Mundo_Tests_Abstract {

	/**
	 * Ensures Mundo_Object throws an error when trying to access
	 * an atomic operation key in _next_update that doesn't exist
	 *
	 * @test
	 * @covers Mundo_Object::next_update
	 * @expectedException Mundo_exception
	 * @expectedexceptionmessage The atomic operation '$nonExistantatomic' does not exist
	 * @return void
	 */
	public function test_incorrect_atomic_operators_with_next_update()
	{
		$doc = new Model_Blogpost;
		$doc->next_update('$nonExistantAtomic');
	}

	/**
	 * Tests unset's atomicity
	 * @test
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::__unset
	 * @covers Mundo_Object::unset_atomic
	 * @covers Mundo_Object::next_update
	 * @covers Mundo_Object::_reset_update
	 *
	 * @param $data initial data
	 * @param $push data to push
	 * @returns void
	 */
	public function test_unset_atomicity()
	{
		/**
		 * Basic set method testing. This tests:
		 *   unset_atomic, __unset and calling unset_atomic from within set/__set
		 *
		 */
		$document = new Model_Blogpost();
		$document->post_title = 'Post title';

		$this->assertEquals(
			$document->next_update(),
			array(
				'$set' => array(
					'post_title' => 'Post title',
				),
			)
		);

		$this->assertEmpty($document->original());

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => 'Post title',
			)
		);

		$document->unset_atomic('post_title');

		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => NULL,
			)
		);
		// Test 1 (unset_atomic) domplete

		$document->post_title = 'Post title';

		unset($document->post_title);
		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => NULL,
			)
		);

		$document->post_title = 'Post title';

		// This should call unset in the set method
		$document->post_title = NULL;

		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => NULL,
			)
		);

		/**
		 * Test how unset works with unsaved embedded collections
		 */

		$document->push(
			'comments',
			array(
				'comment' => 'comment text',
				'comment_author' => 'comment author',
			)
		);

		$document->unset_atomic('comments.0.comment');

		$this->assertEquals(
			$document->next_update(),
			array(
				'$pushAll' => array(
					'comments' => array(
						array(
							'comment_author' => 'comment author'
						)
					),
				),
			)
		);

		/**
		 * Test how unset works with saved data
		 */

		$document->set(array(
			'post_title' => 'Title',
			'post_slug' => 'post-slug',
			'post_date' => new MongoDate,
			'author' => new MongoId,
			'author_name' => 'Author Name',
			'author_email' => 'email@example.com',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 2',
					'author_name' => 'Comment author',
					'author_email' => 'comment.2@example.com',
				)
			)
		));

		$document->save();

		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$document->unset_atomic('post_title');

		$this->assertNull($document->get('post_title'));
		$this->assertNull($document->changed('post_title'));

		$this->assertEquals(
			$document->next_update(),
			array(
				'$unset' => array(
					'post_title' => 1
				),
			)	
		);

		/**
		 * Test that re-setting data removes the $unset modifier
		 */

		$document->post_title = 'Post title';

		$this->assertEquals(
			array(
				'$set' => array(
					'post_title' => 'Post title',
				),
			),	
			$document->next_update()
		);
	}

	/**
	 * Test that the update method creates the correct atomic operation
	 * queries for updating data
	 *
	 * @test
	 * @covers Mundo_Object::update
	 * @dataProvider provider_update
	 *
	 * @param  array  array of model data to set and load
	 * @param  array  array of model data to change
	 * @param  array  expected atomic operation query
	 * @return void
	 */
	public function test_update_updates_atomically($data, $changed_data, $expected_atomic_operation)
	{
		/*
		$document = new Model_Blogpost($data);

		// Update requires a loaded document.
		$document->load();

		// Sanity check
		if ( ! $document->loaded())
		{
			$this->fail("A document could not be loaded");
		}

		// Replace our $data variable with the full document in the DB (add the _id field)
		$data = $document->get();

		// Change our data
		$document->set($changed_data);

		$document->update();

		// Merge our data to compare the new representation with what it should be
		$merged_data = array_merge(Mundo::flatten($data), Mundo::flatten($changed_data));
		$merged_data = Mundo::inflate($merged_data);

		// Test that the update was as atomic as we expected
		$this->assertEquals($document->last_update(), $expected_atomic_operation);

		// Test that the data is now saved
		$this->assertEquals($document->get(), $merged_data);

		// Test that changed is now empty
		$this->assertEmpty($document->changed());

		// Sanity check: reload the model in a new object and compare data to ensure it went to the database A-OK
		$reloaded_document = new Model_Blogpost();
		$reloaded_document->set('_id', $document->get('_id'))->load();

		// If the query was successful these should be the same
		$this->assertEquals($document->get(), $reloaded_document->get());
		 */
	}

	/** 
	 * @todo Test atomic operations using a query passed as an argument
	 *       on loaded and unloaded models (maybe?)
	 */

}
