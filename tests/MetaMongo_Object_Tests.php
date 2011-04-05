<?php

/**
 * Tests the MetaMongo_Object class, which
 *
 * @package MetaMongo
 * @subpackage MetaMongo_Object
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class MetaMongo_Object_Tests extends PHPUnit_Framework_TestCase {

	/**
	 * Remove any test databases.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		// Remove our testing database before writing tests.
        $mongo = new Mongo;

		$config = Kohana::config('metamongo');

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
	public static function set_and_get()
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
				// MetaMongo should throw an error saying our field doesn't exist
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
				// MetaMongo should throw an error saying our field doesn't exist
				"Field 'comments.0.embedded' does not exist", 
			),
		);
	}

	/**
	 * Tests that the set and get functions work as expected
	 *
	 *
	 * @test
	 * @covers MetaMongo_Object::set
	 * @covers MetaMongo_Object::_set
	 * @covers MetaMongo_Object::get
	 * @covers MetaMongo_Object::changed
	 * @dataProvider set_and_get
	 * @param  array  $data             The array of data to set
	 * @param  mixed  $expected_error   Null if setting should pass, otherwise the error exception message.
	 * @param  array  $expected_result  The expected result of get() if it isn't the same as $data.
	 *
	 */
	public function test_set_and_get($data, $expected_error, $expected_result = NULL)
	{

		$metamongo = new Model_Blogpost;

		if ($expected_error)
		{
			try
			{
				// Setting our data should fail
				$metamongo->set($data);
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
			$metamongo->set($data);

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($metamongo->get(), $expected_result);
				$this->assertSame($metamongo->changed(), $expected_result);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($metamongo->get(), $data);
				$this->assertSame($metamongo->changed(), $data);
			}
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
	 * @covers MetaMongo_Object::get
	 * @covers MetaMongo_Object::set
	 * @covers MetaMongo_Object::_set
	 * @covers MetaMongo_Object::changed
	 * @dataProvider provider_single_set_and_get
	 * @param  string  $field            Name of field we are setting
	 * @param  string  $value            Value of the field
	 * @param  string  $expected_error   Expected error message, if any
	 * @param  string  $expected_result  Expected result, if different from array($field => $value)
	 */
	public function test_single_set_and_get($field, $value, $expected_error, $expected_result = NULL)
	{
		$metamongo = new Model_Blogpost;

		if ($expected_error)
		{
			try
			{
				// Setting our data should fail
				$metamongo->set($field, $value);
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
			$metamongo->set($field, $value);

			if ($expected_result)
			{
				// Ensure the data is the same as the expected result
				$this->assertSame($metamongo->get($field), $value);
				$this->assertSame($metamongo->get(), $expected_result);
				$this->assertSame($metamongo->changed($field), $value);
			}
			else
			{
				// Ensure the data is the same as we put in.
				$this->assertSame($metamongo->get($field), $value);
				$this->assertSame($metamongo->get(), array($field => $value));
				$this->assertSame($metamongo->changed(), array($field => $value));
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
	 * @covers MetaMongo_Object::validate
	 * @covers MetaMongo_Object::_extract_rules
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param   array  $data             array of model data to set
	 * @param   bool   $check_result     Whether the validation check() method should return true or false
	 * @param   array  $expected_errors  Array of expected error messages from the errors() method
	 * @return  void
	 */
	public function test_validate_set_data($data, $check_result, $expected_errors)
	{
		$metamongo = new Model_Blogpost($data);

		// Valdiate() returns a validation instance
		$validation = $metamongo->validate();

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
	 * @covers MetaMongo_Object::validate
	 * @dataProvider provider_validate_and_create_data
	 *
	 * @param   array  $data             array of model data to set
	 * @param   bool   $check_result     Whether the validation check() method should return true or false
	 * @param   array  $expected_errors  Array of expected error messages from the errors() method
	 * @return  void
	 */
	public function test_validate_array_data($data, $check_result, $expected_errors)
	{
		$metamongo = new Model_Blogpost;

		// Valdiate() returns a validation instance
		$validation = $metamongo->validate($data);

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
	 * @covers MetaMongo_Object::create
	 * @covers MetaMongo_Object::loaded
	 * @covers MetaMongo_Object::changed
	 * @covers MetaMongo_Object::original
	 * @covers MetaMongo_Object::_init_db
	 * @covers MetaMongo_Object::load
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
			catch(MetaMongo_Exception $e)
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
	 * Tests how the load() method handles loading with no data
	 *
	 * @covers MetaMongo_Object::load
	 * @expectedException MetaMongo_Exception
	 * @expectedExceptionMessage No model data supplied
	 * @return void
	 */
	public function test_document_loading_with_no_data()
	{
		$document = new Model_Blogpost;
		$document->load();
	}
}