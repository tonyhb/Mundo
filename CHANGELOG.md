Changelog
---------
### Version 0.7.3 (15 January 2013)
* PSR-0 standard for autoloading classes / kohana 3.3
* Implemented $_filters for Mundo_Object

### Version 0.7.2 (1 September 2012)
* Adds a `getNext()` method to `Mundo_Cursor_Core` which returns Mundo object instances instead of arrays

### Version 0.7.1 (21 February 2012)

* Allowing model names to use forward slashes, mapped to an underscore. For example, we can now do `Mundo::factory('account/user')` and load a user model in the `model/account/user` path.
* Fixing a couple of typos in the README.md file examples.

### Version 0.7 (8 December 2011)

* Adding find() method to load a cursor of results
* Implementing a Mundo_Cursor class to return Mundo objects instead of arrays when looping through a mongo cursor

### Version 0.6.4

* Fixed bug where setting values for loading changed atomic update queries

### Version 0.6.3 (4 October 2011)

* Added support for schema-less embedded objects as opposed to the whole object

### Version 0.6.2 (3 October 2011)

* Sometimes the author codes drunk

### Version 0.6.1 (2 October 2011)

* Fixing glaringly obvious mistake in the README file.

### Version 0.6 (2 October 2011)

* Adding functionality for schema-less mapping
* Adding version numbers to Mundo_Core.php

### Version 0.5.4 (30th September 2011)

* Fixing load() bug where queries weren't being flattened before being sent to MongoDB. This stopped us reaching into objects.

### Version 0.5.3 (15th September 2011)

* Added support for Mongo authentication
* Added support for Mongo replica sets
* Added support for fsync checking when writing data
* Added support for custom timeout argument when writing data
* Added the ability to delete documents from a collection
* Fixing load() bug in which the _partial variable was incorrectly being set

### Version 0.5.2 (5th September 2011)

* Added the ability to retrieve a subset of fields with the load() method.

### Version 0.5.1 (2nd September 2011)

* Fixed a bug where atomic updates didn't validate data before querying Mongo

### Version 0.5 (2nd September 2011)

Half way there! In this initial alpha release you can do the following:

* Create new objects
* Save objects by replacing the whole object
* Update objects with $inc, $set and $unset, which are created automatically when updating model data
* Automatically create atomic operations for $push, $pull etc. by using simple methods.
* Validate an object's data automatically before creation and updating

There's more stuff to come (the other atomic modifiers, filtering data when set and extending models past the written schema) but it's in a usable state right now. Of course, if you find a bug or have an idea please do make a new issue. I'd love to hear your feedback
