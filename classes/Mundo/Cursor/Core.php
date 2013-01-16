<?php defined('SYSPATH') or die('No direct script access.');

/**
 * This class overrides the MongoCursor class to return Mundo objects instead of 
 * associated arrays for each object in the query result
 *
 * @since 0.7
 */
class Mundo_Cursor_Core extends MongoCursor
{
	/**
	 * Whether we loaded partial fiels or all of them
	 *
	 * @var boolean
	 */
	protected $_partial = FALSE;

	/**
	 * The name of the Mundo object class to return from {@link current()}
	 *
	 * @var string
	 */
	protected $_object;

	/**
	 * This method creates the database query as per MongoCollection::find().
	 *
	 * The only difference is that this method saves whether we are loading 
	 * a subset of fields and the Mundo object name in the cursor for use in 
	 * {@link current()}
	 */
	public function __construct(Mongo $connection, $ns, $query = array(), $fields = array(), $object_name)
	{
		parent::__construct($connection, $ns, $query, $fields);

		if ( ! empty($fields))
		{
			$this->_partial = TRUE;
		}

		$this->_object = $object_name;
	}

	/**
	 * This method returns a new Mundo object for the current query element. It 
	 * works in exactly the same way as the standard MongoCursor except for the 
	 * return.
	 *
	 * @return Mundo_Object
	 */
	public function current()
	{
		$data = parent::current();

		if ( ! is_array($data))
			return $data;

		return new $this->_object($data, array('partial' => $this->_partial));
	}

	/**
	 * Returns the next document from the cursor. This is identical to 
	 * getNext() except this returns a Mundo object.
	 *
	 * @return Mundo_Object
	 */
	public function getNext()
	{
		$this->next();
		return $this->current();
	}
}
