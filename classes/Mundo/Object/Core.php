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
	 * Whether or not you can set fields not defined in $_fields
	 *
	 * @var bool;
	 */
	protected $_schemaless = FALSE;

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
	 * Whether we have a document loaded from the database
	 *
	 * @var boolean
	 */
	protected $_loaded = FALSE;

	/**
	 * Whether or not the model is partially loaded, ie. only selected
	 * fields were returned from the database
	 */
	protected $_partial;

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
			'$pushAll' => array(), // This takes care of $push
			'$pullAll' => array(), // This takes care of $pull
			'$addToSet' => array(),
			'$pop' => array(),
			'$bit' => array(),
			'$inc' => array(),
			'$set' => array(),
			'$unset' => array(),
		);

	/**
	 * Initialise our data
	 *
	 * @param array $data 
	 */
	public function __construct($data = array(), $seed_state = array())
	{
		if ( ! is_array($data))
		{
			// Only accept data in an array
			throw new Mundo_Exception("Arrays are the only accepted arguments when constructing Mundo Models");
		}

		// Set our data
		if ( ! empty($seed_state))
		{
			$this->_clean_up($data);
			$this->_partial = $seed_state['partial'];
		}
		else
		{
			$this->set($data);
		}

		// Set the _collection property to a MongoCollection
		$this->_collection = Mundo::$db->{$this->_collection};
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
	 * Assigns field data by name. Assigned variables will first be added to
	 * the $_changed variable until the collection has been updated.
	 *
	 * This also handles the following positional modifier updates:
	 *   $set, $unset, $inc
	 *
	 * @param  mixed  field name or an array of field => values 
	 * @param  mixed  field value 
	 * @return $this
	 */
	public function set($values, $value = NULL)
	{
		if ($value OR ! is_array($values))
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
			if ($value === NULL)
			{
				// Call the unset method
				$this->unset_atomic($field);
				continue;
			}

			// Check the field exists
			if ( ! $this->_check_field_exists($field))
			{
				throw new Mundo_Exception("Field ':field' does not exist", array(':field' => $field));
			}

			// Set our data
			Arr::set_path($this->_changed, $field, $value);

			// Make sure the $unset operation isn't set
			if (isset($this->_next_update['$unset'][$field]))
			{
				unset($this->_next_update['$unset'][$field]);
			}

			if (is_numeric($value) AND (is_numeric($this->original($field)) OR $this->original($field) === NULL))
			{
				// Work out difference for incrementing
				$difference = $value - $this->original($field);
				$this->_next_update['$inc'] = array_merge($this->_next_update['$inc'], array($field => $difference));
				continue;
			}

			// This must be a $set
			$this->_next_update['$set'] = array_merge($this->_next_update['$set'], array($field => $value));

		}

		return $this;
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

		$data = Arr::path($this->_merge(), $path);

		// If it's not an array return it
		if ( ! is_array($data))
			return $data;

		// Flatten the data
		$data = Mundo::flatten($data); 

		// Remove empty fields
		$data = array_filter($data);

		return Mundo::inflate($data);
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
		$this->unset_atomic($field);
	}

	/**
	 * Unsets $field data.
	 *
	 * This function sets the field data in $_changed to NULL and adds the
	 * $unset atomic operation.
	 * 
	 * It does NOT remove the field from $_changed, otherwise the save
	 * method will save old data.
	 *
	 * Note that this has atomic_ suffix because there's already a function
	 * called unset. Of course.
	 *
	 * @param  string  Field name to set, in dot notation form
	 * @return $this
	 */
	public function unset_atomic($field)
	{
		// If there isn't any original data or changed data set, return
		if ( ! $this->original($field) AND ! $this->changed($field))
			return $this;

		// Set the changed data to null
		Arr::set_path($this->_changed, $field, NULL);

		// Flatten the update array so we can check this doens't exist elsewhere
		$next_update = Mundo::flatten($this->_next_update);

		$preg_field = str_replace('.', '\.', $field);

		// Check the flattened update array to see that there are no other atomic operations for this field
		$key = preg_grep('#^\$[a-zA-Z]+\.'.$preg_field.'#', array_keys($next_update));

		if ( ! empty($key))
		{
			// Get the first key
			$key = array_shift($key);

			// Remove the atomic operation from the flat update array
			unset($next_update[$key]);
		}

		// Inflate the array ready for replacement
		$next_update = Mundo::inflate($next_update);

		// Unsetting the last operation for an atomic operator removes it completely
		$this->_reset_update();

		// So merge the empty (reset) next update with the updated one
		$this->_next_update = array_merge($this->_next_update, $next_update);

		// Removing the previous atomic operation may have been enough, but if the original exists unset the data
		if ($this->original($field) !== NULL)
		{
			// We don't need to array merge because the value will always be 1
			$this->_next_update['$unset'] += array($field => 1);
		}

		return $this;
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
	 * Returns whether the model is partially loaded and we only have
	 * selected fiedls returned from the database.
	 */
	public function partial()
	{
		return $this->_partial;
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
	 * Helper function which takes a field name and checks if it has been
	 * defined in the model
	 *
	 * @param $field  string  field name
	 * @return bool
	 */
	protected function _check_field_exists($field)
	{
		if ($this->_schemaless === TRUE)
			return TRUE;

		// Replace any positional modifier keys with '$'
		$field = preg_replace('#\.[0-9]+#', '.$', $field);

		if (is_array($this->_schemaless))
		{
			foreach($this->_schemaless as $schemaless_field)
			{
				if(substr($field, 0, strlen($schemaless_field)) == $schemaless_field)
					return TRUE;
			}
		}

		// If the field exists or it is the parent of an embedded collection return true
		return (in_array($field, $this->_fields) OR preg_grep('/^'.str_replace(array('$', '.'), array('\$', '\.'), $field).'\./', $this->_fields));
	}

	/**
	 * Helper method for the atomic operation $inc
	 *
	 * This method passes all logic thanks to set() and checks that
	 * the current operation uses numeric values.
	 *
	 * This method
	 *
	 * @param  mixed    field name or array of field => values
	 * @param  numeric  amount to increase or decrease by
	 * @return void
	 */
	public function inc($values, $value)
	{
		if ($value)
		{
			// Normalise single field setting to multiple field setting
			$values = array($values => $value);
		}

		$values = Mundo::flatten($values);

		foreach($values as $field => &$value)
		{
			if ( ! is_numeric($this->original($field)) AND $this->original($field) !== NULL)
			{
				// Non-numeric, cant $inc
				throw new Mundo_Exception('Cannot apply $inc modifier to non-number in field \':field\'', array(':field' => $field));
			}

			if ( ! is_numeric($value))
			{
				// Can only apply $inc modifier with numeric values
				throw new Mundo_Exception('Cannot apply $inc modifier with non-numeric values in field \':field\'', array(':field' => $field));
			}

			// Add $value onto $changed
			$value += $this->get($field);
		}

		$this->set($values);

		return $this;
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

		if (isset($this->_next_update['$pushAll'][$field]))
		{
			// Add the data to the $pushAll
			$this->_next_update['$pushAll'][$field] = array_merge($this->_next_update['$pushAll'][$field], $args);
		}
		else
		{
			$this->_next_update['$pushAll'][$field] = $args;
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

		// Is this a variable that has been pushed and hasn't been written to the database?
		if (isset($this->_changed[$field][$count]) AND isset($this->_next_update['$pushAll'][$field]))
		{
			// If so, stop it from being added in $pushAll
			array_pop($this->_next_update['$pushAll'][$field]);

			// If that was the only atomic update for the $field...
			if (empty($this->_next_update['$pushAll'][$field]))
			{
				// Remove the $field completely
				unset($this->_next_update['$pushAll'][$field]);
			}
		}
		else
		{
			// Add this to $_next_update
			$this->_next_update['$pop'] += array($field => 1);
		}

		// Set the last element of the array to null so it overwrites data in get()
		$this->_changed[$field][$count] = NULL;

		// Return the element we just set to null from $data
		return $data[$count];
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
			$data = $this->get();
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
	 * Loads a single document from the database using the object's
	 * current data.
	 *
	 * You can pass an array of fields to return from the database. This
	 * is the same argument as the $field in MongoCollection::find. 
	 *
	 * Note that unlike MongoCollection::find, this merges query data with
	 * data returned from the database. For example, if you have a username
	 * and load only the email address, the model will contain the username
	 * and email address, not just the data returned from the database.
	 *
	 * @param   array  Array of fields to return or exclude from Mongo
	 * @return  $this
	 */
	public function load($fields = array())
	{
		$query = array();

		if ( ! $this->changed() AND ! $this->loaded())
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
			// Use all recent data as our query. You should use indexed keys here.
			// Note that this is flattened so we can query into objects
			$query = Mundo::flatten($this->get());
		}

		if ($result = $this->_collection->findOne($query, $fields))
		{
			// Only set the data, _loaded and _partial if the load was a success

			if ( ! empty($fields))
			{
				// Merge returned fields with data we queried with
				$this->_data = array_merge($this->get(), $result);
			}
			else
			{
				// There's no harm in overwriting all of our data with the query result
				$this->_data = $result;
			}

			// Set our loaded flag
			$this->_loaded = TRUE;

			// Reset our changed array
			$this->_changed = array();

			// Reset our next update property
			$this->_reset_update();

			if (empty($fields))
			{
				// We loaded the full object from the database
				$this->_partial = FALSE;
			}
			else
			{
				// We loaded only partial fields.
				$this->_partial = TRUE;
			}
		}

		return $this;
	}

	/**
	 * Creates a query from the current data and returns a cursor with all 
	 * results from the query.
	 *
	 * This is similar to {@link load()} but is not limited to one result
	 * 
	 * @param array  array of fields to return
	 * @return Mundo_Cursor
	 */
	public function find($fields = array())
	{
		// Use all recent data as our query. You should use indexed keys here.
		// Note that this is flattened so we can query into objects
		$query = Mundo::flatten($this->get());

		$config = Kohana::$config->load('Mundo');

		$model_name = get_class($this);

		return new Mundo_Cursor(Mundo::$mongo, $config->database.'.'.$this->_collection->getName(), $query, $fields, $model_name);
	}

	/**
	 * Creates a new document in our collection
	 *
	 * @param   array  Array of options for the MongoCollection::insert method
	 * @return  mixed  Same as MongoCollection::insert (@see 
	 *                 http://php.net/manual/en/mongocollection.insert.php)
	 * @throws  mixed  Validation_Exception, Mundo_Exception
	 */
	public function create($options = array())
	{
		if ($this->_loaded == TRUE)
		{
			throw new Mundo_Exception("Cannot create a new document because the model is already loaded");
		}

		// Filter our data
		$this->_filter();

		// Ensure our data is valid
		$this->_validate();

		// Merge our existing data and changed data
		$data = $this->_merge();

		// Merge the default options with user provided ones, if necessary
		$options = Arr::merge(Kohana::$config->load('Mundo')->query_options, $options);

		// Insert our data
		$return = $this->_collection->insert($data, $options);

		$this->_clean_up($data);

		return $return;
	}

	/**
	 * Saves model data using the MongoCollection::save driver method
	 *
	 * @param  array  Array of options for the MongoCollection::save method
	 * @return $this
	 **/
	public function save($options = array())
	{
		// If we have no changed data why bother?
		if ( ! $this->changed())
			return $this;

		if ($this->partial())
		{
			throw new Mundo_Exception("Cannot save the model because it is only partially loaded. Use the update method instead or fully load the object");
		}

		// Filter our data
		$this->_filter();

		// Validate our data
		$this->_validate();

		// Get our original data so we can merge changes
		$data = $this->original();

		// Flatten our changed data for set_path calls
		$changed = Mundo::flatten($this->changed());

		// For each piece of changed data merge it in.
		foreach($changed as $field => $value)
		{
			Arr::set_path($data, $field, $value);
		}

		// Merge the default options with user provided ones, if necessary
		$options = Arr::merge(Kohana::$config->load('Mundo')->query_options, $options);

		$this->_collection->save($data, $options);

		$this->_clean_up($data);

		return $this;
	}

	/**
	 * Atomically updates the document according to data in the changed
	 * property.
	 *
	 *  !! NOTE: This operation does not support upserts, even if you
	 *           include it in $options.
	 *
	 * @param   array  Array of options for the MongoCollection::update method
	 * @returns bool whether the update was successful or not
	 */
	public function update($options = array())
	{
		// If this isn't loaded fail
		if ( ! $this->loaded())
		{
			throw new Mundo_Exception("Cannot atomically update the document because the model has not yet been loaded");
		}

		// Do no work if possible.
		if ( ! $this->changed())
			return $this;

		// Filter our data
		$this->_filter();

		// Validate our data
		$this->_validate();

		// Put our modifier query into a variable
		$update = $this->next_update();

		// Merge the default options with user provided ones, if necessary
		$options = Arr::merge(Kohana::$config->load('Mundo')->query_options, $options);

		// Update using our $id
		$status = $this->_collection->update(array('_id' => $this->get('_id')), $update, $options);

		// Get our original data so we can merge changes
		$data = $this->original();

		// Flatten our changed data for set_path calls
		$changed = Mundo::flatten($this->changed());

		// For each piece of changed data merge it in.
		foreach($changed as $field => $value)
		{
			Arr::set_path($data, $field, $value);
		}

		// Copy the next update into last update
		$this->_last_update = $update;

		$this->_clean_up($data);

		return $status;
	}

	/**
	 * Returns the query used to atomically update currently changed data
	 *
	 * @param  string  Atomic operator to return. Will return all atomic 
	 *                 operations by default
	 * @return array
	 */
	public function next_update($operator = NULL)
	{
		if ($operator === NULL)
		{
			// Intialise our return array
			$update = array();

			// Loop through each modifier and remove the empty ones
			foreach($this->_next_update as $modifier => $data)
			{
				if ( ! empty($data))
				{
					$update[$modifier] = $data;
				}
			}

			return $update;
		}

		if ( ! array_key_exists($operator, $this->_next_update))
		{
			throw new Mundo_Exception("The atomic operation ':operation' does not exist", array(':operation' => $operator));
		}

		return $this->_next_update[$operator];
	}

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
	 * Resets the $_next_update protected variable
	 *
	 * @return void;
	 */
	protected function _reset_update()
	{
		$this->_next_update = array(
			'$pushAll' => array(),
			'$pullAll' => array(),
			'$addToSet' => array(),
			'$pop' => array(),
			'$bit' => array(),
			'$inc' => array(),
			'$set' => array(),
			'$unset' => array(),
		);
	}

	/**
	 * Deletes documents from the collection. This uses data from the 
	 * {@link get()} method as the query, or can use data passed in the
	 * method's argument as the query.
	 *
	 * @param  array  Data to use as the database query
	 * @param  array  Array of options as used in MongoCollection::remove().
	 *                These will overwrite any options set in the config.
	 *                  !! This is currently unimplemented
	 *
	 * @see    get()
	 * @link   http://www.php.net/manual/en/mongocollection.remove.php
	 *
	 * @return mixed  Returns the same as MongoCollection::remove(),
	 *                depending on the safe setting
	 **/
	public function delete($query = array(), $options = array())
	{
		if (empty($query))
		{
			$query = $this->get();
		}

		// Merge the default options with user provided ones, if necessary
		$options = Arr::merge(Kohana::$config->load('Mundo')->query_options, $options);

		return $this->_collection->remove($query, $options);
	}

	/**
	 * Helper function for database interction methods.
	 *
	 * This is the model cleanup process after successful communication
	 * with MongoDB
	 *
	 * @param  $array  new model data from database communication
	 * @return void
	 */
	protected function _clean_up($data)
	{
		// Reset our $_changed to empty after our save
		$this->_changed = array();

		// Reset the update variable
		$this->_reset_update();

		// Update our saved data variable
		$this->_data = $data;

		// We're now loaded
		$this->_loaded = TRUE;
	}

	/**
	 * Filters the data and prepares data for validation
	 *
	 * @param   string $data 
	 * @return  void
	 */
	public function _filter($data = NULL)
	{
		if ( ! $data)
		{
			// Use already set data if none is given
			$data = $this->get();
		}

		$flat_data = Mundo::flatten($data);

		if ($this->_filters)
		{
			// Get our rules
			$filters = $this->_extract_rules($data, $this->_filters);
			
			foreach ($filters as $field => $array)
			{
				// Bind the field name and model so they can be used in the filter method
				$_bound = array
				(
					':field' => $field,
					':model' => $this,
				);

				foreach ($array as $filter_array) 
				{
					$_bound[':value'] = $this->get($_bound[':field']);

					$filter = $filter_array[0];
					$params = Arr::get($filter_array, 1, array(':value'));

					foreach ($params as $key => $param)
					{
						if (is_string($param) AND array_key_exists($param, $_bound))
						{
							// Replace with bound value
							$params[$key] = $_bound[$param];
						}
					}

					if (is_array($filter) OR ! is_string($filter))
					{
						// This is either a callback as an array or a lambda
						$value = call_user_func_array($filter, $params);
					}
					elseif (strpos($filter, '::') === FALSE)
					{
						// Use a function call
						$function = new ReflectionFunction($filter);

						// Call $function($this[$field], $param, ...) with Reflection
						$value = $function->invokeArgs($params);
					}
					else
					{
						// Split the class and method of the rule
						list($class, $method) = explode('::', $filter, 2);

						// Use a static method call
						$method = new ReflectionMethod($class, $method);

						// Call $Class::$method($this[$field], $param, ...) with Reflection
						$value = $method->invokeArgs(NULL, $params);
					}

					$this->set($_bound[':field'], $value);
				}
			}
		}
	}
} // End Mundo_Object_Core