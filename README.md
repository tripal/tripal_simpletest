Tripal Core Test module
=======================

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Usage
 * Maintainers


INTRODUCTION
------------

This module enables functional testing on Tripal extension modules through
Drupal Simpletest module (Drupal core).
Examples of .test file can be found here:
 * https://github.com/tripal/tripal_dt/blob/master/tripal_dt.test
 * http://cgit.drupalcode.org/sandbox-guignonv-2789913/tree/brapi.test


REQUIREMENTS
------------

This module requires the following modules:

 * Tripal >=7.x-2.1 (not tested under 3.x)
   (http://www.drupal.org/project/tripal)
   The version of Tripal must provide the function tripal_get_schema_name() that
   triggers hook_tripal_get_schema_name_alter() which is not available in older
   releases of Tripal 2.x (before December 2016).
 * At the time this README is being written, there appear to be an issue in
   Drupal Core: the simpletest module can't drop PostgreSQL tables in the
   correct order and it will fail to drop a table if this table is still
   referenced elsewhere.
   A workaround is to patch Drupal core file
   `includes/database/pgsql/schema.inc` so it can drop tables regardless their
   dependencies, line 348:
   replace (or comment)
   ```
    $this->connection->query('DROP TABLE {' . $table . '}');
   ```
   by this
   ```
    $this->connection->query('DROP TABLE {' . $table . '} CASCADE');
   ```


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

 * IMPORTANT: you don't need to enable this module in order to run tests! It
   should be automatically enabled by tests during testing only. Therefore it is
   recommended not to enable this module to avoid side effects.


USAGE
-----

When you write tests for your regular Drupal modules, you usually extend the
class "DrupalWebTestCase". If you need to test Tripal extensions, you will need
to extend "TripalTestCase" instead as this class will manage Chado instantiation
and Tripal module activation for you while it will provide the same features as
the "DrupalWebTestCase" class.

To use this class, you will need to import it into your test using:
```
  module_load_include('php', 'tripal_core_test', 'tripal_test_case');
```
Note: It should work even if the module tripal_core_test is not enabled.

It is recommended that you implement only one "public function testXXX()" to
avoid multiple Chado instantiation as each "testXXX" function is run with a new
clean Chado instance and each instantiation take a lot of time and may cause
timeouts.

So your test class definition should look something similar to:
```
  module_load_include('php', 'tripal_core_test', 'tripal_test_case');
  class MyTripalModuleTestCase extends TripalTestCase {
    ...
    public static function getInfo() {
      return array(
        'name' => 'Your Functional Tests',
        'description' => 'Ensure that the extension works properly.',
        'group' => 'YourTestGroupName',
      );
    }
    ...
    public function setUp() {
      // List of module to enable by default (machine names) *excluding* Tripal
      // extension modules but including Tripal package modules. You don't need
      // to take care of dependencies as they are automatically enabled as well.
      $modules_to_enable = array('module1', 'module2', ...);
      parent::setUp($modules_to_enable);
      // Enable your Tripal module(s).
      module_enable(array('tripal_your_extension'), TRUE);
      // Apply thoses changes to the test environment.
      $this->resetAll();

      // Add initialization stuff like module settings and Chado data insertion.
      ...
    }
    ...
    public function testMyFunctionality() {
      // Do testing stuff and assertions.
      ...
    }
  }
```
If you implement the member function tearDown(), don't forget to call parent
implementation:
```
  public function tearDown() {
    // Do your stuff
    ...
    parent::tearDown();
  }
```

References:
 * https://www.drupal.org/docs/7/testing/simpletest-testing-tutorial-drupal-7
 * https://www.drupal.org/docs/7/testing/assertions

To run the tests from the Drupal interface, make sure you enabled simpletest
(core) module and go to:
http://www.yourdevsite.com/admin/config/development/testing
Then select your test and just click "Run tests". It will take time and you may
have a timeout error but you can proceed to the "error" page and your tests may
have passed successfully anyway.

To run the tests from the command line, go to your Drupal installation root and
type:
php -f scripts/run-tests.sh -- --url http://www.yourdevsite.com/ YourTestGroupName
 

MAINTAINERS
-----------

Current maintainer(s):

 * Valentin Guignon (vguignon) - https://www.drupal.org/user/423148
