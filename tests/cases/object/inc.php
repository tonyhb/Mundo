<?php

/**
 * Ensures the $inc method works correctly
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Inc_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Tests the inc set methods atomicity with the $inc modifier
	 *
	 * @test
	 * @covers Mundo_Object::inc
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::update
	 * @covers Mundo_Object::last_update
	 * @return void
	 */
	public function test_inc_atomicity()
	{
		$document = new Model_Blogpost;

		$document->set('post_title', 4);

		$modifiers = array();

		$modifiers['$inc'] = array(
			'post_title' => 4
		);
			
		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		// Ensure that overwriting a non-saved $inc uses $original data for calculations

		$document->set('post_title', 8);

		$modifiers['$inc'] = array(
			'post_title' => 8
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		// Test overwriting a saved $inc uses saved $original data for calctulations

		$data = array(
			'post_title' => 4,
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
		);

		$document->set($data);

		$document->save();

		$document->post_title = 5;

		$modifiers['$inc'] = array(
			'post_title' => 1
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		$document->post_title = 6;

		$modifiers['$inc'] = array(
			'post_title' => 2
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		/**
		 * Test the inc method
		 */
		$data = array(
			'post_title' => 4,
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
		);

		$document->set($data);

		$document->save();

		$document->inc('post_title', 5);

		$modifiers['$inc'] = array(
			'post_title' => 5
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		// Inc increments by $value, so ensure it is original + inc
		$this->assertEquals(
			9,
			$document->get('post_title')
		);

		// This increments on top of the new value too
		$document->inc('post_title', 6);

		$modifiers['$inc'] = array(
			'post_title' => 11
		);

		$update = $document->next_update();
		$this->assertEquals(
			$modifiers,
			$update
		);

		// Inc increments by $value, so ensure it is original + inc
		$this->assertEquals(
			15,
			$document->get('post_title')
		);

		/**
		 * Test updating from inc modifier
		 */
		$document->update();

		$this->assertEmpty($document->changed());

		$this->assertEquals(
			array(),
			$document->next_update()
		);

		// Ensure the update method saved the query array
		$this->assertEquals(
			array(
				'$inc' => array(
					'post_title' => 11
				)
			), 
			$document->last_update()
		);

		// Load a copy of the saved document to confirm changes
		$loaded_doc = new Model_Blogpost;
		$loaded_doc->_id = $document->_id;

		$loaded_data = $loaded_doc->load()->get();
		$doc_data = $document->get();

		// Remove IDs because they dont compare
		unset($loaded_data['_id']);
		unset($doc_data['_id']);

		// Ensure the data representing the mongo db is saved
		$data['post_title'] = 15;
		$this->assertEquals(
			$data,
			$doc_data
		);

		$this->assertEquals(
			$loaded_data,
			$doc_data
		);
	}

	/**
	 * Ensures the inc method throws an error when called on a field
	 * that has non-numeric saved data
	 *
	 * @test
	 * @covers Mundo_Object::inc
	 * @return void
	 */
	public function test_inc_throws_error_when_field_is_non_numeric()
	{
		$document = new Model_Blogpost;

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

		try
		{
			$document->inc('post_title', 5);
		}
		catch(Mundo_Exception $e)
		{
			$this->assertSame($e->getMessage(), "Cannot apply \$inc modifier to non-number in field 'post_title'");
			return;
		}

		$this->fail("The inc() method should have raised an exception when called upon a non-numeric field");
	}



	/**
	 * Ensures the inc method throws an error when called with a
	 * non-numeric value
	 *
	 * @test
	 * @covers Mundo_Object::inc
	 * @return void
	 */
	public function test_inc_throws_error_when_value_is_non_numeric()
	{
		$document = new Model_Blogpost;

		$document->set(array(
			'post_title' => 5,
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

		try
		{
			$document->inc('post_title', 'hello');
		}
		catch(Mundo_Exception $e)
		{
			$this->assertSame($e->getMessage(), "Cannot apply \$inc modifier with non-numeric values in field 'post_title'");
			return;
		}

		$this->fail("The inc() method should have raised an exception when called upon a non-numeric field");
	}

}
