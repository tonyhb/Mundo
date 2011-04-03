<?php

/**
 * Tests the MetaMongo class
 *
 * @package MetaMongo
 * @category Tests
 * @author Tony Holdstock-Brown
 */
class MetaMongo extends PHPUnit_Framework_TestCase
{

	public function __construct()
	{
		parent::__construct();

		try
		{
			// Ensure we include our MetaMongo instance without Kohana's help. Note that __DIR__ is equivalent to dirname(__FILE__) and was added in PHP 5.3.0
			// include __DIR__.'/test_data/blogpost.php';
		}
		catch (Exception $e) {}
	}

	/**
	 * Ensures the factory method assigns the variables passed and returns a
	 * MetaMongo instance.
	 *
	 *
	 * @acovers MetaMongo::factory
	 */
	public function test_factory_returns_instance_with_values()
	{
		MetaMongo::factory('blogpost');
	}

}
