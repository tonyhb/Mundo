<?php

if ( ! class_exists('Mundo_Tests_Abstract')) {
	include dirname(__DIR__).'/abstract.php';
}

/**
 * Ensures unsetting model properties works as expected.
 *
 * Note: Atomicity tests are performed in a separate atomicity test class.
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Unset_Tests extends Mundo_Tests_Abstract {

	/**
	 * Tests that the overloading method __unset works as expected with unloaded models
	 *
	 * @test
	 * @covers Mundo_Object::__isset
	 * @covers Mundo_Object::__unset
	 * @dataProvider provider_set_and_get
	 * @param string $data 
	 * @param string $expected_error 
	 * @return void
	 */
	public function test_overloading_unloaded_unset($data, $expected_error, $expected_result = NULL)
	{
		if ($expected_error)
		{
			// Skip non-valid data, as this is already tested in test_overloading_set_get_and_isset.
			return;
		}

		$document = new Model_Blogpost;

		// Set and unset each piece of data in series
		foreach ($data as $field => $value)
		{
			// Just double-check that, in case of some mysterious magic, we haven't got any data
			$this->assertNull($document->$field);

			$document->set($field, $value);

			$this->assertEquals($document->get($field), $data[$field]);

			// And unset our data
			unset($document->$field);

			$this->assertNull($document->changed($field));
		}
	}

	/**
	 * Tests that the overloading method __unset works as expected with loaded models
	 *
	 * @test
	 * @covers Mundo_Object::__isset
	 * @covers Mundo_Object::__unset
	 * @dataProvider provider_validate_and_create_data
	 * @param string $data 
	 * @param string $expected_error 
	 * @return void
	 * @author Tony Holdstock-Brown
	 */
	public function test_overloading_loaded_unset($data, $validation_status, $expected_validation_errors = NULL)
	{
		if ( ! $validation_status)
			return;

		// Create our model
		$document = new Model_Blogpost;

		// Set our data for loading the model from Mongo
		$document->set($data);
		$document->save($data);
		unset($document);

		$document = new Model_Blogpost;

		// Set our data for loading the model from Mongo
		$document->set($data);
		$document->load();

		// Ensure we've got loaded data
		$this->assertTrue($document->loaded());
		$this->assertInstanceOf('MongoId', $document->get('_id'));
		$this->assertEmpty($document->changed());

		$fields = $document->get();
		foreach($fields as $field => $value)
		{
			// Unset our already saved data
			unset($document->$field);

			$this->assertNull($document->changed($field));
			$this->assertEquals($document->original($field), $fields[$field]);
		}

		// Ensure that get() returns NULL because everything is unset
		$this->assertEquals(NULL, $document->get());

		// Assert that all of the keys now hold NULL
		foreach($document->changed() as $value)
		{
			$this->assertNull($value);
		}
	}

}
