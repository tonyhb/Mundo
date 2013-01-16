## Running tests

To run the unittests, navigate to the `tests` directory and type the following:

`phpunit --configuration phpunit.xml --coverage-html report`

This will run the tests from the XML file and output a coverage report in the `$modpath/tests/report` directory, which should be in the `.gitignore` file (so you don't need to worry about Git, baby).


## Viewing code coverage reports

Sometimes, with some web server setups, the `$modpath/tests/report` directory is not publicly visible. You can open the files without a web server or set the `$modpath/tests/report` directory as the document root of a virtual host. Naice.