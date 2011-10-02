<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Example Mundo model used for our tests
 *
 * @package Mundo
 * @category Tests
 * @author Tony Holdstock-Brown
 **/
class Model_Page extends Mundo_Object
{

	/**
	 * This is the name of the collection we're saving to in MongoDB.
	 *
	 * !! Note: This string becomes a MongoCollection instance once _init_db()
	 *          has been called (see Mundo_Core for this method).
	 *
	 * @var string
	 */
	protected $_collection = 'pages';

	protected $_extendable = TRUE;

	/**
	 * These are the fields, or key names, that we store in our collection.
	 * We denote embedded objects or arrays by using the '$' character.
	 *
	 * For more information on MongoDB/NoSQL Schema Design see the following:
	 * [http://www.mongodb.org/display/DOCS/Schema+Design]
	 *
	 * @var array
	 */
	protected $_fields = array(
		'_id', // This field must always be specified as it (almost) always exists.
		'name',
		'slug',
	);

	/**
	 * This declares validation rules for each $_field item. The syntax is 
	 * almost identical to that of the Validation library, except we allow
	 * for embedded arrays.
	 *
	 * @see Kohana::Validation
	 *
	 * @var array
	 */
	protected $_rules = array(
		// You do NOT need to specify that the '_id' field is an instance of MongoID.

		'name' => array( // This array lists the rules that apply to the post_title field
			array('not_empty'), // These methods are set in the Valid class, though you can use the same syntax as the Validation library to call your own functions.
		),
		'slug' => array(
			array('alpha_dash', array(':value', TRUE)), // The first array value is the method name, the second is an array of paramters.
			array('not_empty'),
		),
	);

} // End Blogpost
