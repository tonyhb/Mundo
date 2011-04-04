<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Example MetaMongo model used for our tests
 *
 * @package MetaMongo
 * @category Tests
 * @author Tony Holdstock-Brown
 **/
class Model_Blogpost extends MetaMongo_Object
{

	/**
	 * This is the name of the collection we're saving to in MongoDB.
	 *
	 * !! Note: This string becomes a MongoCollection instance once _init_db()
	 *          has been called (see MetaMongo_Core for this method).
	 *
	 * @var string
	 */
	protected $_collection = 'posts';

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
		'post_title',
		'post_slug',
		'post_date',
		'author', // A reference to another collection's ObjectID
		'author_name', // Denormalisation is a good thing with NoSQL (in general).
		'author_email', 
		'post_excerpt',
		'post_content',
		'post_metadata' => array(
			// The single array denotes a single embedded object.
			'keywords',
			'description',
		),
		'comments' => array(
			// The following '$' character as an array key tells MetaMongo we can have many of the following objects.
			'$' => array(
				'comment',
				'author_name',
				'author_url',
				'author_email',
				'likes' => array(
					'$', // A single '$' character as a value (NOT a key) tells MetaMongo that we can have a flat array of any length (in this case the name of people that 'liked' the comment.)
				),
			),
		),
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

		'post_title' => array( // This array lists the rules that apply to the post_title field
			array('not_empty'), // These methods are set in the Valid class, though you can use the same syntax as the Validation library to call your own functions.
		),
		'post_slug' => array(
			array('alpha_dash', array(':value', TRUE)), // The first array value is the method name, the second is an array of paramters.
			array('not_empty'),
		),
		'post_date' => array(
			array('MetaMongo::instance_of', array(':value', 'MongoDate')), // MetaMongo comes with an instanceof static method to ensure we insert correct Mongo classes.
			array('not_empty'),
		),
		'author' => array(
			array('MetaMongo::instance_of', array(':value', 'MongoDate')),
			array('not_empty'),
		),
		'author_name' => array(
			array('alpha', array(':value', true)),
		),
		'author_email' => array(
			array('not_empty'),
			array('email'),
		),
		'post_content' => array(
			array('not_empty'),
		),
		'comments' => array(
			// Reference each embedded object
			'$' => array(
				// Each embedded object is as standard from here on out
				'comment_author' => array(
					array('not_empty'),
				),
				'comment_email' => array(
					array('not_empty'),
					array('email'),
				),
				'likes' => array(
					'$' => array(
						// Note we don't have field names and jump straight into the rules: this is a flat array
						array('alpha', array(':value', true)),
					)
				)
			)
		),
		'post_metadata'  => array(
			'keywords' => array(
				array('not_empty'),
			),
			'description' => array(
				array('not_empty'),
			),
		),
	);

	/**
	 * Filters are applied to fields as they are set.
	 *
	 * !! Note: This is a destructive method and automatically changes the data 
	 *          being set.
	 *
	 * @var array
	 */
	protected $_filters = array(
		'post_slug' => array(
			array('inflector::underscore'), // Ensure there's no spaces in our slug
		),
		'post_date' => array(
			array('MetaMongo::date') // Convert our date to a MongoDate object using MetaMongo's static date method
		),
	);

} // End Blogpost