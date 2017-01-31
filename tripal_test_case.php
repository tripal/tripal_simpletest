<?php

/**
 * @file
 * This is the base class for Tripal core and extensions testing. This class
 * handles the auto-installation of Chado and Tripal module activation.
 *
 * @see DrupalWebTestCase
 *
 * @ingroup tripal_core_test
 */
class TripalTestCase extends DrupalWebTestCase {

  /**
   * Tells which Tripal modules have been enabled throw TripalTestCase.
   * This is an associative array where keys are Tripal module machine names and
   * values are boolean (TRUE enabled).
   *
   * @var array
   */
  protected $enabled_tripal_modules = array();

  /**
   * Instanciate a new Chado schema.
   *
   * @param string $version
   *   Chado version to install. The string can be either of the form '1.3' or
   *   'Install Chado v1.3'. Supported Chado versions depend on the Tripal
   *   module version installed.
   * @return bool
   *   TRUE if Chado has been instantiated.
   *
   * @ingroup tripal_core_test
   */
  protected function initChado($version = 'Install Chado v1.3') {
    // Check Chado version.
    if (preg_match('/(?:^|\D)(\d\.\d+)/', $version, $matches)) {
      $version = 'Install Chado v' . $matches[1];
    }
    else {
      // Default to 1.3.
      $version = 'Install Chado v1.3';
    }

    $chado_initialized = FALSE;
    try {
      $this->verbose($version);
      // Capture Tripal outputs otherwise it would raise errors during the tests.
      ob_start();
      tripal_core_install_chado($version);
      $chado_initialized = $GLOBALS["chado_is_installed"];
    }
    catch (Exception $e) {
      $this->verbose('Chado initialization failed: ' . $e->getMessage());
    }
    // Get Tripal output.
    $tripal_message = ob_get_clean();
    if ($tripal_message) {
      $this->verbose('Chado initialization: ' . $tripal_message);
    }

    return $chado_initialized;
  }

  /**
   * Enables a given Tripal modules.
   *
   * @param string[] $modules
   *   List of Tripal modules to enable. Modules are enabled in the appropriate
   *   order with their dependencies. Tripal Views, Tripal DB and Tripal CV are
   *   authomatically enabled if needed. Tripal CV additional step (tripal job)
   *   is performed automatically.
   *
   * @ingroup tripal_core_test
   */
  protected function enableTripalModules($modules = array()) {

    $tripal_modules = array();
    foreach ($modules as $module) {
      $tripal_modules[$module] = $module;
    }
    // By default, enable Tripal Views, DB and CV.
    if (!$tripal_modules) {
      $tripal_modules = array('tripal_cv' => 'tripal_cv');
    }


    if ($tripal_modules && !isset($enabled_tripal_modules['tripal_views'])) {
      // Tripal Views is required by other modules and should be enabled first.
      module_enable(array('tripal_views'), TRUE);
      $enabled_tripal_modules['tripal_views'] = 'tripal_views';
      if (isset($tripal_modules['tripal_views'])) {
        unset($tripal_modules['tripal_views']);
      }
    }

    // Tripal Bulk Loader does not require other modules than Tripal Views.
    if (isset($tripal_modules['tripal_bulk_loader'])
        && !isset($enabled_tripal_modules['tripal_bulk_loader'])) {
      module_enable(array('tripal_bulk_loader'), TRUE);
      $enabled_tripal_modules['tripal_bulk_loader'] = 'tripal_bulk_loader';
      if (isset($tripal_modules['tripal_bulk_loader'])) {
        unset($tripal_modules['tripal_bulk_loader']);
      }
    }

    if ($tripal_modules && !isset($enabled_tripal_modules['tripal_db'])) {
      // Tripal DB is required by other modules and should be enabled after
      // Tripal Views.
      module_enable(array('tripal_db'), TRUE);
      $enabled_tripal_modules['tripal_db'] = 'tripal_db';
      if (isset($tripal_modules['tripal_db'])) {
        unset($tripal_modules['tripal_db']);
      }
    }

    // Tripal CV is required by other modules and should be enabled after
    // Tripal DB.
    if ($tripal_modules && !isset($enabled_tripal_modules['tripal_cv'])) {
      module_enable(array('tripal_cv'), TRUE);
      ob_start();
      tripal_cv_load_obo_v1_2_id(3, 1);
      $tripal_message = ob_get_clean();
      $enabled_tripal_modules['tripal_cv'] = 'tripal_cv';
      if (isset($tripal_modules['tripal_cv'])) {
        unset($tripal_modules['tripal_cv']);
      }
      $this->verbose("obo loaded. " . $tripal_message);
    }

    // Load other modules if some.
    if ($tripal_modules) {
      module_enable($tripal_modules, TRUE);
    }
  }

  /**
   * Set up the test environment for Tripal/Chado.
   *
   * This function accept several argument formats.
   *   -No argument: Tripal core, views, db and cv modules are enabled and
   *    Chado 1.3 is instantiated.
   *   -A single string corresponding to a non-Tripal module: Tripal core
   *    module is enabled, Chado 1.3 is instantiated and the given module is
   *    enabled.
   *   -A single string corresponding to a Tripal module: Tripal core and views
   *    modules are enabled and Chado 1.3 is instantiated. If the given Tripal
   *    module is Tripal DB or Tripal Bulk Loader, only that additional module
   *    will be enabled. Otherwise, Tripal DB and Tripal CV modules plus the
   *    given module will be enabled.
   *   -An array of string: Tripal core module is enabled, Chado 1.3 is
   *    instantiated and the given modules are enabled, including their
   *    requirements.
   *   -An associative array with the keys:
   *     -'modules': an array of name of modules to enable (follows the same
   *       rules as above).
   *     -'chado': a string containing the version of Chado to instanciate. If
   *       set to a FALSE value, Chado will NOT be instantiated. If not set,
   *       Chado 1.3 will be instantiated by default.
   *
   * @see DrupalWebTestCase::setUp()
   *
   * @ingroup tripal_core_test
   */
  public function setUp() {

    // Get arguments and see for specific settings.
    $args = func_get_args();
    if (!$args) {
      $args = array();
    }
    elseif (!is_array($args)) {
      // Case when a single module name has been specified as argument.
      $args = array('modules' => array($args));
    }
    // Check if an array of module names has been provided.
    if (isset($args[0])) {
      $args = array('modules' => array($args));
    }
    // Remove tripal modules for now. They will be enabled later.
    $tripal_modules = array();
    $modules = array();
    if (isset($args['modules'])) {
      foreach ($args['modules'] as $module) {
        if (preg_match('/^tripal/', $module)) {
          $tripal_modules[] = $module;
        }
        else {
          $modules[] = $module;
        }
      }
    }

    // Make sure we enable 'tripal_core_test' module in order to be able to work
    // with a testing Chado instance.
    $modules[] = 'tripal_core_test';
    parent::setUp($modules);

    // Check if Chado should be instantiated.
    $chado_initialized = FALSE;
    if (isset($args['chado'])) {
      if ($args['chado']) {
        $chado_initialized = $this->initChado($args['chado']);
      }
      else {
        $this->verbose('Chado not instantiated during setUp');
      }
    }
    else {
      $chado_initialized = $this->initChado();
    }

    // Remove Tripal core test module that has already been enabled before.
    if (isset($tripal_modules['tripal_core_test'])) {
      unset($tripal_modules['tripal_core_test']);
    }

    // Check we can run tests.
    if ($chado_initialized && $tripal_modules) {
      // Enables Tripal modules.
      $this->enableTripalModules($tripal_modules);
    }
    elseif ($tripal_modules) {
      $this->verbose('Unable to enable the following Tripal modules because Chado has not been instantiated: ' . implode(', ', $tripal_modules));
    }
  }

  /**
   * Remove Chado test instance.
   *
   * @ingroup tripal_core_test
   */
  public function tearDown() {
    db_query("DROP SCHEMA " . $this->databasePrefix . '_chado CASCADE;');
    parent::tearDown();
  }

}
