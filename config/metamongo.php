<?php defined('SYSPATH') or die('No direct script access.');

// This allows our environment to dictate which database to connect to
switch(Kohana::$environment)
{
	case Kohana::PRODUCTION:
	case Kohana::STAGING:
		// Staging & production database
		$enviromnent_settings = array(
			'database'  => 'metamongo-production',
		);
		break;

	case Kohana::TESTING:
		// Testing database
		$enviromnent_settings = array(
			'database'  => 'metamongo-testing',
		);
		break;

	case Kohana::DEVELOPMENT:
		// Development database
		$enviromnent_settings = array(
			'database'  => 'metamongo-dev',
		);
}

return array(

	/**
	 * @todo Allow replica sets and the like.
	 **/

	// The "Safe" value used for Mongo inserts with important information.
	// See http://www.php.net/manual/en/mongocollection.insert.php
	'mongo_safe'   => TRUE,

) + $enviromnent_settings;