<?php

/**
 * Ensures that we can extend models by adding data to fields that have not
 * been initialised in the $_fields variable.
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Extensibility_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Remove any test databases.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		// Remove our testing database before writing tests.
		$mongo = new Mongo;

		$config = Kohana::$config->load("mundo");

		// Select our database
		$db = $mongo->{$config->database};

		// Drop it like it's hot.
		$db->drop();
	}

	/**
	 * Remove our test database we played witj
	 *
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
		// Repeat our drop function
		self::setUpBeforeClass();
	}

	/**
	 * Ensures we can set data to a field that doesn't exist
	 *
	 * @test
	 * @return void
	 */
	public function test_setting_undefined_fields()
	{
		$model = new Model_Page;

		$model->set(array(
			'name' => 'Page name',
			'slug' => 'page-slug',
			'author' => 'John Smith',
		));

		$this->assertEquals(array(
			'name' => 'Page name',
			'slug' => 'page-slug',
			'author' => 'John Smith',
		), $model->get());

		$this->assertEquals(array(
			'$set' => array(
				'name' => 'Page name',
				'slug' => 'page-slug',
				'author' => 'John Smith'
			),
		), $model->next_update());
	}
}
