<?php

if ( ! class_exists('Mundo_Tests_Abstract')) {
	include dirname(__DIR__).'/abstract.php';
}

/**
 * Ensures getting and setting works as expected. This covers both magic methods 
 * and the get/set methods.
 *
 * Note: Atomicity tests are performed in a separate atomicity test class.
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_getAndSet_Tests extends Mundo_Tests_Abstract {

	/**
	 * Provider for test_single_set_and_get
	 *
	 * @return array
	 */
	public static function provider_single_set_and_get()
	{
		return array(
			// Set a value in a root-level field
			array(
				'post_title',
				'Post title goes here',
				NULL
			),
			// Use dot-notation to set an array value
			array(
				'post_metadata.keywords',
				'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
				NULL,
				array(
					'post_metadata' => array(
						'keywords' => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
					)
				)
			)
		);
	}

	/**
	 * Tests that the set and get functions work as expected
	 *
	 *
	 * @test
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::changed
	 * @covers Mundo_Object::_check_field_exists
	 * @dataProvider provider_set_and_get
	 * @param  array  $data             The array of data to set
	 * @param  mixed  $expected_error   Null if setting should pass, otherwise the error exception message.
	 * @param  array  $expected_result  The expected result of get() if it isn't the same as $data.
	 *
	 */
	public function test_set_and_get($data, $expected_error, $expected_result = NULL)
	{

		$Mundo = new Model_Blogpost;

		if ($expected_error)
		{
			try
			{
				// Setting our data should fail
				$Mundo->set($data);
			}
			catch (Exception $e)
			{
				// Ensure our error message is correct and it failed for the right reasons.
				$this->assertEquals($e->getMessage(), $expected_error);
			}
		}
		else
		{
			// Set our data
			$Mundo->set($data);

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($Mundo->get(), $expected_result);
				$this->assertSame($Mundo->changed(), $expected_result);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($Mundo->get(), $data);
				$this->assertSame($Mundo->changed(), $data);
			}


			// Ensure the atomic operators got updated
			$modifiers = array('$set' => array());
			$flat_data = Mundo::flatten($data);
			$modifiers['$set'] += $flat_data;

			$this->assertEquals($modifiers, $Mundo->next_update());
		}
	}

	/**
	 * Tests that overloading properties works the same as setting and getting single fields
	 *
	 * @test
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::__set
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::__get
	 * @covers Mundo_Object::__isset
	 * @covers Mundo_Object::changed
	 * @covers Mundo_Object::_check_field_exists
	 * @dataProvider provider_set_and_get
	 * @param   string  $data 
	 * @param   string  $expected_error 
	 * @param   string  $expected_result 
	 * @return  void
	 */
	public function test_overloading_set_get_and_isset($data, $expected_error, $expected_result = NULL)
	{

		$document = new Model_Blogpost;

		if ($expected_error)
		{
			foreach ($data as $field => $value)
			{
				try
				{
					// Set each single field via overloading
					$document->$field = $value;
				}
				catch(Exception $e)
				{
					// Ensure our error message is correct and it failed for the right reasons.
					$this->assertEquals($e->getMessage(), $expected_error);
				}
			}
		}
		else
		{
			foreach ($data as $field => $value)
			{
				// Ensure __isset returns false with no data
				$this->assertFalse(isset($document->$field));

				// Set each single field via overloading
				$document->$field = $value;

				// Assert overloading method __isset returns true when data is set
				$this->assertTrue(isset($document->$field));

				// Ensure getting data through normal methods and overloading works, hence the data was added OK.
				$this->assertEquals($document->get($field), $data[$field]);
				$this->assertEquals($document->$field, $document->get($field));
			}

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($document->get(), $expected_result);
				$this->assertSame($document->changed(), $expected_result);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($document->get(), $data);
				$this->assertSame($document->changed(), $data);
			}
		}
	}

	/**
	 * Test setting and getting single fields at once works as expected
	 *
	 * @test
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::_check_field_exists
	 * @covers Mundo_Object::changed
	 * @dataProvider provider_single_set_and_get
	 * @param  string  $field            Name of field we are setting
	 * @param  string  $value            Value of the field
	 * @param  string  $expected_error   Expected error message, if any
	 * @param  string  $expected_result  Expected result, if different from array($field => $value)
	 */
	public function test_single_set_and_get($field, $value, $expected_error, $expected_result = NULL)
	{
		$Mundo = new Model_Blogpost;

		if ($expected_error)
		{
			try
			{
				// Setting our data should fail
				$Mundo->set($field, $value);
			}
			catch (Exception $e)
			{
				// Ensure our error message is correct and it failed for the right reasons.
				$this->assertEquals($e->getMessage(), $expected_error);
			}
		}
		else
		{
			// Set our data
			$Mundo->set($field, $value);

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($Mundo->get($field), $value);
				$this->assertSame($Mundo->get(), $expected_result);
				$this->assertSame($Mundo->changed($field), $value);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($Mundo->get($field), $value);
				$this->assertSame($Mundo->get(), array($field => $value));
				$this->assertSame($Mundo->changed(), array($field => $value));
			}
		}
	}

	/**
	 * This ensures that getting data which has a mixture of original and 
	 * changed data returns the correct merge of the two.
	 *
	 * @test
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::_merge
	 * @dataProvider provider_validate_and_create_data
	 * @return void
	 * @author Tony Holdstock-Brown
	 */
	public function test_using_get_with_loaded_and_changed_data_merges($data, $validation_status, $expcted_validation_errors = NULL)
	{
		// If the data hasn't been validated it won't have been saved, so skip this test
		if ( ! $validation_status)
			return;

		// First, create the document.
		$document = new Model_Blogpost;
		$document->set($data)->save();
		unset($document); # Verbosity

		$document = new Model_Blogpost;
		$document->set($data)->load();

		if ( ! $document->loaded())
		{
			$this->fail("A document has not been loaded");
		}

		if (count($document->changed()) > 0)
		{
			$this->fail('A document has been loaded but the $_changed variable has not been emptied');
		}

		// Get our loaded data
		$data = $document->get();

		// Our new data
		$new_data = array(
			'post_title' => 'New post title',
			'post_slug'  => 'New post slug',
			'post_metadata.keywords' => 'New post keywords',
		);
		$document->set($new_data);

		$this->assertEquals(Mundo::flatten($document->changed()), $new_data);

		// Get the $data and $new_data merge for comparison
		$data = Mundo::flatten($data);
		$data = array_merge($data, $new_data);
		$data = Mundo::inflate($data);

		// Ensure merging works fine
		$this->assertSame($document->get(), $data);
	}

}
