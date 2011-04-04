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
	 * @var arra
	 */
	protected $_rules;

	/**
	 * An array of filters which are ran when setting data.
	 *
	 * @var array
	 */
	protected $_filters;

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
	 * This is an internal variable used
	 *
	 * @var string
	 */
	private $__array_path;

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
		// Save this so looping through our fields doesn't overwrite the path permanently.
		$parent_path = $this->__array_path;

		if ($value)
		{
			// Normalise single field setting to multiple field setting
			$values = array($values => $value);
		}

		if ( ! $values)
			return $this;

		foreach ($values as $field => $value)
		{

			if (strpos($field, '.') !== FALSE)
			{
				// We're using dot notation to set an embedded object, so separate our path string.
				$paths = explode('.', $field);

				// Pop the field name we are setting (the last element)
				$field = array_pop($paths);

				// Take the remaining keys as our parent path and array path
				$parent_path = $this->__array_path = implode('.', $paths);
			}

			// If the array path exists
			$this->__array_path = ($this->__array_path) ? $this->__array_path.'.'.$field : $field;

			if (is_array($value))
			{
				// Call set on the embedded object
				$this->set($value);
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
				Arr::set_path($this->_changed, $this->__array_path, $value);
			}

			// Reset our array path
			$this->__array_path = $parent_path;
		}

		return $this;
	}

	/**
	 * Gets data from the object. Note that this merges the original data 
	 * ($_data) and the changed data ($_changed) before returning.
	 *
	 * @param  string  Path of the field to return (eg. comments.0.author)
	 * @return mixed
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
	 * Convenience function for merging saved and changed data
	 *
	 * @return array
	 */
	protected function _merge()
	{
		return Arr::merge($this->_data, $this->_changed);
	}
} // End MetaMongo_Object_Core