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

This module enables functionnal testing on Tripal extension modules through
Drupal Simpletest module (Drupal core).
An example of .test file can be found on Tripal Git at:
https://github.com/tripal/tripal_dt


REQUIREMENTS
------------

This module requires the following modules:

 * Tripal 7.x-2.x (not tested under 3.x) (http://www.drupal.org/project/tripal)
   The version of Tripal must provide the function tripal_get_schema_name() that
   triggers hook_tripal_get_schema_name_alter() which is not available in older
   releases of Tripal 2.x (before December 2016).


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

 * IMPORTANT: you don't need to enable this module in order to run tests! It
   should be automatically enabled by tests during testing only. Therefore it is
   recommanded not to enable this module to avoid side effects.


USAGE
-----

When you write tests for your regular Drupal modules, you usually extend the
class "DrupalWebTestCase". If you need to test Tripal extensions, you will need
to extend "TripalTestCase" instead as this class will manage Chado instantiation
and Tripal module activation for you while it will provide the same features as
the "DrupalWebTestCase" class.

To use this class, you will need to import it into your test using:

  module_load_include('php', 'tripal_core_test', 'tripal_test_case');

Note: It should work even if the module tripal_core_test is not enabled.

It is recommanded that you implement only one "public function testXXX()" to
avoid multiple Chado instantiation as each "testXXX" function is run with a new
clean Chado instance and each instantiation take a lot of time and may cause
timeouts.

So your test class definition should look something similar to:

  module_load_include('php', 'tripal_core_test', 'tripal_test_case');
  class MyTripalModuleTestCase extends TripalTestCase {
  ...
    public function setUp() {
      parent::setUp();
      // Enable your Tripal module.
      module_enable(array('tripal_dt'), TRUE);
      // Add initialization stuff like module settings and Chado data insertion.
      ...
    }
    ...
    public function testMyFunctionality() {
      // Do testing stuff and assertions.
      ...
    }
  }

If you impelment the member function tearDown(), don't forget to call parent
implementation:

  public function tearDown() {
    // Do your stuff
    ...
    parent::tearDown();
  }

References:
https://www.drupal.org/docs/7/testing/simpletest-testing-tutorial-drupal-7
https://www.drupal.org/docs/7/testing/assertions


MAINTAINERS
-----------

Current maintainers:

 * Valentin Guignon (vguignon) - https://www.drupal.org/user/423148
