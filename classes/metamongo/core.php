<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Base functions for MetaMongo. This class handles creation of models as well
 * as standard validation and conversion functions for models.
 *
 * @package MetaMongo
 * @author Tony Holdstock-Brown
 **/
class MetaMongo_Core
{

	const ASC = 1;
	const DESC = -1;

	/**
	 * Return a MetaMongo_Object class with some initial $data.
	 *
	 * @return  MetaMongo_Object
	 * @author  Tony Holdstock-Brown
	 **/
	public static function factory($model, $data = array())
	{
		// String typecast
		$model = (string) $model;
		
		$model = 'Model_'.$model;

		return new $model($data);
	}

	/**
	 * Flatten our data into an associative array that can be rebuilt using
	 * Arr::set_path.
	 *
	 * For example: 
	 *     array(
	 *       'post_content' => 'Post content here' 
	 *       'comments'     => array(
	 *           array (
	 *             'author'  => 'Comment Author', 
	 *             'text'    => 'Comment text here'
	 *           ),
	 *           array (
	 *             'author'  => 'Another Author', 
	 *             'text'    => 'Comment body again'
	 *           ),
	 *     ));
	 *
	 * becomes
	 *
	 *     array(
	 *       'post_content'      => 'Post content here'
	 *       'comments.0.author' => 'Comment Author',
	 *       'comments.0.text  ' => 'Comment text here,
	 *       'comments.1.author' => 'Another Author',
	 *       'comments.1.text  ' => 'Comment body again,
	 *     );
	 *
	 * We use this instead of Arr::flatten because this builds paths as array keys.
	 *
	 * @param   array   $data  Data to flatten 
	 * @return  array
	 */
	public static function flatten($data)
	{
		if ( ! $data OR ! is_array($data))
			return array();

		return self::_flatten($data);
	}

	/**
	 * Logic behind the flatten() method.
	 *
	 * @param   array   Data to flatten 
	 * @param   string  Internal use for recursivity
	 * @return  array
	 */
	protected static function _flatten($data, $path = NULL)
	{
		$flat = array();
		foreach ($data as $field => $value)
		{
			// Add the field to our path
			$dot_path = ($path) ? $path.'.'.$field : $field;

			if (is_array($value))
			{
				// Call this function again on embedded collections or objects with our path reference
				$flat += self::_flatten($value, $dot_path);
			}
			else
			{
				// Assign our data to our path/field name
				$flat[$dot_path] = $value;
			}

		}
		return $flat;
	}

	/**
	 * Takes dotted-path arrays and rebuilds it as an associative array.
	 *
	 * For example:
	 *    array(
	 *       'container.name' => 'Name here',
	 *       'container.data' => 'Data here',
	 *     );
	 *
	 * becomes
	 *    array(
	 *       'container' => array(
	 *           'name' => 'Name here',
	 *           'data' => 'Data here',
	 *       ),
	 *    );
	 *
	 * @param  array $data 
	 * @return void
	 * @author Tony Holdstock-Brown
	 */
	public static function inflate($data)
	{
		$result = array();

		foreach ($data as $path => $value)
		{
			Arr::set_path($result, $path, $value);
		}

		return $result;
	}

	/**
	 * Returns whether a variable is an instance of a class
	 *
	 * @param  mixed   $object  Variable to test 
	 * @param  string  $name    Instance type to check for
	 * @return bool
	 */
	public static function instance_of($object, $name)
	{
		return is_object($object) && ($object instanceof $name);
	}

} // End MetaMongo_Core