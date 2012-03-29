<?php
/**
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

/**
 * DSL for building Graphite graph target components.
 *
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_Graph_Target {

  /**
   * Handle attempts to read from non-existant members.
   *
   * Looks for $name in settings and returns value if found.
   * If the setting isn't valid an E_USER_NOTICE warning will be raised.
   *
   * @param string $name Setting name
   * @return mixed Setting value or null if not found
   */
  public function __get ($name) {
    if ('qs' == $name || 'url' == $name) {
      return $this->qs();
    }

    $cname = Graphite_Graph_Params::canonicalName($name);
    if (array_key_exists($cname, $this->settings)) {
      $val = $this->settings[$cname];

      if ('hide' == mb_substr($cname, 0, 4) &&
          'hide' != mb_substr(mb_strtolower($name), 0, 4)) {
        // invert inverted alias
        $val = !$val;
      }

      return $val;
    }

    if (false === $cname) {
      // invalid request
      $trace = debug_backtrace();
      trigger_error("Undefined property via __get(): {$name} in " .
          "{$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
    }
    return null;
  } //end __get


  /**
   * Handle attempts to write to non-existant members.
   *
   * Sets setting $name=$val if $name is a valid setting.
   * If the setting isn't valid an E_USER_NOTICE warning will be raised.
   *
   * @param string $name Member name
   * @param mixed $val Value to set
   * @return void
   */
  public function __set ($name, $val) {
    $cname = Graphite_Graph_Params::canonicalName($name);
    if (false !== $cname) {
      if ('hide' == mb_substr($cname, 0, 4) &&
          'hide' != mb_substr(mb_strtolower($name), 0, 4)) {
        // invert logical toggle
        $val = !((bool) $val);
      }

      $this->settings[$cname] = $val;
    } else {
      // invalid request
      $trace = debug_backtrace();
      trigger_error("Undefined property via __set(): {$name} in " .
          "{$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
    }
  } //end __set


  /**
   * Handle attempts to call non-existant methods.
   *
   * Looks for $name in settings and gets/sets value if found.
   * If the setting isn't valid an E_USER_NOTICE warning will be raised.
   *
   * @param string $name Method name
   * @param array $args Invocation arguments
   * @return mixed Property value or self
   */
  public function __call ($name, $args) {
    if (count($args) > 0) {
      // set property and return self for chaining
      $this->__set($name, $args[0]);
      return $this;

    } else {
      // return property
      return $this->__get($name);
    }
  } //end __call

  /**
   * Generate the target parameter for a given configuration.
   * @param array $conf Configuration
   * @return string Target parameter
   * @throws Graphite_ConfigurationException If neither series nor target is set
   *    in $conf
   */
  static public function generate ($conf) {
    if (isset($conf['target'])) {
      // explict target has been provided by the user
      return $conf['target'];
    }

    if (!isset($conf['series'])) {
      throw new Graphite_ConfigurationException(
        "metric does not have any data associated with it.");
    }

    // find functions named in the conf data
    $funcs = array();
    foreach ($conf as $key => $args) {
      $name = Graphite_Graph_Functions::canonicalName($key);
      if ($name) {
        $funcs[$name] = $args;
      }
    }
    // sort the found functions by priority
    uksort($funcs, array('Graphite_Graph_Functions', 'cmp'));

    // start from the provided series
    $target = $conf['series'];
    $haveAlias = false;

    // build up target string
    foreach ($funcs as $name => $args) {
      $spec = Graphite_Graph_Functions::callSpec($name);

      if (is_scalar($args)) {
        $args = array($args);
      }

      if ($spec->isAlias && $haveAlias) {
        // only one alias should be applied in each target
        continue;

      } else if ($spec->isAlias && !$args[0]) {
        // explicitly disabled alias
        continue;
      }

      $callArgs = $spec->formatArgs($args);
      array_unshift($callArgs, $target);
      $target = "{$name}(" . implode(',', $callArgs) . ")";

      // keep track of alias application
      $haveAlias = $haveAlias || $spec->isAlias;
    } //end foreach $funcs

    return $target;
  } //end generateTarget


} //end Graphite_Graph_Target
