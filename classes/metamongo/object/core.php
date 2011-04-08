<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Core functions for the MetaMongo objects, including data manipulation,
 * database updates and database searching.
 *
 * @package MetaMongo
 * @subpackage MetaMongo_Object
 * @author Tony Holdstock-Brown
 **/
class MetaMongo_Object_Core
{

	/**
	 * This is the name of the collection we're saving to in MongoDB.
	 *
	 * !! Note: This string becomes a MongoCollection instance once _init_db()
	 *          has been called (see MetaMongo_Core for this method).
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
			throw new MetaMongo_Exception("Arrays are the only accepted arguments when constructing MetaMongo Models");
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

		// Call our set function
		$this->_set($values);

		return $this;
	}

	/**
	 * The logic behind the set() & __set() methods, set as protected to avoid 
	 * accidentally setting the $parent_path parameter and to reduce confusion.
	 *
	 *
	 * @param   array  $values        Values to set 
	 * @param   string $parent_path   The path to our current field
	 * @return  void
	 */
	protected function _set($values, $parent_path = NULL)
	{
		foreach ($values as $field => $value)
		{
			if (strpos($field, '.') !== FALSE)
			{
				// We're using dot notation to set an embedded object, so separate our path string.
				$paths = explode('.', $field);

				// Pop the field name we are setting (the last element)
				$field = array_pop($paths);

				// Take the remaining keys as our parent path and array path
				$parent_path = implode('.', $paths);
			}

			// Set our working path as either the field name or the path to our field.
			$path = ($parent_path) ? $parent_path.'.'.$field : $field;

			if (is_array($value))
			{
				// Call set on the embedded object
				$this->_set($value, $path);
			}
			else
			{
				if ($parent_path)
				{
					// Exchange numerical paths for the '$' indicator.
					if ($object = Arr::path($this->_fields, preg_replace('#\.[0-9]+#', '.$', $parent_path)))
					{
						// Check to see that the field exists or we have an array that allows any kind of data.
						$field_exists = in_array($field, $object) || $object[0] == '$';
					}
					else
					{
						// We could not get an object for the parent path, assume the field doesn't exist. 
						// This should never happen, hence the code coverage tags. If it does, send me an email =D

						// @codeCoverageIgnoreStart
						$field_exists = FALSE;
						// @codeCoverageIgnoreEnd
					}
				}
				else
				{
					// CHeck to see if the value exists in the first array dimension
					$field_exists = in_array($field, $this->_fields);
				}

				if ( ! $field_exists)
				{
					// Add the path to the field name to show where the error occurred
					$field = ($parent_path) ? $parent_path.'.'.$field : $field;
					throw new MetaMongo_Exception("Field ':field' does not exist", array(':field' => $field));
				}

				// Set our data
				Arr::set_path($this->_changed, $path, $value);
			}
		}
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
			return $this->_merge(); 
		}

		return Arr::path($this->_merge(), $path);
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

		$flat_data = MetaMongo::flatten($data);

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
	 * This extracts rules form $_rules in the format required for the
	 * Validation library
	 *
	 * @param string $rules 
	 * @return void
	 */
	protected function _extract_rules($data, $rules = NULL, $path = NULL)
	{

		if ( ! $rules)
		{
			// We have to manually set them with recusivity
			$rules = $this->_rules;
		}

		foreach ($rules as $field => $rule)
		{
			
			if ($field == '$')
			{
				// If this is an embedded collection, we need to work out how many collections we're accounting for.
				// This is to assign validation rules to each collection member we have.
				$collection_number = count(Arr::path($data, $path)) - 1;

				if ($collection_number < 0)
				{
					// We have no embedded objects, so don't validate
					continue;
				}
			}
			else
			{
				// Add dots to our path (not necessary on the first traversal)
				$dotted_path = $path ? $path.'.'.$field : $field;

				// Hack to loop assignments once without collecitons
				$collection_number = 1;
			}

			do
			{
				if ($field == '$')
				{
					// Add our collection number to our path (if we need to).
					$dotted_path = $path ? $path.'.'.$collection_number : $collection_number;
				}

				if (Arr::is_assoc($rule))
				{
					// If $rule is an associative array this is an embedded object/coll. Run this again.
					if ($embedded_rules = $this->_extract_rules($data, $rule, $dotted_path))
					{
						// Make sure we return it
						$ruleset = isset($ruleset) ? Arr::merge($ruleset, $embedded_rules) : $embedded_rules;
					}
				}
				else
				{
					// Assign our rule
					$ruleset[$dotted_path] = $rule;
				}
			}
			while($collection_number--);
		}

		// Return our rules
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
	 * @throws  mixed  Validation_Exception, MetaMongo_Exception
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
				Throw new MetaMongo_Exception("Creating failed: a document with ObjectId ':object_id' exists already.", array(":object_id" => $this->get('_id')));
			}

			// Garbage collection
			unset($object, $class);
		}

		$validate = $this->validate();

		if ( ! $validate->check())
		{
			throw new Validation_Exception($validate);
		}

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
		$config = Kohana::config('metamongo');

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

		if ($object_id)
		{
			// Load from the given ObjectId
			$query = array('_id' => $object_id);
		}
		elseif ( ! $this->changed() AND ! $this->loaded())
		{
			// No data to query with
			throw new MetaMongo_Exception("No model data supplied");
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

} // End MetaMongo_Object_Core