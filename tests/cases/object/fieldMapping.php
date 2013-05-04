<?php

if ( ! class_exists('Mundo_Tests_Abstract')) {
	include dirname(__DIR__).'/abstract.php';
}

/**
 *
 *
 *
 */
class Mundo_Object_Mapping_tests extends Mundo_Tests_Abstract
{

	/**
	 * This tests basic first-level field mapping using the set method
	 *
	 * @test
	 */
	public function test_setting_mapped_fields()
	{
		// Test a basic set
		$object = Mundo::Factory('mapping');
		$object->set('post_title', 'The post title');

		$this->assertEquals(array(
			'$set' => array('t' => 'The post title'),
		), $object->next_update());

		$this->assertEquals('The post title', $object->get('post_title'));
	}

	/**
	 * This tests setting embedded arrays using the set method
	 *
	 * @test
	 */
	public function test_setting_embedded_arrays()
	{
		$object = Mundo::Factory('mapping');
		$object->set('post_metadata', array(
			'keywords' => 'key, words',
			'description' => 'Post description',
		));

		$this->assertEquals(array(
			'keywords' => 'key, words',
			'description' => 'Post description',
		), $object->get('post_metadata'));

		// Because we've set it using the `set` method this should update the 
		// $set atomic operator
		$this->assertEquals(array(
			'$set' => array('pm.k' => 'key, words', 'pm.d' => 'Post description'),
		), $object->next_update());
	}

	/**
	 * This ensures taht setting embedded documents (ie. multiple comments in 
	 * a blog post) works with field mapping
	 *
	 * @test
	 */
	public function test_setting_embedded_documents()
	{
		$object = Mundo::Factory('mapping');

		$object->set(array(
			'comments' => array(
				array(
					'comment' => 'Comment 1',
					'author_name' => 'Author',
					'author_email' => 'author@example.com',
				)
			))
		);

		$this->assertEquals(array('comments' => array(
			array(
				'comment' => 'Comment 1',
				'author_name' => 'Author',
				'author_email' => 'author@example.com',
			)
		)),
		$object->get());

		$this->assertEquals(array(
			'$set' => array(
				'c.0.c' => 'Comment 1',
				'c.0.an' => 'Author',
				'c.0.ae' => 'author@example.com',
			)),
			$object->next_update());
	}

	public function test_pushing_and_popping_mapped_fields()
	{

		/**
		 * Test the push method
		 */
		$object = Mundo::Factory('mapping');
		$object->push('comments', array(
			'comment' => 'Comment 1',
			'author_name' => 'Author',
			'author_email' => 'author@example.com',
		));

		$this->assertEquals(array('comments' => array(
			array(
				'comment' => 'Comment 1',
				'author_name' => 'Author',
				'author_email' => 'author@example.com',
			)
		)),
		$object->get());

		$this->assertEquals(array(
			'$pushAll' => array(
				'c' => array(
					array(
						'c' => 'Comment 1',
						'an' => 'Author',
						'ae' => 'author@example.com',
					)
				)
			)),
			$object->next_update());

		/**
		 * Test the pop method after an unsaved push
		 */
		$object->pop('comments');
	}

	/**
	 * @todo Test the pop method after a saved push
	 */

	/**
	 * @todo Test the unset modifier
	 */

	/**
	 * @todo Test the inc modifier
	 */

	/**
	 * @todo Ensure the create method works with mapped fields
	 */

	/**
	 * @todo Ensure the save method works with mapped fields
	 */

	/**
	 * @todo Ensure the delete method works with mapped fields
	 */

	/**
	 * @todo Ensure the find method works with mapped fields
	 */

	/**
	 * @todo Ensure the load method works with mapped fields
	 */

	/**
	 * @todo Ensure validation works with mapped fields
	 */

}
