<?php
/**
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

if (!defined('E_USER_DEPRECATED')) {
  // php < 5.3.0 doesn't have deprecated constants so sub in notice
  define('E_USER_DEPRECATED', E_USER_NOTICE);
}

/**
 * Helper object for processing ini file configurations in
 * Graphite_GraphBuilder.
 *
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_Graph_IniHelper {

  /**
   * Paired Graphite_GraphBuilder
   *
   * @var Graphite_GraphBuilder
   */
  protected $builder;

  /**
   * Global graph configuration.
   *
   * @var array
   */
  protected $global = array();

  /**
   * Global graph configuration.
   *
   * @var array
   */
  protected $series = array();


  /**
   * Constructor.
   *
   * @param Graphite_GraphBuilder $builder Builder to configure
   */
  protected function __construct ($builder) {
    $this->builder = $builder;
    $this->global = array();
    $this->series = array();
  } //end __construct


  /**
   * Get a prefix by name.
   *
   * @param string $name Prefix name
   * @return string Fully expanded prefix
   */
  protected function prefix ($name) {
    return $this->builder->getMeta('prefix', $name, '');
  }


  /**
   * Load configuration data from the provided array into this instance.
   *
   * "Ini-style" confiugration data is expected to be an array as would be
   * returned by {@see Graphite_IniParser::parse()}. If a key in this array is
   * paired with a scalar value then it is assumed to be a graph level
   * configuration setting (eg. 'width' => 400). If the key indexes an array
   * then the value is assumed to be either a series to draw on the graph or
   * metadata configuration. Metadata is denoted by the inclusion of an ':is'
   * key in the value data.
   *
   * Configuration data is processed and stored in member variables of this
   * helper instance.
   *
   * @param array $ini Ini-style configuration data
   * @return void
   */
  protected function loadIniData ($ini) {
    foreach ($ini as $key => $value) {
      if (is_array($value)) {
        // sub-arrays either describe prefixes or series
        if (isset($value[':is'])) {
          // found some metadata
          $mdType = self::pop($value, ':is');
          $this->builder->storeMeta($mdType, $key, $value);

        } else if (isset($value[':is_prefix'])) {
          // deprecated version of ":is = prefix"
          unset($value[':is_prefix']);
          $this->builder->storeMeta('prefix', $key, $value);
          trigger_error("[{$key}] uses deprecated :is_prefix syntax.",
              E_USER_DEPRECATED);

        } else {
          // normal series
          $this->builder->storeMeta('series', $key, $value);
          $this->series[$key] = $value;
        }

      } else {
        // must be a general setting
        $this->global[$key] = $value;
      }
    } //end foreach
  } //end loadIniData


  /**
   * Find an existing series configuration.
   *
   * @param string $name Series name
   * @return array Series configuration
   */
  protected function findSeries ($name) {
    if (isset($this->series[$name])) {
      return $this->series[$name];
    }
    $series = $this->builder->getMeta('series', $name, false);
    if (!$series) {
      $series = $this->builder->getMeta('abstract', $name);
    }
    return $series;
  } //end findSeries


  /**
   * Inherit series configuration.
   *
   * Starting with a base series that indicates inheritance, walk upward in the
   * series inheritance hirarchy until you find a root series. Then come
   * back down the ancestry stack merging the parent's defaults with the
   * child's configuration until you get back to the orginal child.
   *
   * @param string $name Series to extend
   * @param array $child Configuration to merge with parent
   * @return array Series configuration
   */
  protected function extendSeries ($name, $child=null) {
    $conf = $this->findSeries($name);
    $parent = self::pop($conf, ':extends');
    if (null !== $parent) {
      $conf = array_merge($this->extendSeries($parent), $conf);
    }
    if (null !== $child) {
      $conf = array_merge($conf, $child);
    }
    return $conf;
  } //end extendSeries


  /**
   * Configure a Graphite_GraphBuilder using our internal state.
   *
   * @return void
   */
  protected function configureGraph () {
    // apply global settings to our graph
    foreach ($this->global as $setting => $args) {
      $this->builder->$setting($args);
    }

    // add series
    foreach ($this->series as $name => $conf) {
      // TODO: add support for preconfigured "types" like line, forecast, etc

      $parent = self::pop($conf, ':extends');
      if (null !== $parent) {
        $conf = $this->extendSeries($parent, $conf);
      }

      $prefix = self::pop($conf, ':prefix');
      if (null !== $prefix) {
        $this->builder->prefix($this->builder->lookupPrefix($prefix));
      }

      // series is either given explicitly or inferred from section label
      $seriesName = self::pop($conf, 'metric', $name);

      $this->builder->series($seriesName, $conf);

      if (null !== $prefix) {
        $this->builder->endPrefix();
      }
    } //end foreach
  } //end configureGraph


  /**
   * Remove and return a named value from an array.
   *
   * The input array is modified as an intentional side effect.
   *
   * @param array $arr Array to modify
   * @param mixed $key Key to pop
   * @param mixed $default Default value to return if not found
   * @return mixed Value found in array or default value if not found
   */
  static protected function pop (&$arr, $key, $default=null) {
    $ret = $default;
    if (array_key_exists($key, $arr)) {
      $ret = $arr[$key];
    }
    unset($arr[$key]);
    return $ret;
  }


  /**
   * Process an ini configuration.
   *
   * @param Graphite_GraphBuilder $builder Builder we are helping
   * @param array $ini Ini-style data set
   * @return void
   */
  static public function process ($builder, $ini) {
    $helper = new Graphite_Graph_IniHelper($builder);
    $helper->loadIniData($ini);
    $helper->configureGraph();
  } //end process

} //end Graphite_Graph_IniHelper
