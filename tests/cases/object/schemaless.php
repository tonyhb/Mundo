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
class Mundo_Object_Schemaless_Tests extends PHPUnit_Framework_TestCase {

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

		$model->save();

		$this->assertTrue($model->loaded());
		$this->assertTrue($model->get('_id') instanceof MongoId);
	}

	/**
	 * Ensures you can push to an undefined field
	 *
	 * @test
	 * @return void
	 */
	public function test_pushing_to_undefined_array()
	{
		$model = new Model_Page;

		$model->push('comments', array('comment' => 'one'), array('comment' => 'two'));

		$this->assertEquals(array(
			'comments' => array(
				array('comment' => 'one'),
				array('comment' => 'two'),
			)
		), $model->get());
	}

	/**
	 * Ensures you can unset an undefined field which has already been set
	 *
	 * @test
	 * @return void
	 */
	public function test_unsetting_previously_set_undefined_field()
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

		$model->unset_atomic('author');

		$this->assertEquals(array(
			'name' => 'Page name',
			'slug' => 'page-slug',
		), $model->get());

		$this->assertEquals(array(
			'$set' => array(
				'name' => 'Page name',
				'slug' => 'page-slug',
			),
		), $model->next_update());

		$model->save();

		$this->assertTrue($model->loaded());
		$this->assertTrue($model->get('_id') instanceof MongoId);

		$id = $model->get('_id');

		unset($model);

		$model = new Model_Page;

		$model->set('_id', $id);

		$model->load();

		$this->assertTrue($model->loaded());
	}

	public function test_setting_selectively_extensible_fields()
	{
		$model = new Model_Resource;

		try
		{
			$failed = FALSE;
			$model->set(array(
				"type" => "task",
				"name" => "Rewrite presentation",
				"foo" => "bar",
			));
		}
		catch(Exception $e)
		{
			$failed = TRUE;
			$this->assertEquals("Field 'foo' does not exist", $e->getMessage());
		}

		if ( ! $failed)
		{
			$this->fail('The model should only allow unmapped fields described in $_schemaless');
		}

		$model->set(array(
			"type" => "task",
			"name" => "Rewrite presentation",
			"metadata.author" => "Jane Smith",
			"metadata.location" => "Example",
		));

		$this->assertEquals(array(
			"type" => "task",
			"name" => "Rewrite presentation",
			"metadata" => array(
				"author" => "Jane Smith",
				"location" => "Example",
			),
		), $model->get());

		$model->set("comments", array(
			array(
				"name" => "Laura",
				"text" => "Comment 1",
			),
			array(
				"name" => "Emma",
				"text" => "Comment 2",
				"upboats" => array("Wadsworth", "Sure_Ill_Draw_That")
			)
		));

		$this->assertEquals(array(
			"type" => "task",
			"name" => "Rewrite presentation",
			"metadata" => array(
				"author" => "Jane Smith",
				"location" => "Example",
			),
			"comments" => array(
				array(
					"name" => "Laura",
					"text" => "Comment 1",
				),
				array(
					"name" => "Emma",
					"text" => "Comment 2",
					"upboats" => array("Wadsworth", "Sure_Ill_Draw_That")
				)
			),
		), $model->get());

	}
}
