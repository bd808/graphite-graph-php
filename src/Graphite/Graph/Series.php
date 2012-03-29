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
 * @link http://readthedocs.org/docs/graphite/en/latest/functions.html
 */
class Graphite_Graph_Series {

  /**
   * @var array
   */
  protected $functions;

  /**
   * @var Graphite_GraphBuilder
   */
  protected $graph;


  /**
   * Constructor.
   *
   * @param string $series Base series to construct target from.
   */
  public function __construct ($series=null, $graph=null) {
    $this->functions = array();
    if (null !== $series) {
      $this->functions['series'] = $series;
    }
    $this->graph = $graph;
  } //end __construct


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
    $func = Graphite_Graph_Functions::canonicalName($name);
    if (false !== $func) {
      $this->functions[$func] = $args;
    }
    return $this;
  } //end __call


  /**
   * Get the description of this target as an array suitable for use with a
   * call to {@link Graphite_GraphBuilder::metric()}.
   *
   * @return array Series configuration
   */
  public function asMetric () {
    return $this->functions;
  }


  /**
   * Build a target parameter.
   *
   * @return mixed Series parameter for use in query string or parent graph
   * @todo Document difference
   */
  public function build () {
    if (null === $this->graph) {
      return self::generate($this);
    } else {
      return $this->graph->metric('', $this->asMetric());
    }
  }


  /**
   * Builder factory.
   *
   * @param string $series Base series to construct target from.
   */
  static public function builder ($series=null) {
    return new Graphite_Graph_Series($series);
  }

  /**
   * Generate the target parameter for a given configuration.
   * @param mixed $conf Configuration as array or Graphite_Graph_Series object
   * @return string Series parameter for use in query string
   * @throws Graphite_ConfigurationException If neither series nor target is set
   *    in $conf
   */
  static public function generate ($conf) {
    if ($conf instanceof Graphite_Graph_Series) {
      $conf = $conf->functions;
    }

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

      if ($spec->isAlias() && $haveAlias) {
        // only one alias should be applied in each target
        continue;

      } else if ($spec->isAlias() && !$args[0]) {
        // explicitly disabled alias
        continue;
      }

      // format call as a string
      $target = $spec->asString($target, $args);

      // keep track of alias application
      $haveAlias = $haveAlias || $spec->isAlias();
    } //end foreach $funcs

    return $target;
  } //end generateSeries


} //end Graphite_Graph_Series
