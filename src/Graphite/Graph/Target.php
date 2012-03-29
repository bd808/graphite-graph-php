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
 * Example:
 * <code>
 * <?php
 * $g = new Graphite_GraphBuilder(array('width' => 600, 'height' => 300));
 * $g->title('Memory')
 *   ->vtitle('Mbytes')
 *   ->bgcolor('white')
 *   ->fgcolor('black')
 *   ->from('-2days')
 *   ->area('stacked')
 *   ->prefix('metrics.collectd')
 *   ->prefix('com.example.host-1')
 *   ->prefix('snmp')
 *   ->metric('memory-free', array(
 *     'cactistyle' => true,
 *     'color' => '00c000',
 *     'alias' => 'Free',
 *     'scale' => '0.00000095367',
 *   ))
 *   ->metric('memory-used', array(
 *     'cactistyle' => true,
 *     'color' => 'c00000',
 *     'alias' => 'Used',
 *     'scale' => '0.00000095367',
 *   ));
 * ?>
 * <img src="http://graphite.example.com/render?<?php echo $g->qs(); ?>">
 * </code>
 *
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 * @link http://readthedocs.org/docs/graphite/en/latest/functions.html
 */
class Graphite_Graph_Target {

  /**
   * @var array
   */
  protected $functions;

  /**
   * Constructor.
   *
   * @param string $series Base series to construct target from.
   */
  public function __construct ($series=null) {
    $this->functions = array();
    if (null !== $series) {
      $this->functions['series'] = $series;
    }
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
   * @return array Target configuration
   */
  public function asMetric () {
    return $this->functions;
  }


  /**
   * Build a traget parameter.
   *
   * @return string Target parameter for use in query string
   */
  public function build () {
    return self::generate($this);
  }


  /**
   * Builder factory.
   *
   * @param string $series Base series to construct target from.
   */
  static public function builder ($series=null) {
    return new Graphite_Graph_Target($series);
  }

  /**
   * Generate the target parameter for a given configuration.
   * @param mixed $conf Configuration as array or Graphite_Graph_Target object
   * @return string Target parameter for use in query string
   * @throws Graphite_ConfigurationException If neither series nor target is set
   *    in $conf
   */
  static public function generate ($conf) {
    if ($conf instanceof Graphite_Graph_Target) {
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
  } //end generateTarget


} //end Graphite_Graph_Target
