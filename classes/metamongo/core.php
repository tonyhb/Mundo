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

} // End MetaMongo_Core