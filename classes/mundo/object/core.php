<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Core functions for the Mundo objects, including data manipulation,
 * database updates and database searching.
 *
 * @package Mundo
 * @subpackage Mundo_Object
 * @author Tony Holdstock-Brown
 **/
class Mundo_Object_Core
{

	/**
	 * This is the name of the collection we're saving to in MongoDB.
	 *
	 * !! Note: This string becomes a MongoCollection instance once _init_db()
	 *          has been called (see Mundo_Core for this method).
	 *
	 * @var string
	 */
	protected $_collection;

	/**
	 * The name of the fields in our collection.
	 *
	 * @var array
	 */
	protected $_fields;

	/**
	 * Validation rules to run against data. The validation rules are ran when
	 * we save/update the collection or when we call the validate() method.
	 *
	 * @var array
	 */
	protected $_rules;

	/**
	 * An array of filters which are ran when setting data.
	 *
	 * @var array
	 */
	protected $_filters;

	/**
	 * Our Mongo class
	 *
	 * @var  Mongo
	 * @see  http://www.php.net/manual/en/class.mongo.php
	 */
	protected $_mongo;

	/**
	 * Our MongoDB class
	 *
	 * @var  MongoDB
	 * @see  http://www.php.net/manual/en/class.mongodb.php
	 */
	protected $_db;

	/**
	 * The "safe" parameter for DB queries
	 *
	 * @var  mixed
	 */
	protected $_safe;

	/**
	 * Initialise our data
	 *
	 * @param array $data 
	 */
	public function __construct($data = array())
	{
		if ( ! is_array($data))
		{
			// Only accept data in an array
			throw new Mundo_Exception("Arrays are the only accepted arguments when constructing Mundo Models");
		}

		// Set our data
		$this->set($data);
	}

	/**
	 * This is a container for the object's saved data
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * A container for changed data that needs saving to our collection
	 *
	 * @var array
	 */
	protected $_changed = array();

	/**
	 * Assigns field data by name. Assigned variables will first be added to
	 * the $_changed variable until the collection has been updated.
	 *
	 * @todo Allow setting of data through dot path notation (eg. comment.author)
	 *
	 * @param  mixed  field name or an array of field => values 
	 * @param  mixed  field value 
	 * @return $this
	 */
	public function set($values, $value = NULL)
	{
		if ($value)
		{
			// Normalise single field setting to multiple field setting
			$values = array($values => $value);
		}

		if ( ! $values)
			return $this;

		// Flatten our set data
		$values = Mundo::flatten($values);

		foreach ($values as $field => $value)
		{
			// Check the field exists
			if ( ! $this->_check_field_exists($field))
			{
				throw new Mundo_Exception("Field ':field' does not exist", array(':field' => $field));
			}

			// Set our data
			Arr::set_path($this->_changed, $field, $value);
		}

		return $this;
	}

	/**
	 * Allow setting our field values by overloading, as in:
	 *
	 *    $model->field = $value;
	 *
	 * @param  string  $field   Field name
	 * @param  string  $value  Value
	 * @return void
	 */
	public function __set($field, $value)
	{
		$this->set($field, $value);
	}

	/**
	 * Allow retrieving field values via overloading.
	 *
	 * !! Note: Returns NULL if field does not exist.
	 *
	 * @param  string  $field  name of field to retrieve
	 * @return mixed
	 */
	public function __get($field)
	{
		return $this->get($field);
	}


	/**
	 * Checks if a field's value is set
	 *
	 * @param  string  $field field name
	 * @return bool
	 */
	public function __isset($field)
	{
		return ! ($this->get($field) === NULL);
	}

	/**
	 * Unset a field's data (ie. reset it's data to NULL)
	 *
	 * @param  string $field  Field name to unset
	 * @return void
	 */
	public function __unset($field)
	{
		// If there isn't any original data or changed data set, return
		if ( ! $this->original($field) AND ! $this->changed($field))
			return;

		// If the field has been changed and there is no original data, we need to remove it from our changed array
		$changed = $this->changed($field) AND ! $this->original($field);

		// Set our $field to NULL
		$this->set(array($field => NULL));
	}

	/**
	 * Gets data from the object. Note that this merges the original data 
	 * ($_data) and the changed data ($_changed) before returning.
	 *
	 * @param  string  Path of the field to return (eg. comments.0.author)
	 * @return mixed   Array of data if no path was supplied, or the value of the field/null if no field was found
	 **/
	public function get($path = NULL)
	{
		if ( ! $path)
		{
			// Flatten data so we can remove any NULL elements
			$data = Mundo::flatten($this->_merge());

			// Remove empty fields
			$data = array_filter($data);

			// Return purged data
			return (empty($data)) ? NULL : Mundo::inflate($data);
		}

		// Flatten our field just in case
		$data = Arr::path($this->_merge(), $path);

		// If it's not an array return it
		if ( ! is_array($data))
			return $data;

		$data = Mundo::flatten($data); 

		// Remove empty fields
		$data = array_filter($data);

		return Mundo::inflate($data);
	}

	/**
	 * Gets changed data for a given field. If no field is supplied, this
	 * returns all changed data.
	 *
	 * @return mixed
	 */
	public function changed($path = NULL)
	{
		if ( ! $path)
		{
			return $this->_changed;
		}

		return Arr::path($this->_changed, $path);
	}

	/**
	 * Gets original (saved) data for a given field. If no field is supplied, 
	 * this returns all original data.
	 *
	 * @return mixed
	 */
	public function original($path = NULL)
	{
		if ( ! $path)
		{
			return $this->_data; 
		}

		return Arr::path($this->_data, $path);
	}

	/**
	 * Convenience function for merging saved and changed data
	 *
	 * @return array
	 */
	protected function _merge()
	{
		return Arr::merge($this->_data, $this->_changed);
	}

	/**
	 * Loads a Validation object with the rules defined in the model and 
	 * either the set model data or the data passed as an argument.
	 *
	 * Note: This function returns a Validation object and does not validate
	 * data itself. Just run check() on this function's return value. This is
	 * because it makes it easier to grab error messages and use the normal
	 * Validation library.
	 *
	 * @param   string $data 
	 * @return  Validation
	 */
	public function validate($data = NULL)
	{
		if ( ! $data)
		{
			// Use already set data if none is given
			$data = $this->_merge();
		}

		$flat_data = Mundo::flatten($data);

		$validation = Validation::factory($flat_data);

		if ($this->_rules)
		{
			// Get our rules
			$rules = $this->_extract_rules($data);

			foreach ($rules as $field => $rule)
			{
				// Assign the rules for each field
				$validation->rules($field, $rule);
			}
		}

		return $validation;
	}

	/**
	 * Helper function used to validate current data when querying database
	 *
	 * @throws Validation_Exception
	 * @return boolean
	 */
	protected function _validate()
	{

		$validate = $this->validate();

		if ( ! $validate->check())
		{
			throw new Validation_Exception($validate);
			return FALSE;
		}

		return TRUE;
	}
	/**
	 * This extracts rules form $_rules in the format required for the
	 * Validation library
	 *
	 * @param string $rules 
	 * @return void
	 */
	protected function _extract_rules($data, $rules = NULL, $path = NULL)
	{
		// Our initially empty ruleset which will hold the collections entire rules
		$ruleset = array();

		// If rules werent provided through recursivity reformat.
		if ( ! $rules)
		{
			$rules = $this->_rules;
		}

		foreach ($rules as $field => $rules)
		{
			if ( ! strpos($field, '$'))
			{
				// The easiest of the bunch: just assign the rules
				$ruleset[$path.$field] = $rules;
				continue;
			}

			// Explode the $field on the first positional modifier to go through each member collection
			$field = explode('$', $field, 2);

			// Find out how many collections in this section we're accounting for
			$collection_count = count(Arr::path($data, $path.$field[0])) - 1;

			// We also need to add the collection's fields to the rules. They are separated 
			// to field => $rule in the next function call in the for-each loop
			$rules = array($field[1] => $rules);

			// We need to loop through each collection and re-call the function to add the rules to it.
			while($collection_count >= 0)
			{
				// Add the path to where we are, the field that contains the collections and the collection count together
				$new_path = $path.$field[0].$collection_count;

				// Pass the data, embedded collection fields and rules and new path together
				$return = $this->_extract_rules($data, $rules, $new_path);

				// Add the returns
				$ruleset += $return;

				$collection_count--;
			}
		}

		return isset($ruleset) ? $ruleset : FALSE;
	}

	/**
	 * Whether we have a document loaded from the database
	 *
	 * @var boolean
	 */
	protected $_loaded = FALSE;

	/**
	 * Returns the $_loaded value which indicates whether a document has been
	 * loaded from the database
	 *
	 * @return boolean
	 */
	public function loaded()
	{
		return $this->_loaded;
	}

	/**
	 * Creates a new document in our collection
	 *
	 * @return  mixed  $this
	 * @throws  mixed  Validation_Exception, Mundo_Exception
	 */
	public function create()
	{
		if ($this->get('_id'))
		{
			// Running the load() method alters $_loaded, so we need to duplicate our class

			// Get the model class name (PHP => 5.3.X )
			$class = get_called_class();

			// Create a duplicate class; 
			$object = new $class;

			// Assign our ID
			$object->set('_id', $this->get('_id'));

			// See if an object with this ID exists
			if($object->load($this->get('_id'))->loaded())
			{
				// We cannot create a document with a duplicate ID
				Throw new Mundo_Exception("Creating failed: a document with ObjectId ':object_id' exists already.", array(":object_id" => $this->get('_id')));
			}

			// Garbage collection
			unset($object, $class);
		}

		// Ensure our data is valid
		$this->_validate();

		// Intiialise our database
		$this->_init_db();

		// Merge our existing data and changed data
		$data = $this->_merge();

		// Insert our data
		$this->_collection->insert($data, array('safe' => $this->_safe));

		// Reset our $_changed to empty after our save
		$this->_changed = array();

		// Update our saved data variable
		$this->_data = $data;

		// We're now loaded
		$this->_loaded = TRUE;

		return $this;
	}

	/**
	 * Saves model data using the MongoCollection::save driver method
	 *
	 * @return $this
	 **/
	public function save()
	{
		// Validate our data
		$this->_validate();

		// If we have no changed data why bother?
		if ( ! $this->changed())
			return $this;

		// Get our original data so we can merge changes
		$data = $this->original();

		// Flatten our changed data for set_path calls
		$changed = Mundo::flatten($this->changed());

		// For each piece of changed data merge it in.
		foreach($changed as $field => $value)
		{
			Arr::set_path($data, $field, $value);
		}

		// Connect to and query the collection
		$this->_init_db();
		$this->_collection->save($data, array('safe' => $this->_safe));

		// Reset our changed array
		$this->_changed = array();

		// Replace our data just in case an upsert created an ID
		$this->_data = $data;

		// Ensure we're loaded if that was an upsert
		$this->_loaded = TRUE;

		return $this;
	}

	/**
	 * Atomically updates the document according to data in the changed
	 * property.
	 *
	 * @returns $this
	 */
	public function update()
	{
		// If this isn't loaded fail
		if ( ! $this->loaded())
		{
			throw new Mundo_Exception("Cannot atomically update the document because the model has not yet been loaded");
		}

		// Do no work if possible.
		if ( ! $this->changed())
			return $this;

		// Initialise an array with all atomic operations as keys
		$query = array(
			'$inc' => array(),
			'$set' => array(),
			'$unset' => array(),
			'$push' => array(),
			'$pushAll' => array(),
			'$addToSet' => array(),
			'$pop' => array(),
			'$pull' => array(),
			'$pullAll' => array(),
			'$bit' => array(),
		);

		// Take our changed and original and flatten them for comparison
		$changed = Mundo::flatten($this->changed());
		$original = Mundo::flatten($this->original());

		foreach ($changed as $field => $value)
		{
			// If this exists but the new one is null, $unset it
			if ($value === NULL)
			{
				$query['$unset'] += array($field => 1);
				continue;
			}

			// Are we amending a field that has at least one positional modifier in it?
			if ( ! in_array($field, $this->_fields) AND in_array(preg_replace('#\.[0-9]+#', '.$', $field), $this->_fields))
			{
				// Instantly we know there will be a $push/$pull etc. modifier to the container array.

				// If this arose from an array_push, $push it etc.
				// A flat out $model->set() is a complete replacement.
				continue;
			}

			// If the value is an array, test for array modifiers.
			if (is_array($value))
			{
				// Are we adding to the array?
			}

			// If this is numeric, $ing it
			// Everything is a $set by default
			$query['$set'] += array($field => $value);
		}

		/*
		 * Testing shit really. Ignore
		foreach ($this->_changed as $field => $value)
		{
			// If this exists but the new one is null, $unset it
			if ($value === NULL)
			{
				$query['$unset'] += array($field => 1);
				continue;
			}

			if (is_array($value))
			{
				// If this is an associative array, set it using dot notation as you would in the shell
				if (Arr::is_assoc($value))
				{
					// Get our array keys
					$keys = array_keys($value);
					foreach ($keys as $key)
					{

					echo Debug::vars($field);

					//$query['$set'] += array($field.'.'.$value
				}

				echo Debug::vars($value, Arr::is_assoc($value));
			}

			// Everything is a $set by default
			$query['$set'] += array($field => $value);
		}
		*/


		// Remove the empty atomic operations
		$query = array_filter($query);

		// echo Debug::vars($query);
	}

	/**
	 * Pushes $data onto the end of an array. This replaces array_push
	 * because of overloaded properties, and accepts the same arguments
	 * as array_push
	 *
	 * @param  $field  the field we are pushing data onto
	 * @param  $data1, $data2... Data to push onto the end of this array
	 * @return int     the total number of elements in the array
	 **/
	public function push()
	{
		// Get the function arguments
		$args = func_get_args();

		// Shift the field name from the data to append
		$field = array_shift($args);

		if ( ! $this->_check_field_exists($field))
		{
			// Fail because there's nothing to modify
			throw new Mundo_Exception("Field ':field' does not exist", array(':field' => $field));
		}

		// Find out how many embedded collections are in $field (this will give us the next array key too)
		$count = count($this->get($field));

		// Loop through each piece of data to push onto the $field
		foreach($args as $data)
		{
			$this->_changed[$field][$count] = $data;
			$count++;
		}

		return $count;
	}

	/**
	 * Removes the last element in the array $field and returns it.
	 *
	 * @param  $field  the field we are popping
	 * @return mixed   popped data 
	 */
	public function pop($field)
	{
		// Get the most recent model data
		$data = $this->get($field);

		if ( ! is_array($data))
		{
			// We can only pop arrays
			throw new Mundo_Exception("Field ':field' is not an array", array(':field' => $field));
		}

		// Find the last key we're modifying
		$count = count($data) - 1;

		// Set the last element of the array to null so it overwrites data in get()
		$this->_changed[$field][$count] = NULL;

		// Add this to $_next_update
		$this->_next_update['$pop'] += array($field => 1);

		// Return the element we just set to null from $data
		return $data[$count];
	}

	/**
	 * Helper function which takes a field name and checks if it has been
	 * defined in the model
	 *
	 * @param $field  string  field name
	 * @return bool
	 */
	protected function _check_field_exists($field)
	{
		// Replace any positional modifier keys with '$'
		$field = preg_replace('#\.[0-9]+#', '.$', $field);

		// If the field exists or it is the parent of an embedded collection return true
		return (in_array($field, $this->_fields) OR preg_grep('/^'.str_replace(array('$', '.'), array('\$', '\.'), $field).'\./', $this->_fields));
	}

	/**
	 * Stores an array containing the last update() query sent to the driver
	 * Mongo PHP driver
	 *
	 * @var array
	 **/
	protected $_last_update;

	/**
	 * An array which contains the next atomical update to save $_changed
	 * data in the collection. We organise all array fields so we don't
	 * have to check if they exists to add to them when changing data.
	 *
	 * @var array
	 */
	protected $_next_update = array(
			'$inc' => array(),
			'$set' => array(),
			'$unset' => array(),
			'$push' => array(),
			'$pushAll' => array(),
			'$addToSet' => array(),
			'$pop' => array(),
			'$pull' => array(),
			'$pullAll' => array(),
			'$bit' => array(),
		);

	/**
	 * Displays the last atomic operation as it would have been sent to the
	 * Mongo PHP driver
	 *
	 * @return array
	 */
	public function last_update()
	{
		return $this->_last_update;
	}

	/**
	 * Connect to Mongo for queries
	 *
	 * @return self 
	 */
	protected function _init_db()
	{

		if ($this->_mongo instanceof Mongo AND $this->_db instanceof MongoDB AND $this->_collection instanceof MongoCollection)
		{
			// Our database is already initialised
			return $this;
		}

		// Get our configuration information
		$config = Kohana::$config->load("mundo");

		// Load and connect to mongo
		$this->_mongo = new Mongo();

		// Select our database
		$this->_db = $this->_mongo->{$config->database};

		// Set our safety settings
		$this->_safe = $config->mongo_safe;

		// Load our selected collection using the same variable as our collection name.
		$this->_collection = $this->_db->{$this->_collection};

		return $this;
	}

	/**
	 * Loads a single document from the database using the object's
	 * current data
	 *
	 * @param   MongoId   Object ID if you want to load from a specific ID without other model data
	 * @return  $this
	 */
	public function load($object_id = NULL)
	{
		$query = array();

		/**
		 * @todo Assess the below: should we just attempt to check for
		 * an object_id argument, use the current $_id from merged and
		 * then use all data instead of this?
		 */

		if ($object_id)
		{
			// Load from the given ObjectId
			$query = array('_id' => $object_id);
		}
		elseif ( ! $this->changed() AND ! $this->loaded())
		{
			// No data to query with
			throw new Mundo_Exception("No model data supplied");
		}
		elseif ( ! $this->changed())
		{
			// No changed data, so assume we are reloading our object. Use the current ObjectId.
			$query = array('_id' => $this->get('_id'));
		}
		else
		{
			// Use all recent data as our query
			$query = $this->get();
		}

		// Initialise our database
		$this->_init_db();

		if ($result = $this->_collection->findOne($query))
		{
			// Assign our returned data
			$this->_data = $result;

			// Set our loaded flag
			$this->_loaded = TRUE;

			// Reset our changed array
			$this->_changed = array();
		}

		return $this;
	}

} // End Mundo_Object_Core
