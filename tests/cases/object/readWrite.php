<?php

if ( ! class_exists('Mundo_Tests_Abstract')) {
	include dirname(__DIR__).'/abstract.php';
}

/**
 * Ensures the `create()`, `read()`, `write()` and `update()` database 
 * interaction methods communicate with the database as expected.
 *
 * Note: Atomicity tests are performed in a separate atomicity test class.
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_readWrite_Tests extends Mundo_Tests_Abstract {

	/**
	 * Check that the create() method adds documents to our collection, adds
	 * the ObjectId to our $_data and throws validation exceptions if data
	 * does not pass the validation check.
	 *
	 * @test
	 * @covers Mundo_Object::create
	 * @covers Mundo_Object::loaded
	 * @covers Mundo_Object::changed
	 * @covers Mundo_Object::original
	 * @covers Mundo_Object::load
	 * @covers Mundo_Object::validate
	 * @covers Mundo_Object::_validate
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param  array   $data   Data to add to the DB
	 * @param  string  $validation_status   Whether $data should pass validation checks
	 * @param  string  $expected_validation_errors    Expected validation error messages
	 * @return void
	 */
	public function test_create_and_load_and_load_selected_fields($data, $validation_status, $expected_validation_errors = NULL)
	{
		$document = new Model_Blogpost($data);

		if ($validation_status)
		{
			// Attempt to create
			$document->create();

			// Ensure an ObjectId has been added to our data, which indicates the save
			$this->assertInstanceOf('MongoId', $document->get('_id'));

			// Ensure we are now loaded
			$this->assertTrue($document->loaded());

			// Save our new data with the ObjectId
			$saved_data = $document->get();

			// Ensure that all data has been moved into the $_data variable
			$this->assertEquals($saved_data, $document->original());

			// Ensure we have no changed data
			$this->assertempty($document->changed());

			// Test reloading our saved object
			$this->assertEquals($document->load()->get(), $saved_data);
			
			//
			// Test loading from our ID
			//

			$loaded_object = new Model_Blogpost;
			$loaded_object->set('_id', $document->original('_id'));
			$loaded_object->load();

			$this->assertEquals($loaded_object->get(), $document->original());
			
			//
			// Test loading from non-unique keys.
			//

			$loaded_object = new Model_Blogpost($data);
			$loaded_object->load();
			$this->assertEquals($loaded_object->get(), $document->original());	

			// Ensure we can't run create() when the model is alread loaded
			try
			{
				$document->create();
			}
			catch(Mundo_Exception $e)
			{
				$this->assertSame($e->getMessage(), "Cannot create a new document because the model is already loaded");

				return;
			}

			$this->fail("Data was created despite already specifying an ObjectId");
		}
		else
		{
			try
			{
				// Attempt to create
				$document->create();
			}
			catch(Validation_Exception $e)
			{
				// Ensure an ObjectId hasn't been added to our data
				$this->assertSame($data, $document->get());
				$this->assertSame($data, $document->changed());

				// Assert we're not loaded
				$this->assertFalse($document->loaded());

				// Ensure that we failed for the expected reasons
				$this->assertSame($e->array->errors(TRUE), $expected_validation_errors);

				return;
			}

			$this->fail("Data should have failed validation but an exception was not raised");
		}
	}

	/**
	 * This follows on from the previos method of creating data, and uses
	 * the same saved data to attempt to load selected fields only.
	 *
	 * The model should load selected fields and set $_partial to true,
	 * which should stop rogue save() calls overwriting the whole object.
	 *
	 * @test
	 * @covers Mundo_Object::load
	 * @covers Mundo_Object::partial
	 * @covers Mundo_Object::loaded
	 * @covers Mundo_Object::save
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param  array   $data   Data added to the database and available to query
	 * @param  string  $validation_status   Whether $data should pass validation checks
	 * @param  string  $expected_validation_errors    Expected validation error messages
	 * @return void
	 */
	public function test_loading_partial_fields_works_and_running_save_fails($data, $validation_status, $expected_validation_errors = NULL)
	{
		if ($validation_status)
		{
			$load_selected_fields = new Model_Blogpost();

			// Use a required field
			$load_selected_fields->post_slug = $data['post_slug'];

			// Load just the ID and post title from the post slug
			$load_selected_fields->load(array('_id', 'post_title'));

			// Ensure that partial() returns true because of the limited field returns
			$this->assertTrue($load_selected_fields->partial());

			// Create our array of data to compare to
			$expected_getdata = array(
				'_id' => $load_selected_fields->_id,
				'post_title' => $data['post_title'],
				'post_slug' => $data['post_slug'],
			);

			// Ensure that we've only got our selected fields
			$this->assertEquals($expected_getdata, $load_selected_fields->get());

			// Change a variable to allow saving/updating
			$load_selected_fields->post_title = 'This is a new post title';

			try
			{
				$load_selected_fields->save();
			}
			catch(Mundo_Exception $e)
			{
				$this->assertSame("Cannot save the model because it is only partially loaded. Use the update method instead or fully load the object", $e->getMessage());
				return;
			}

			$this->fail("Model should not have saved because it is partially loaded");
		}
	}

	/**
	 * When a partial model has been loaded, ensure that running load
	 * and returning the whole object sets $_partial to TRUE
	 *
	 * @test
	 * @covers Mundo_Object::load
	 * @covers Mundo_Object::partial
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param  array   $data   Data added to the database and available to query
	 * @param  string  $validation_status   Whether $data should pass validation checks
	 * @param  string  $expected_validation_errors    Expected validation error messages
	 * @return void
	 */
	public function test_loading_partial_then_loading_fully_resets_partial_property($data, $validation_status, $expected_validation_errors = NULL)
	{
		if ($validation_status)
		{
			$load_selected_fields = new Model_Blogpost();

			// Use a required field
			$load_selected_fields->post_slug = $data['post_slug'];

			// Load just the ID and post title from the post slug
			$load_selected_fields->load(array('_id', 'post_title'));

			// Ensure that partial() returns true because of the limited field returns
			$this->assertTrue($load_selected_fields->partial());

			$load_selected_fields->load();

			$this->assertFalse($load_selected_fields->partial());
		}
	}

	/**
	 * Tests how the load() method handles loading with no data
	 *
	 * @covers Mundo_Object::load
	 * @expectedException Mundo_exception
	 * @expectedexceptionmessage no model data supplied
	 * @return void
	 */
	public function test_document_loading_with_no_data()
	{
		$document = new Model_Blogpost;
		$document->load();
	}

	/**
	 * Ensures that the document's representation in the database is saved
	 * correctly. The save() method is essentially a duplicate of the 
	 * driver's method: this does not use atomic operations.
	 *
	 * @test
	 * @covers Mundo_Object::save
	 * @dataProvider provider_update
	 * @return void
	 */
	public function test_save_document_with_valid_and_previously_inserted_data($data, $changed_data, $expected_query)
	{
		$document = new Model_Blogpost;

		// Set our data and load the correct document from the database
		$document->set($data)->load();

		// Basic sanity checking without asserts
		if ( ! $document->loaded())
		{
			$this->fail("A document could not be loaded");
		}

		$changed = $document->changed();
		if ( ! empty($changed))
		{
			$this->fail("The document was loaded but the _changed variable not emptied");
		}

		// Merge our data for the assertions
		$flattened_data = Mundo::flatten($document->get());
		$flattened_changed = Mundo::flatten($changed_data);

		foreach($flattened_data as $field => $value)
		{
			// Merge our arrays like this so the NULL mimicing unset takes effect
			if (array_key_exists($field, $flattened_changed))
				$flattened_data[$field] = $flattened_changed[$field];
		}

		$merged_data = Mundo::inflate($flattened_data);

		// Update our values in the model
		$document->set($changed_data);
		$document->save();

		// Original data will be $merged from above
		$this->assertEquals($document->original(), $merged_data);

		// And changed should have been emptied.
		$this->assertEmpty($document->changed());
	}

	/**
	 * Test saving data  with invalid data throws a validation exception
	 * instead of saving.
	 *
	 * @test
	 * @covers Mundo_Object::save
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param   array  $data             array of model data to set
	 * @param   bool   $check_result     Whether the validation check() method should return true or false
	 * @param   array  $expected_errors  Array of expected error messages from the errors() method
	 * @return void
	 */
	public function test_saving_invalid_data_throws_exception($data, $validation_status, $expected_validation_errors = NULL)
	{
		// Set our data.
		$document = new Model_Blogpost($data);

		if ( ! $validation_status)
		{
			try
			{
				// Attempt to create
				$document->save();
			}
			catch(Validation_Exception $e)
			{
				// Ensure an ObjectId hasn't been added to our data
				$this->assertSame($data, $document->get());
				$this->assertSame($data, $document->changed());

				// Assert we're not loaded
				$this->assertFalse($document->loaded());

				// Ensure that we failed for the expected reasons
				$this->assertSame($e->array->errors(TRUE), $expected_validation_errors);

				return;
			}

			$this->fail("Data should have failed validation but an exception was not raised");

		}
	}

	/**
	 * Test saving an unloaded model or model without an _id results in 
	 * saving using an upsert.
	 *
	 * @test
	 * @covers Mundo_Object::save
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param   array  $data             array of model data to set
	 * @param   bool   $check_result     Whether the validation check() method should return true or false
	 * @param   array  $expected_errors  Array of expected error messages from the errors() method
	 * @return void
	 */
	public function test_saving_unloaded_document_results_in_upsert($data, $validation_status, $expected_validation_errors = NULL)
	{
		// Set our data.
		$document = new Model_Blogpost($data);

		if ($validation_status)
		{
			$document->save();

			// Ensure an ObjectId has been added to our data, which indicates the save
			$this->assertInstanceOf('MongoId', $document->get('_id'));

			// Ensure we are now loaded
			$this->assertTrue($document->loaded());

			// Save our new data with the ObjectId
			$saved_data = $document->get();

			// Ensure that all data has been moved into the $_data variable
			$this->assertEquals($saved_data, $document->original());

			// Ensure we have no changed data
			$this->assertempty($document->changed());

			// Test reloading our saved object
			$this->assertEquals($document->load()->get(), $saved_data);

			// Test loading from our ID
			$loaded_object = new Model_Blogpost;
			$loaded_object->set('_id', $document->original('_id'));
			$loaded_object->load();

			$this->assertEquals($loaded_object->get(), $document->original());
		}
	}

	/**
	 * Ensures that running the update method on an object that hasn't been
	 * loaded from the database fails
	 *
	 * @test
	 * @covers Mundo_Object::update
	 * @expectedException Mundo_Exception
	 * @expectedExceptionMessage Cannot atomically update the document because the model has not yet been loaded
	 *
	 * @return void
	 */
	public function test_updating_unloaded_object_fails()
	{
		$document = new Model_Blogpost;

		$document->set('post_title', 'This is the post title');

		$document->update();
	}

	/**
	 * @Todo Test saving with no changed data does nothing
	 */

	/**
	 * @todo Test update() fails with invalid data
	 */

}
