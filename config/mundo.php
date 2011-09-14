<?php defined('SYSPATH') or die('No direct script access.');

// This allows our environment to dictate which database to connect to
switch(Kohana::$environment)
{
	case Kohana::PRODUCTION:
	case Kohana::STAGING:
		// Staging & production database
		$enviromnent_settings = array(
			'database'  => 'Mundo-production',
		);
		break;

	case Kohana::TESTING:
		// Testing database
		$enviromnent_settings = array(
			'database'  => 'Mundo-testing',
		);
		break;

	case Kohana::DEVELOPMENT:
		// Development database
		$enviromnent_settings = array(
			'database'  => 'Mundo-dev',
		);
}

return array(

	/**
	 * @todo Allow replica sets and the like.
	 **/

	'query_options' => array(
		'safe' => TRUE,
		'fsync' => FALSE,
		'timeout' => 20000, // Default driver timeout of 20 secods.
	),

) + $enviromnent_settings;
