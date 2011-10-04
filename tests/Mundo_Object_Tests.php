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
	 * Provides test data for test_set_and_get
	 *
	 */
	public static function provider_set_and_get()
	{
		// $data, $expected_error (null if it should succeed), $expected_result (with dot notation)
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
			// Dot notation within normal keys
			array(
				array(
					'post_title' => 'Example blog post',
					'post_metadata.keywords' => 'keyword, another',
					'post_metadata.description' => 'This is a post description keyword tag',
					'author_name' => 'Author name',
				),
				NULL,
				array(
					'post_title' => 'Example blog post',
					'post_metadata' => array(
						'keywords' => 'keyword, another',
						'description' => 'This is a post description keyword tag'
					),
					'author_name' => 'Author name'
				)
			),
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
					'post_title'    => 'Another example blog post',
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
	 * Tests the inc set methods atomicity with the $inc modifier
	 *
	 * @test
	 * @covers Mundo_Object::inc
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::update
	 * @covers Mundo_Object::last_update
	 * @return void
	 */
	public function test_inc_atomicity()
	{
		$document = new Model_Blogpost;

		$document->set('post_title', 4);

		$modifiers = array();

		$modifiers['$inc'] = array(
			'post_title' => 4
		);
			
		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		// Ensure that overwriting a non-saved $inc uses $original data for calculations

		$document->set('post_title', 8);

		$modifiers['$inc'] = array(
			'post_title' => 8
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		// Test overwriting a saved $inc uses saved $original data for calctulations

		$data = array(
			'post_title' => 4,
			'post_slug' => 'post-slug',
			'post_date' => new MongoDate,
			'author' => new MongoId,
			'author_name' => 'Author Name',
			'author_email' => 'email@example.com',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 2',
					'author_name' => 'Comment author',
					'author_email' => 'comment.2@example.com',
				)
			)
		);

		$document->set($data);

		$document->save();

		$document->post_title = 5;

		$modifiers['$inc'] = array(
			'post_title' => 1
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		$document->post_title = 6;

		$modifiers['$inc'] = array(
			'post_title' => 2
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		/**
		 * Test the inc method
		 */
		$data = array(
			'post_title' => 4,
			'post_slug' => 'post-slug',
			'post_date' => new MongoDate,
			'author' => new MongoId,
			'author_name' => 'Author Name',
			'author_email' => 'email@example.com',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 2',
					'author_name' => 'Comment author',
					'author_email' => 'comment.2@example.com',
				)
			)
		);

		$document->set($data);

		$document->save();

		$document->inc('post_title', 5);

		$modifiers['$inc'] = array(
			'post_title' => 5
		);

		$this->assertEquals(
			$modifiers,
			$document->next_update()
		);

		// Inc increments by $value, so ensure it is original + inc
		$this->assertEquals(
			9,
			$document->get('post_title')
		);

		// This increments on top of the new value too
		$document->inc('post_title', 6);

		$modifiers['$inc'] = array(
			'post_title' => 11
		);

		$update = $document->next_update();
		$this->assertEquals(
			$modifiers,
			$update
		);

		// Inc increments by $value, so ensure it is original + inc
		$this->assertEquals(
			15,
			$document->get('post_title')
		);

		/**
		 * Test updating from inc modifier
		 */
		$document->update();

		$this->assertEmpty($document->changed());

		$this->assertEquals(
			array(),
			$document->next_update()
		);

		// Ensure the update method saved the query array
		$this->assertEquals(
			array(
				'$inc' => array(
					'post_title' => 11
				)
			), 
			$document->last_update()
		);

		// Load a copy of the saved document to confirm changes
		$loaded_doc = new Model_Blogpost;
		$loaded_doc->_id = $document->_id;

		$loaded_data = $loaded_doc->load()->get();
		$doc_data = $document->get();

		// Remove IDs because they dont compare
		unset($loaded_data['_id']);
		unset($doc_data['_id']);

		// Ensure the data representing the mongo db is saved
		$data['post_title'] = 15;
		$this->assertEquals(
			$data,
			$doc_data
		);

		$this->assertEquals(
			$loaded_data,
			$doc_data
		);
	}

	/**
	 * Ensures the inc method throws an error when called on a field
	 * that has non-numeric saved data
	 *
	 * @test
	 * @covers Mundo_Object::inc
	 * @return void
	 */
	public function test_inc_throws_error_when_field_is_non_numeric()
	{
		$document = new Model_Blogpost;

		$document->set(array(
			'post_title' => 'Title',
			'post_slug' => 'post-slug',
			'post_date' => new MongoDate,
			'author' => new MongoId,
			'author_name' => 'Author Name',
			'author_email' => 'email@example.com',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 2',
					'author_name' => 'Comment author',
					'author_email' => 'comment.2@example.com',
				)
			)
		));

		$document->save();

		try
		{
			$document->inc('post_title', 5);
		}
		catch(Mundo_Exception $e)
		{
			$this->assertSame($e->getMessage(), "Cannot apply \$inc modifier to non-number in field 'post_title'");
			return;
		}

		$this->fail("The inc() method should have raised an exception when called upon a non-numeric field");
	}



	/**
	 * Ensures the inc method throws an error when called with a
	 * non-numeric value
	 *
	 * @test
	 * @covers Mundo_Object::inc
	 * @return void
	 */
	public function test_inc_throws_error_when_value_is_non_numeric()
	{
		$document = new Model_Blogpost;

		$document->set(array(
			'post_title' => 5,
			'post_slug' => 'post-slug',
			'post_date' => new MongoDate,
			'author' => new MongoId,
			'author_name' => 'Author Name',
			'author_email' => 'email@example.com',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 2',
					'author_name' => 'Comment author',
					'author_email' => 'comment.2@example.com',
				)
			)
		));

		$document->save();

		try
		{
			$document->inc('post_title', 'hello');
		}
		catch(Mundo_Exception $e)
		{
			$this->assertSame($e->getMessage(), "Cannot apply \$inc modifier with non-numeric values in field 'post_title'");
			return;
		}

		$this->fail("The inc() method should have raised an exception when called upon a non-numeric field");
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
	 * Ensures Mundo_Object throws an error when trying to access
	 * an atomic operation key in _next_update that doesn't exist
	 *
	 * @test
	 * @covers Mundo_Object::next_update
	 * @expectedException Mundo_exception
	 * @expectedexceptionmessage The atomic operation '$nonExistantatomic' does not exist
	 * @return void
	 */
	public function test_incorrect_atomic_operators_with_next_update()
	{
		$doc = new Model_Blogpost;
		$doc->next_update('$nonExistantAtomic');
	}

	public function provider_push()
	{
		// $model_data, $multiple_push_arrays, $push_data, $expected_result, $atomic_operation, $save
		return array(
			array(
				array(
					'post_title' => 'Title',
					'post_slug' => 'post-slug',
					'post_date' => new MongoDate,
					'author' => new MongoId,
					'author_name' => 'Author Name',
					'author_email' => 'email@example.com',
					'post_content' => 'Content',
					'post_metadata' => array(
						'keywords' => 'keyword one',
						'description' => 'keyword two',
					),
					'comments' => array(
						array(
							'comment' => 'Comment 2',
							'author_name' => 'Comment author',
							'author_email' => 'comment.2@example.com',
						)
					)
				),
				FALSE,
				array(
					'comment' => 'Comment 3'
				),
				array(
					array(
						'comment' => 'Comment 2',
						'author_name' => 'Comment author',
						'author_email' => 'comment.2@example.com',
					),
					array(
						'comment' => 'Comment 3'
					),
				),
				array(
					'comments' => array(
						array(
							'comment' => 'Comment 3'
						),
					),
				),
				TRUE
			),
			array(
				array(
					'post_title' => 'Title',
					'post_content' => 'Content',
					'post_metadata' => array(
						'keywords' => 'keyword one',
						'description' => 'keyword two',
					),
					'comments' => array(
						array(
							'comment' => 'Comment 1',
						)
					)
				),
				TRUE,
				array(
					array(
						'comment' => 'Comment 2'
					),
					array(
						'comment' => 'Comment 3',
						'author'  => '3rd comment author',
					)
				),
				array(
					array(
						'comment' => 'Comment 1',
					),
					array(
						'comment' => 'Comment 2'
					),
					array(
						'comment' => 'Comment 3',
						'author'  => '3rd comment author',
					)
				),
				array(
					'comments' => array(
						array(
							'comment' => 'Comment 2'
						),
						array(
							'comment' => 'Comment 3',
							'author'  => '3rd comment author',
						)
					),
				),
			),
		);
	}

	/**
	 * Tests the push() method, which replaces array_push on model data
	 * These tests modify the comments field.
	 *
	 * @test
	 * @covers Mundo_Object::push
	 * @covers Mundo_Object::_check_field_exists
	 * @covers Mundo_Object::next_update
	 * @dataProvider provider_push
	 *
	 * @param $data initial data
	 * @param $push data to push
	 * @returns void
	 */
	public function test_push_with_unsaved_data($data, $multiple_push_arrays, $push_data, $expected_result, $atomic_operation)
	{
		// Duplicate data so we can run multiple pushes twice (the data is modified by array_push)
		$var_data = $data;

		// Initialise our model
		$document = new Model_Blogpost($data);

		if ($multiple_push_arrays)
		{
			// Call push with multiple arrays
			$doc_count = call_user_func_array(array($document, "push"), array_merge(array('comments'), $push_data));
			$var_count = call_user_func_array('array_push', array_merge(array(&$var_data['comments']), $push_data));
		}
		else
		{
			// Add data to the array
			$doc_count = $document->push('comments', $push_data);
			$var_count = array_push($var_data['comments'], $push_data);
		}

		// Ensure pushing the array had the expected results
		$this->assertEquals($expected_result, $document->get('comments'));

		// The model's push should work the same as the normal function
		$this->assertEquals($var_data['comments'], $document->get('comments'));
		$this->assertEquals($doc_count, $var_count);

		// Ensure that the atomic query for this change was written
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));

		// If there are multiple push arrays try setting them one at a time now.
		if ( ! $multiple_push_arrays)
			return;

		$var_data = $data;

		$document = new Model_Blogpost($data);

		foreach($push_data as $push)
		{
			$doc_count = $document->push('comments', $push);
			$var_count = array_push($var_data['comments'], $push);

			// Make sure everything's OK each time
			$this->assertEquals($doc_count, $var_count);
			$this->assertEquals($var_data['comments'], $document->get('comments'));
		}

		$this->assertEquals($expected_result, $document->get('comments'));
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));
	}

	static $empty_update = array(
		'$pushAll' => array(), // This takes care of $push
		'$pullAll' => array(), // This takes care of $pull
		'$addToSet' => array(),
		'$pop' => array(),
		'$bit' => array(),
		'$inc' => array(),
		'$set' => array(),
		'$unset' => array(),
	);

	protected function reset_update()
	{
		self::$empty_update = array(
			'$pushAll' => array(), // This takes care of $push
			'$pullAll' => array(), // This takes care of $pull
			'$addToSet' => array(),
			'$pop' => array(),
			'$bit' => array(),
			'$inc' => array(),
			'$set' => array(),
			'$unset' => array(),
		);
	}

	/**
	 * Tests the push() method, which replaces array_push on model data
	 * These tests modify the comments field.
	 *
	 * @test
	 * @covers Mundo_Object::push
	 * @covers Mundo_Object::_check_field_exists
	 * @covers Mundo_Object::next_update
	 * @covers Mundo_Object::_reset_update
	 * @dataProvider provider_push
	 *
	 * @param $data initial data
	 * @param $push data to push
	 * @returns void
	 */
	public function test_push_with_saved_data($data, $multiple_push_arrays, $push_data, $expected_result, $atomic_operation, $save = FALSE)
	{
		if ( ! $save)
			return;

		// Duplicate data so we can run multiple pushes twice (the data is modified by array_push)
		$var_data = $data;

		// Initialise our model
		$document = new Model_Blogpost($data);

		if ($document->next_update() == array())
		{
			$this->fail('The update array should have been updated when setting $data');
			return;
		}

		$document->save();

		// Ensure saving resets our update array
		$this->assertEquals($document->next_update(), array());

		if ($multiple_push_arrays)
		{
			// Call push with multiple arrays
			$doc_count = call_user_func_array(array($document, "push"), array_merge(array('comments'), $push_data));
			$var_count = call_user_func_array('array_push', array_merge(array(&$var_data['comments']), $push_data));
		}
		else
		{
			// Add data to the array
			$doc_count = $document->push('comments', $push_data);
			$var_count = array_push($var_data['comments'], $push_data);
		}

		// Ensure pushing the array had the expected results
		$this->assertEquals($expected_result, $document->get('comments'));

		// The model's push should work the same as the normal function
		$this->assertEquals($var_data['comments'], $document->get('comments'));
		$this->assertEquals($doc_count, $var_count);

		// Ensure that the atomic query for this change was written
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));

		// If there are multiple push arrays try setting them one at a time now.
		if ( ! $multiple_push_arrays)
			return;

		$var_data = $data;

		$document = new Model_Blogpost($data);

		foreach($push_data as $push)
		{
			$doc_count = $document->push('comments', $push);
			$var_count = array_push($var_data['comments'], $push);

			// Make sure everything's OK each time
			$this->assertEquals($doc_count, $var_count);
			$this->assertEquals($var_data['comments'], $document->get('comments'));
		}

		$this->assertEquals($expected_result, $document->get('comments'));
		$this->assertEquals($atomic_operation, $document->next_update('$pushAll'));

		/**
		 * @todo SAVE
		 */
	}

	/**
	 *
	 * @expectedException Mundo_Exception
	 * @expectedExceptionMessage Field 'foo' does not exist
	 *
	 * @return void
	 */
	public function test_pushing_invalid_field_throws_error()
	{
		$document = new Model_Blogpost();
		$document->push('foo', array('bar' => FALSE));
	}

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


	/**
	 * Tests unset's atomicity
	 * @test
	 * @covers Mundo_Object::set
	 * @covers Mundo_Object::__unset
	 * @covers Mundo_Object::unset_atomic
	 * @covers Mundo_Object::next_update
	 * @covers Mundo_Object::_reset_update
	 *
	 * @param $data initial data
	 * @param $push data to push
	 * @returns void
	 */
	public function test_unset_atomicity()
	{
		/**
		 * Basic set method testing. This tests:
		 *   unset_atomic, __unset and calling unset_atomic from within set/__set
		 *
		 */
		$document = new Model_Blogpost();
		$document->post_title = 'Post title';

		$this->assertEquals(
			$document->next_update(),
			array(
				'$set' => array(
					'post_title' => 'Post title',
				),
			)
		);

		$this->assertEmpty($document->original());

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => 'Post title',
			)
		);

		$document->unset_atomic('post_title');

		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => NULL,
			)
		);
		// Test 1 (unset_atomic) domplete

		$document->post_title = 'Post title';

		unset($document->post_title);
		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => NULL,
			)
		);

		$document->post_title = 'Post title';

		// This should call unset in the set method
		$document->post_title = NULL;

		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$this->assertEquals(
			$document->changed(),
			array(
				'post_title' => NULL,
			)
		);

		/**
		 * Test how unset works with unsaved embedded collections
		 */

		$document->push(
			'comments',
			array(
				'comment' => 'comment text',
				'comment_author' => 'comment author',
			)
		);

		$document->unset_atomic('comments.0.comment');

		$this->assertEquals(
			$document->next_update(),
			array(
				'$pushAll' => array(
					'comments' => array(
						array(
							'comment_author' => 'comment author'
						)
					),
				),
			)
		);

		/**
		 * Test how unset works with saved data
		 */

		$document->set(array(
			'post_title' => 'Title',
			'post_slug' => 'post-slug',
			'post_date' => new MongoDate,
			'author' => new MongoId,
			'author_name' => 'Author Name',
			'author_email' => 'email@example.com',
			'post_content' => 'Content',
			'post_metadata' => array(
				'keywords' => 'keyword one',
				'description' => 'keyword two',
			),
			'comments' => array(
				array(
					'comment' => 'Comment 2',
					'author_name' => 'Comment author',
					'author_email' => 'comment.2@example.com',
				)
			)
		));

		$document->save();

		$this->assertEquals(
			$document->next_update(),
			array()
		);

		$document->unset_atomic('post_title');

		$this->assertNull($document->get('post_title'));
		$this->assertNull($document->changed('post_title'));

		$this->assertEquals(
			$document->next_update(),
			array(
				'$unset' => array(
					'post_title' => 1
				),
			)	
		);

		/**
		 * Test that re-setting data removes the $unset modifier
		 */

		$document->post_title = 'Post title';

		$this->assertEquals(
			array(
				'$set' => array(
					'post_title' => 'Post title',
				),
			),	
			$document->next_update()
		);
	}

	/**
	 * Provider for the save and update methods
	 */
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
							'author_name'  => 'Dr. Smith',
							'author_url'   => 'http://example-commenter.com/',
							'author_email' => 'my.new.email@example.com',
							'likes'        => array('Joe Bloggs', 'Ted Smith'),
						),
					),
				),
				// Expected query
				array(
					'$set' => array(
						'post_title' => 'New post title',
						'post_date' => new MongoDate(strtotime("26th March 2011, 11:24AM")),
						'post_metadata.keywords' => 'new keywords, updated object',
						'comment.1.author_name' => 'Dr. Smith',
						'comment.1.author_email' => 'my.new.email@example.com',
						'comment.1.likes' => array('Joe Bloggs', 'Ted Smith')
					),
					'$unset' => array('post_excerpt'),
				)
			),
		);
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
	 * @Todo Test saving with no changed data does nothing
	 */

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
	 * Test that the update function creates the correct atomic operation
	 * queries for updating data
	 *
	 * @test
	 * @covers Mundo_Object::update
	 * @dataProvider provider_update
	 *
	 * @param  array  array of model data to set and load
	 * @param  array  array of model data to change
	 * @param  array  expected atomic operation query
	 * @return void
	 */
	public function test_update_updates_atomically($data, $changed_data, $expected_atomic_operation)
	{
		/*
		$document = new Model_Blogpost($data);

		// Update requires a loaded document.
		$document->load();

		// Sanity check
		if ( ! $document->loaded())
		{
			$this->fail("A document could not be loaded");
		}

		// Replace our $data variable with the full document in the DB (add the _id field)
		$data = $document->get();

		// Change our data
		$document->set($changed_data);

		$document->update();

		// Merge our data to compare the new representation with what it should be
		$merged_data = array_merge(Mundo::flatten($data), Mundo::flatten($changed_data));
		$merged_data = Mundo::inflate($merged_data);

		// Test that the update was as atomic as we expected
		$this->assertEquals($document->last_update(), $expected_atomic_operation);

		// Test that the data is now saved
		$this->assertEquals($document->get(), $merged_data);

		// Test that changed is now empty
		$this->assertEmpty($document->changed());

		// Sanity check: reload the model in a new object and compare data to ensure it went to the database A-OK
		$reloaded_document = new Model_Blogpost();
		$reloaded_document->set('_id', $document->get('_id'))->load();

		// If the query was successful these should be the same
		$this->assertEquals($document->get(), $reloaded_document->get());
		 */
	}


	/**
	 * @todo Test update() fails with invalid data
	 */

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
	 * @todo Test atomic operations using a query passed as an argument
	 *       on loaded and unloaded models (maybe?)
	 */
}