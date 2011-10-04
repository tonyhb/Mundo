Mundo
=====

Mundo is a mapping system written for the MongoDB Database and the [Kohana Framework](http://kohanaframework.org/).

It's currently in beta and can do the following:

* Validate model data (including embedded objects and collections)
* Provide an object for the model's state and data
* Automatically update collections using atomic operations where possible
* Allow both schema and schema-less mapping with minimal hassle

There's example usage in the tests folder and the code is well commented (hopefully). The unit test provides 100% coverage but it's not quite comprehensive. Of course, if you find a bug or have an idea please do make a new issue.


Basic setup
===========

Database
--------

You can choose which Mongo servers, replica sets and databases you connect to in the Mundo configuration file. This follows suit with the official PHP driver, using exactly the same parameters.

Models
------

In Mundo, each of your collections are mapped to separate models using the `Mundo_Object` class. In it, you define the collection's fields (`protected $_fields`) and any validation you want to happen on these fields (`protected $_validation`) using Kohana's validation library.

Here's an example, which we will run through after the code:

	class Model_Blogpost extends Mundo_Object
	{
		protected $_schemaless = FALSE; // Setting this to TRUE allows you to write to and read fields not defined in the $_fields variable

		protected $_collection = 'posts'; // This is the name of the collection in Mongo		

		protected $_fields = array(
			'_id', // This field must always be specified as it always exists.
			'post_title',
			'post_slug',
			'post_date',
			'author', // This will be a reference to another collection's ObjectID
			'author_name', // Denormalisation is a good thing with NoSQL (in general).
			'author_email', 
			'post_excerpt',
			'post_content',
			'post_metadata.keywords', // Use dot notation to illustrate embedded objects
			'post_metadata.description',
			'comments.$.comment', // The positional operator ($) denotes multiple embedded objects
			'comments.$.author_name',
			'comments.$.author_url',
			'comments.$.author_email',
			'comments.$.likes.$', // The positional operator ($) at the end of a field denotes an array of values
			'comments.$.like_count',
		);

		protected $_rules = array(

			'post_title' => array( 
				array('not_empty'), // These methods are set in the Valid class, though you can use the same syntax as the Validation library to call your own functions.
			),
			'post_slug' => array(
				array('alpha_dash', array(':value', TRUE)), // The first array value is the method name, the second is an array of paramters.
				array('not_empty'),
			),
			'post_date' => array(
				array('Mundo::instance_of', array(':value', 'MongoDate')), // Mundo comes with an instanceof static method to ensure we insert correct Mongo classes.
				array('not_empty'),
			),
			'author' => array(
				array('Mundo::instance_of', array(':value', 'MongoId')),
				array('not_empty'),
			),
			),
			'comments.$.author_name' => array(
				array('not_empty'),
			),
			'comments.$.author_email' => array(
				array('not_empty'),
				array('email'),
			),
			'comments.$.likes.$' => array(
				array('regex', array(':value', '/^[\w\s]+$/')),
			),
			'comments.$.like_count' => array(
				array('numeric'),
			),
			'post_metadata.keywords' => array(
				array('not_empty'),
			),
			'post_metadata.description' => array(
				array('not_empty'),
			),
		);

		// Model-related methods...
	}

We've covered a lot in this code. You can see how the model is organised and how we denote embedded objects and documents. Here's a quick run-through:

### Embedded objects

Embedded objects are referenced using dot-notation, exactly the same way as in the Mongo shell. If you've got an address, you can define the state as `address.state` and apply rules in the same way.


### Embedded documents

If you want to save multiple embedded objects within a field (comments in a blog post, for example), just use the positional operator `$` as you would in the Mongo shell. The positonal operator tells Mundo that you can have many embedded objects within a field. 

You apply validation rules the same way. Validation happens on each field in each embedded object recursively, so if you've got a comment and a user hasn't supplied their name in one of them validation will still throw an error.


### Array of values

If you've got an array of values (say, a list of names of people that liked a post) use the positional modifier `$` at the end of the field (for example, `comments.$.likes.$` to save `array('John', 'Michael', 'Emma')`).

### Schema-less mapping

If you want the flexibility that NoSQL provides and don't want to have to map out your fields, versions upwards of 0.6 allow schema-less mapping. Just set the `$_schemaless` variable to TRUE and you can add, edit and atomically update fields on the go. Note that unmapped fields cannot have validation rules applied to them, so that's up to you.

If you only want schema-less embedded collections, you can set the `$_schemaless` in exactly the same way as `$_fields`:

	protected $_schemaless = array(
		'metadata', // This allows a single embedded object (ie metadata.created_on, metadata.keywords etc.)
		'comments.$ // This allows an array of embedded objects (ie comments.0.comment, comments.1.comment, comments.1.votes)
	);


Basic usage
===========

Instantiating
-------------

Once you've defined your model, you'll want to instantiate it to use it. You should always use use the `Mundo::factory()` method like so:

    $model = Mundo::factory('blogpost');

This returns a new `Model_Blogpost`. You can also pass an array of key => values as the second argument to automatically set data upon creation:

    $model = Mundo::factory('blogpost', array('keys' => 'values'));


Setting data
------------

There's a couple of ways you can do this; all of them have the same results. Jolly good. Here's the first - overloading properties:

    $model->field = 'value';

Or using the set method:

    $model->set('field', 'value');

Or with an array of field => values:

    $model->set(array('field' => 'value', 'field_2' => 'value'));

Setting a model always returns itself so you can chain and chain and chain, like so to atomically update:

    $model->set('field', 'value')->update();

Setting embedded data is easy, too:

    $model->set('post_metadata.keywords', 'foo, bar');

Or:

    $model->('post_metadata.keywords') = 'foo, bar, bas';


Creating a document
-------------------

There's two ways to do this: calling `save()` to perform an upsert or calling `create()`. The `create()` method is generally better because it won't overwrite anything if you accidentally have another object's MongoId in the data. Here's an example:

    $model->create();

or:

    $model->save(); // this upserts, which means it will create an object if it doesn't exist or update if it does.


Saving and updating a document
------------------------------

There's two ways you can update an object's representation in the database, just like the mongo shell. You can `save()` to replace the current object or `update()` to perform atomic modifiers on the data. The second way is normally better. You've seens aving above (if not, it's in the 'creating an object' section), so lets talk about atomic updates.

### Atomic operations

The following atomic operations are supported: `$set`, `$inc`, `$unset`, `$pushAll` and `$popAll` (the last two remove the need for `$push` and `$pop`).

Atomic updates are made by default when using any of the set methods for `$set`, `$inc` and `$unset` (for `$unset` you can set the field to `NULL` or use the `atomic_unset()` method). 

To use `$pushAll` and `$popAll`, use the `push()` and `pop()` methods:

    $model->push('field', $array...); // You can supply an endless number of arrays as arguments to push to the model. Neato!

    $value = $model->pop('field');

You can see what the next atomic update query will be by calling the `next_update()` method:

    $atomic_operation = $model->next_query();

This is useful because sometimes you might make queries that have conflicting mods and you'll need to debug.

You can see the last atomic operation by calling the `last_update()` respectively:

    $last_atomic_operation = $model->last_query();

Magic, huh?


Loading a document
------------------

Loading is much the same as any other ORM. You set model data (preferably indexed data such as the `_id` field) and run the `load()` method, like so:

	$model->set('_id', new MongoId('000000000001')); // we could have used $model->_id = new MongoId(); too.
	
	$model->load();
	
	// Sanity check and ensure the model has loaded
	if ( ! $model->loaded())
	{
		throw new Exception("We couldn't find that model in the database!");
	}

You can see we checked the model was loaded using the `loaded()` method, which will return `TRUE` or `FALSE` respectively.

*Subset of fields*

You can choose to return a subset of fields from Mongo too. Just pass an array to the `load()` method with the field names you want to load (or ignore). It works exactly the same way as [MongoCollection::find in PHP](http://www.php.net/manual/en/mongocollection.find.php).

If you do choose to load just a few fields from the whole object, Mundo will set the model's state to partially loaded. This means you won't be able to run the `save()` method and overwrite your entire object with just the few fields you're working with. You can check if your model is partially loaded by running:

    $partial = $model->partial(); // TRUE if you've returned a subset of fields, FALSE if you have the whole object

This resets if you run `load()` and get the whole object. Cool, huh?

Deleting a document
-------------------

Since Mundo 0.5.3 you can delete a document from a collection. To do this use the `delete()` method:

    $model->delete();

Note that if you have no data in your model this can (and will) remove every object from your collection! Nasty. It might be prudent to ensure you've not got an empty data from `get()` before you call this method, eh.


Wrap-up
-------

That's the basics! Remember, it's not feature-complete. I want to add a find() method to query for many results, allow filtering of data when it's set (so we can automatically hyphenise slugs, for example) and allow extending objects beyond the `$_fields`.

If you've found a bug or have an idea for this please raise a new issue, I'd love to hear your feedback.

You can use this library wherever and whenever you see fit: it's released under the MIT lisence.
