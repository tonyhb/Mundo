<?php

if ( ! class_exists('Mundo_Tests_Abstract')) {
	include dirname(__DIR__).'/abstract.php';
}

/**
 * Ensures validation functionality works as expected
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Validate_Tests extends Mundo_Tests_Abstract {

	/**
	 * Validates data that has already been set (from the _merge method)
	 *
	 * @covers Mundo_Object::validate
	 * @covers Mundo_Object::_extract_rules
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param   array  $data             array of model data to set
	 * @param   bool   $check_result     Whether the validation check() method should return true or false
	 * @param   array  $expected_errors  Array of expected error messages from the errors() method
	 * @return  void
	 */
	public function test_validate_set_data($data, $check_result, $expected_errors)
	{
		$Mundo = new Model_Blogpost($data);

		// Valdiate() returns a validation instance
		$validation = $Mundo->validate();

		$this->assertSame($validation->check(), $check_result);

		if ($expected_errors)
		{
			$this->assertSame($expected_errors, $validation->errors(TRUE));
		}
	}

	/**
	 * Validates data that is passed as an argument to the validate method
	 *
	 * @test
	 * @covers Mundo_Object::validate
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param   array  $data             array of model data to set
	 * @param   bool   $check_result     Whether the validation check() method should return true or false
	 * @param   array  $expected_errors  Array of expected error messages from the errors() method
	 * @return  void
	 */
	public function test_validate_array_data($data, $check_result, $expected_errors)
	{
		$Mundo = new Model_Blogpost;

		// Valdiate() returns a validation instance
		$validation = $Mundo->validate($data);

		$this->assertSame($validation->check(), $check_result);

		if ($expected_errors)
		{
			$this->assertSame($expected_errors, $validation->errors(TRUE));
		}
	}

}
