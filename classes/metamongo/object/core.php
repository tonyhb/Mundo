<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Core functions for the MetaMongo objects, including data manipulation,
 * database updates and database searching.
 *
 * @package MetaMongo
 * @author Tony Holdstock-Brown
 **/
class MetaMongo_Object_Core
{

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

} // End MetaMongo_Object_Core