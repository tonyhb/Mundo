<?php

/**
 * Ensures the $pop method works correctly
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Pop_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Tests the pop() method
	 *
	 * @test
	 * @covers Mundo_Object::pop
	 */
	public function test_pop_with_unsaved_data()
	{
		$data = array(
			'post_title' => 'Title',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 1',
				),
				array(
					'comment' => 'Comment 2',
				),
				array(
					'comment' => 'Comment 3',
				)
			)
		);

		$document = new Model_Blogpost($data);


		$doc_return = $document->pop('comments');
		$var_return = array_pop($data['comments']);

		$this->assertEquals($document->get('comments'), $data['comments']);
		$this->assertEquals($doc_return, $var_return);

		// Make sure that our atomic update is OK
		$this->assertEquals(
			$document->next_update('$pop'),
			array(
				'comments' => 1,
			)
		);

		try
		{
			// Test that popping a non-array field
			$document->pop('post_title');
		}
		catch(Mundo_Exception $e)
		{
				$this->assertEquals($e->getMessage(), "Field 'post_title' is not an array");
				return;
		}

		$this->fail("Model should have thrown an exception when attempting to pop a non-array field");
	}

	/**
	 * Test that running pop() after push() works correctly.
	 * Pop() should set the array key to NULL and also remove the $pushAll
	 * query in _next_update.
	 *
	 * @test
	 * @covers Mundo_Object::push
	 * @covers Mundo_Object::pop
	 * @return void
	 */
	public function test_unsaved_push_then_pop_then_push_then_pop()
	{
		$data = array(
			'post_title' => 'Title',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 1',
				),
				array(
					'comment' => 'Comment 2',
				),
				array(
					'comment' => 'Comment 3',
				)
			)
		);

		$document = new Model_Blogpost($data);

		$document->push('comments',
			array(
				'comment' => 'Comment 4',
			)
		);

		// Basic sanity checks on the push
		$this->assertEquals(
			$document->next_update('$pushAll'), 
			array(
				'comments' => array(
					array(
						'comment' => 'Comment 4',
					)
				)
			)
		);

		$this->assertEquals(
			$document->get('comments'), 
			array_merge(
				$data['comments'], 
				array(
					array('comment' => 'Comment 4')
				)
			)
		);

		$pop = $document->pop('comments');

		// Ensure we got the just-pushed embedded collection
		$this->assertEquals($pop, array('comment' => 'Comment 4'));

		// Ensure that the next update doesn't contain anything in $pop and
		// instead removed the $pushAll query
		$this->assertEquals(
			$document->next_update('$pushAll'),
			array()
		);
		$this->assertEquals(
			$document->next_update('$pop'),
			array()
		);

		/** Now test running 2 pops and one pull leaves one in $pushAll */

		// This also tests push() after pop()
		$document->push('comments',
			array(
				'comment' => 'Comment 4',
			),
			array(
				'comment' => 'Comment 5',
			)
		);

		// Basic sanity checks on the push
		$this->assertEquals(
			$document->next_update('$pushAll'), 
			array(
				'comments' => array(
					array(
						'comment' => 'Comment 4',
					),
					array(
						'comment' => 'Comment 5',
					)
				)
			)
		);

		$this->assertEquals(
			$document->get('comments'), 
			array_merge(
				$data['comments'], 
				array(
					array('comment' => 'Comment 4'),
					array('comment' => 'Comment 5'),
				)
			)
		);

		$pop = $document->pop('comments');

		// Ensure we got the just-pushed embedded collection
		$this->assertEquals($pop, array('comment' => 'Comment 5'));
		$this->assertEquals(count($document->get('comments')), 4);

		// Ensure that the next update doesn't contain anything in $pop and
		// instead removed the $pushAll query
		$this->assertEquals(
			$document->next_update('$pushAll'),
			array(
				'comments' => array(
					array(
						'comment' => 'Comment 4',
					),
				),
			)
		);
		$this->assertEquals(
			$document->next_update('$pop'),
			array()
		);

		$pop = $document->pop('comments');

		// Ensure we got the just-pushed embedded collection
		$this->assertEquals($pop, array('comment' => 'Comment 4'));
		$this->assertEquals(count($document->get('comments')), 3);

		// Ensure that the next update doesn't contain anything in $pop and
		// instead removed the $pushAll query
		$this->assertEquals(
			$document->next_update('$pushAll'),
			array()
		);
		$this->assertEquals(
			$document->next_update('$pop'),
			array()
		);

	}

}
