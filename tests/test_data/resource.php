<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Example Mundo model used for our tests.
 *
 * This class shows how to use schema-less mapping in an embedded object.
 *
 * Because field definitions ending in the positional operator `$` denote
 * only an array of values (ie. array('one', 'two', ...)) they cannot accept
 * multi-dimensional arrays, or embedded objects.
 *
 * Setting the field in $_extensible bypasses this issue, making the main
 * collection non-extensible whilst the embedded object can have any data
 * inside it whatsoever.
 *
 * @package Mundo
 * @category Tests
 * @author Tony Holdstock-Brown
 **/
class Model_Resource extends Mundo_Object
{

	/**
	 * This is the name of the collection we're saving to in MongoDB.
	 *
	 * !! Note: This string becomes a MongoCollection instance once _init_db()
	 *          has been called (see Mundo_Core for this method).
	 *
	 * @var string
	 */
	protected $_collection = 'resources';

	/**
	 * Allow embedded objects within contnet which have unmapped data 
	 * structures
	 *
	 * @var mixed Boolean for the status of the whole object, or an array 
	 *            of extensible fields
	 */
	protected $_extensible = array(
		'content.$',
	);

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
		'_id',
		'type',
		'name',
		'content.$',
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
