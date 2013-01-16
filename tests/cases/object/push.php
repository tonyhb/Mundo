<?php

/**
 * Ensures the $push method works correctly
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Push_Tests extends PHPUnit_Framework_TestCase {

	public function provider_push()
	{
		// $model_data, $multiple_push_arrays, $push_data, $expected_result, $atomic_operation, $save
		return array(
			array(
				array(
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
				),
				FALSE,
				array(
					'comment' => 'Comment 3'
				),
				array(
					array(
						'comment' => 'Comment 2',
						'author_name' => 'Comment author',
						'author_email' => 'comment.2@example.com',
					),
					array(
						'comment' => 'Comment 3'
					),
				),
				array(
					'comments' => array(
						array(
							'comment' => 'Comment 3'
						),
					),
				),
				TRUE
			),
			array(
				array(
					'post_title' => 'Title',
					'post_content' => 'Content',
					'post_metadata' => array(
						'keywords' => 'keyword one',
						'description' => 'keyword two',
					),
					'comments' => array(
						array(
							'comment' => 'Comment 1',
						)
					)
				),
				TRUE,
				array(
					array(
						'comment' => 'Comment 2'
					),
					array(
						'comment' => 'Comment 3',
						'author'  => '3rd comment author',
					)
				),
				array(
					array(
						'comment' => 'Comment 1',
					),
					array(
						'comment' => 'Comment 2'
					),
					array(
						'comment' => 'Comment 3',
						'author'  => '3rd comment author',
					)
				),
				array(
					'comments' => array(
						array(
							'comment' => 'Comment 2'
						),
						array(
							'comment' => 'Comment 3',
							'author'  => '3rd comment author',
						)
					),
				),
			),
		);
	}

	/**
	 * Tests the push() method, which replaces array_push on model data
	 * These tests modify the comments field.
	 *
	 * @test
	 * @covers Mundo_Object::push
	 * @covers Mundo_Object::_check_field_exists
	 * @covers Mundo_Object::next_update
	 * @dataProvider provider_push
	 *
	 * @param $data initial data
	 * @param $push data to push
	 * @returns void
	 */
	public function test_push_with_unsaved_data($data, $multiple_push_arrays, $push_data, $expected_result, $atomic_operation)
	{
		// Duplicate data so we can run multiple pushes twice (the data is modified by array_push)
		$var_data = $data;

		// Initialise our model
		$document = new Model_Blogpost($data);

		if ($multiple_push_arrays)
		{
			// Call push with multiple arrays
			$doc_count = call_user_func_array(array($document, "push"), array_merge(array('comments'), $push_data));
			$var_count = call_user_func_array('array_push', array_merge(array(&$var_data['comments']), $push_data));
		}
		else
		{
			// Add data to the array
			$doc_count = $document->push('comments', $push_data);
			$var_count = array_push($var_data['comments'], $push_data);
		}

		// Ensure pushing the array had the expected results
		$this->assertEquals($expected_result, $document->get('comments'));

		// The model's push should work the same as the normal function
		$this->assertEquals($var_data['comments'], $document->get('comments'));
		$this->assertEquals($doc_count, $var_count);

		// Ensure that the atomic query for this change was written
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));

		// If there are multiple push arrays try setting them one at a time now.
		if ( ! $multiple_push_arrays)
			return;

		$var_data = $data;

		$document = new Model_Blogpost($data);

		foreach($push_data as $push)
		{
			$doc_count = $document->push('comments', $push);
			$var_count = array_push($var_data['comments'], $push);

			// Make sure everything's OK each time
			$this->assertEquals($doc_count, $var_count);
			$this->assertEquals($var_data['comments'], $document->get('comments'));
		}

		$this->assertEquals($expected_result, $document->get('comments'));
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));
	}

	/**
	 * Tests the push() method, which replaces array_push on model data
	 * These tests modify the comments field.
	 *
	 * @test
	 * @covers Mundo_Object::push
	 * @covers Mundo_Object::_check_field_exists
	 * @covers Mundo_Object::next_update
	 * @covers Mundo_Object::_reset_update
	 * @dataProvider provider_push
	 *
	 * @param $data initial data
	 * @param $push data to push
	 * @returns void
	 */
	public function test_push_with_saved_data($data, $multiple_push_arrays, $push_data, $expected_result, $atomic_operation, $save = FALSE)
	{
		if ( ! $save)
			return;

		// Duplicate data so we can run multiple pushes twice (the data is modified by array_push)
		$var_data = $data;

		// Initialise our model
		$document = new Model_Blogpost($data);

		if ($document->next_update() == array())
		{
			$this->fail('The update array should have been updated when setting $data');
			return;
		}

		$document->save();

		// Ensure saving resets our update array
		$this->assertEquals($document->next_update(), array());

		if ($multiple_push_arrays)
		{
			// Call push with multiple arrays
			$doc_count = call_user_func_array(array($document, "push"), array_merge(array('comments'), $push_data));
			$var_count = call_user_func_array('array_push', array_merge(array(&$var_data['comments']), $push_data));
		}
		else
		{
			// Add data to the array
			$doc_count = $document->push('comments', $push_data);
			$var_count = array_push($var_data['comments'], $push_data);
		}

		// Ensure pushing the array had the expected results
		$this->assertEquals($expected_result, $document->get('comments'));

		// The model's push should work the same as the normal function
		$this->assertEquals($var_data['comments'], $document->get('comments'));
		$this->assertEquals($doc_count, $var_count);

		// Ensure that the atomic query for this change was written
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));

		// If there are multiple push arrays try setting them one at a time now.
		if ( ! $multiple_push_arrays)
			return;

		$var_data = $data;

		$document = new Model_Blogpost($data);

		foreach($push_data as $push)
		{
			$doc_count = $document->push('comments', $push);
			$var_count = array_push($var_data['comments'], $push);

			// Make sure everything's OK each time
			$this->assertEquals($doc_count, $var_count);
			$this->assertEquals($var_data['comments'], $document->get('comments'));
		}

		$this->assertEquals($expected_result, $document->get('comments'));
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));

		/**
		 * @todo SAVE
		 */
	}

	/**
	 *
	 * @expectedException Mundo_Exception
	 * @expectedExceptionMessage Field 'foo' does not exist
	 *
	 * @return void
	 */
	public function test_pushing_invalid_field_throws_error()
	{
		$document = new Model_Blogpost();
		$document->push('foo', array('bar' => FALSE));
	}

}
