<?php

/**
 * Tests the Mundo_Object class, which
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class Mundo_Object_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Remove any test databases.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		// Remove our testing database before writing tests.
        $mongo = new Mongo;

		$config = Kohana::config('Mundo');

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
	 * Provides test data for test_set_and_get
	 *
	 */
	public static function provider_set_and_get()
	{
		// $data, $expected_error (null if it should succeed)
		return array(
			// A full dataset
			array(
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'commenter.brown@example.com',
						),
					),
				),
				// This should work correctly
				NULL,
			),
			// Only the "likes" array of one comment.
			array(
				array(
					'comments' => array(
						array(
							'likes' => array('Joe Bloggs', 'Ted Smith'),
						),
					),
				),
				// This should work correctly
				NULL,
			),
			// Two fields being set using dot path notation
			array(
				array(
					'post_metadata.keywords' => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
					'post_metadata.description' => 'An example description tag for a blog post. Google SERP me plox!',
				),
				// This should work correctly
				NULL,
				array(
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
				)
			),
			// Dot notation on an embedded object
			array(
				array(
					'comments.0.comment'      => 'Comment number 1',
					'comments.0.author_name'  => 'Commenter Smith',
					'comments.0.author_url'   => 'http://example-commenter.com/',
					'comments.0.author_email' => 'commenter.smith@example.com',
					'comments.0.likes'        => array('Joe Bloggs', 'Ted Smith'),
					'comments.1.comment'      => 'Comment number 2',
					'comments.1.author_name'  => 'Commenter Brown',
					'comments.1.author_email' => 'commenter.brown@example.com',
					'comments.1.likes.0'      => 'First like',
					'comments.1.likes.1'      => 'Second like',
				),
				NULL,
				array(
					'comments' => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'commenter.brown@example.com',
							'likes'        => array('First like', 'Second like'),
						),
					),
				)
			),
			// Data with an undefined field which should fail.
			array(
				array(
					'post_title' => 'Example blog post',
					'post_slug'  => 'example-blog-post',
					'post_date'  => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'     => new MongoId('4d965966ef966f0916000000'),
					'undefined'  => 'Undefined field which should cause setting to fail.',
				),
				// Mundo should throw an error saying our field doesn't exist
				"Field 'undefined' does not exist", 
			),
			// Data with an undefined field in an embedded object
			array(
				array(
					'post_title' => 'Example blog post',
					'post_slug'  => 'example-blog-post',
					'post_date'  => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'     => new MongoId('4d965966ef966f0916000000'),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
							'embedded'    => 'An undefined field in an embedded object',
						),
					),
				),
				// Mundo should throw an error saying our field doesn't exist
				"Field 'comments.0.embedded' does not exist", 
			),
		);
	}

	/**
	 * Tests that the set and get functions work as expected
	 *
	 *
	 * @test
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::_set
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::changed
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
		}
	}

	/**
	 * Tests that overloading properties works the same as setting and getting single fields
	 *
	 * @test
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::_set
	 * @covers Mundo_Object::__set
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::__get
	 * @covers Mundo_Object::__isset
	 * @covers Mundo_Object::changed
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
	 * Test setting and getting single fields at once works as expected
	 *
	 * @test
	 * @covers Mundo_Object::get
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::_set
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
	 * Provides data for test_validate_set_data, test_validate_array_data
	 * test_create_document
	 *
	 * @return array
	 */
	public static function provider_validate_and_create_data()
	{
		return array(
			// $field_data, $check_result, $expected_errors
			array(
				// Test explicitly setting an ObjectId
				array(
					'_id'           => new MongoId('4d9b16c8ef966fff00000006'),
					'post_title'    => 'Blog post inserted from ID',
					'post_slug'     => 'blog-post-from-id',
					'post_date'     => new MongoDate(strtotime("4th March  2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => 'An excerpt from a blog post added using an explicit ID',
					'post_content'  => 'Blog post content here.',
					'post_metadata' => array(
						'keywords'    => 'specific id, mongoid',
						'description' => 'Description tag here',
					)
				),
				TRUE,
				NULL
			),
			array(
				// Complete 
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'commenter.brown@example.com',
						),
					),
				),
				TRUE,
				NULL
			),
			array(
				// Without required object (post_metadata)
				array(
					'post_title'    => 'Blog post inserted from ID',
					'post_slug'     => 'blog-post-from-id',
					'post_date'     => new MongoDate(strtotime("4th March  2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => 'An excerpt from a blog post added using an explicit ID',
					'post_content'  => 'Blog post content here.',
				),
				FALSE,
				array(
					'post_metadata.keywords' => 'post metadata keywords must not be empty',
					'post_metadata.description' => 'post metadata description must not be empty',
				)
			),
			array(
				// Incorrect post slug (first dimension error)
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post !',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'commenter.brown@example.com',
						),
					),
				),
				FALSE,
				array(
					'post_slug' => 'post slug must contain only numbers, letters and dashes',
				),
			),
			array(
				// Invalid embedded object email
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'incorrect.email@example',
						),
					),
				),
				FALSE,
				array(
					"comments.1.author_email" => "comments author email must be a email address",
				)
			),
			array(
				// Missing required field in an embedded object
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'incorrect.email@example.com',
						),
					),
				),
				FALSE,
				array(
					"comments.0.author_name" => "comments author name must not be empty"
				)
			),
		);
	}

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
	 * @covers Mundo_Object::_init_db
	 * @covers Mundo_Object::load
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param  array   $data   Data to add to the DB
	 * @param  string  $validation_status   Whether $data should pass validation checks
	 * @param  string  $expected_validation_errors    Expected validation error messages
	 * @return void
	 */
	public function test_create_and_load_document($data, $validation_status, $expected_validation_errors = NULL)
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
			
			// Test loading from our ID
			$loaded_object = new Model_Blogpost;
			$loaded_object->set('_id', $document->original('_id'));
			$loaded_object->load();

			$this->assertEquals($loaded_object->get(), $document->original());
			
			// Test loading from non-unique keys.
			$loaded_object = new Model_Blogpost($data);
			$loaded_object->load();
			$this->assertEquals($loaded_object->get(), $document->original());	

			// Ensure we can't run create() when we already have an ObjectId in our data			
			$document = new Model_Blogpost($saved_data);

			try
			{
				$document->create();
			}
			catch(Mundo_Exception $e)
			{
				$this->assertSame($e->getMessage(), "Creating failed: a document with ObjectId '".$document->get('_id')."' exists already.");

				// Assert we're not loaded
				$this->assertFalse($document->loaded());
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

		$document->load();

		// Ensure we've got loaded data
		$this->assertTrue($document->loaded());
		$this->assertInstanceOf('MongoId', $document->get('_id'));
		$this->assertEmpty($document->changed());

		foreach($data as $field => $value)
		{
			// Unset our already saved data
			unset($document->$field);

			$this->assertNull($document->changed($field));
			$this->assertEquals($document->original($field), $data[$field]);
		}

		// Ensure that our array keys for the unset fields exist in changed
		$changed_keys = array_keys($document->changed());
		$data_keys = array_keys($data);

		$this->assertEquals($changed_keys, $data_keys);
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

	/**
	 * Tests how the load() method handles loading with no data
	 *
	 * @covers Mundo_Object::load
	 * @expectedException Mundo_Exception
	 * @expectedExceptionMessage No model data supplied
	 * @return void
	 */
	public function test_document_loading_with_no_data()
	{
		$document = new Model_Blogpost;
		$document->load();
	}

	public function provider_update()
	{
		return array(
			array(
				// Complete data to load, copied from provider_validate_and_create_data
				array(
					'post_title'    => 'Example blog post',
					'post_slug'     => 'example-blog-post',
					'post_date'     => new MongoDate(strtotime("2nd February 2011, 2:56PM")),
					'author'        => new MongoId('4d965966ef966f0916000000'),
					'author_name'   => 'Author Jones',
					'author_email'  => 'author@example.com',
					'post_excerpt'  => '...An excerpt from the post. Boom!',
					'post_content'  => 'This is the whole post. And this should be an excerpt from the bost. Boom! // End of blog post 1.',
					'post_metadata' => array(
						'keywords'    => 'mongodb, mongo, php, php mongo orm, php mongodb orm, sexiness',
						'description' => 'An example description tag for a blog post. Google SERP me plox!',
					),
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
						array(
							'comment'      => 'Comment number 2',
							'author_name'  => 'Commenter Brown',
							'author_email' => 'commenter.brown@example.com',
						),
					),
				),
				// What we're changing data to
				array(
					'post_title'    => 'New post title',
					'post_date'     => new MongoDate(strtotime("26th March 2011, 11:24AM")),
					'post_metadata' => array(
						'keywords' => 'new keywords, updated object',
					),
					// mimic __unset
					'post_excerpt'  => NULL,
					// Remove one embedded object
					'comments'      => array(
						array(
							'comment'      => 'Comment number 1',
							'author_name'  => 'Commenter Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'commenter.smith@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
					),
				),
				// Expected query: TODO
				TRUE,
				NULL
			),
		);
	}

	
	/**
	 * Ensures that running the update method on an object that hasn't been
	 * loaded from the database fails
	 *
	 * @test
	 * @covers Mundo_Object::update
	 * @expectedException Mundo_Exception
	 * @expectedExceptionMessage Cannot update the document because the model has not yet been loaded
	 * @return void
	 */
	public function test_updating_unloaded_object_fails()
	{
		$document = new Model_Blogpost;

		$document->set('post_title', 'This is the post title');

		$document->update();
	}

	/**
	 * Ensures that the document's representation in the database is updated
	 * correctly and that the update function uses the most appropriate
	 * atomic operations to update the data
	 *
	 * @test
	 * @covers Mundo_Object::update
	 * @covers Mundo_Object::last_query
	 * @dataProvider provider_update
	 * @return void
	 */
	public function test_updating_document_with_valid_data($data, $changed_data, $expected_query)
	{
		$document = new Model_Blogpost;

		// Set our data and load the correct document from the database
		$document->set($data)->load();

		// Basic sanity checking without asserts
		if ( ! $document->loaded())
		{
			$this->fail("A document could not be loaded");
		}
		if ( ! empty($document->changed()))
		{
			$this->fail("The document was loaded but the _changed variable not emptied");
		}

		// Merge our data
		$merged_data = array_merge(Mundo::flatten($data), Mundo::flatten($changed_data));
		$merged_data = Mundo::inflate($merged_data);

		// Update our values in the model
		$document->set($changed_data)->update();

		// Ensure our data has been saved successfully
		$this->assertEquals($document->original(), $merged_data);
		$this->assertEmpty($document->changed());

		// Ensure that we used correct atomic operations, baby
		$this->assertEquals($document->last_query(), $expected_query);
	}

	/**
	 * @todo Test updating with invalid data
	 **/
}