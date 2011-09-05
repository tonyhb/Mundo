Changelog
---------

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
