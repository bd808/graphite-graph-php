<?php
/**
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

/**
 * Utility for target functions.
 *
 * Functions are used to transform, combine and perform calculations on series
 * data.
 *
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 * @link http://readthedocs.org/docs/graphite/en/latest/functions.html
 */
class Graphite_Graph_Functions {

  /**
   * Metric manipulation function specifications.
   *
   * Each specification is shorthand for constructing a
   * Graphite_Graph_CallSpec stored as an array. This is a tuple of:
   * (call signature, sort order, alias flag)
   *
   * @var array
   * @see Graphite_Graph_CallSpec::__construct
   */
  static protected $functions = array(
      'alias'                      => array('"', 99, 1),
      'aliasByNode'                => array('#*', 50, 1),
      'aliasSub'                   => array(array('"', '"'), 50, 1),
      'alpha'                      => array('#', 50, 0),
      'areaBetween'                => array(null, 50, 0),
      'asPercent'                  => array('#?', 50, 0),
      'averageAbove'               => array('#', 50, 0),
      'averageBelow'               => array('#', 50, 0),
      'averageSeries'              => array(null, 50, 0),
      'averageSeriesWithWildcards' => array('#*', 50, 0),
      'cactiStyle'                 => array(null, 100, 0),
      'color'                      => array('"', 98, 0),
      'cumulative'                 => array(null, 50, 0),
      'currentAbove'               => array('#', 50, 0),
      'currentBelow'               => array('#', 50, 0),
      'dashed'                     => array('#?', 50, 0),
      'derivative'                 => array(null, 50, 0),
      'diffSeries'                 => array('-*', 50, 0),
      'divideSeries'               => array('-', 50, 0),
      'drawAsInfinite'             => array(null, 50, 0),
      'events'                     => array('"*', 1, 0),
      'exclude'                    => array('"', 50, 0),
      'group'                      => array('-*', 50, 0),
      'groupByNode'                => array(array('#', '"'), 50, 1),
      'highestAverage'             => array('#', 50, 0),
      'highestCurrent'             => array('#', 50, 0),
      'highestMax'                 => array('#', 50, 0),
      'hitcount'                   => array('"', 50, 0),
      'holtWintersAberration'      => array('#?', 50, 0),
      'holtWintersConfidenceArea'  => array('#?', 50, 0),
      'holtWintersConfidenceBands' => array('#?', 50, 0),
      'holtWintersForecast'        => array(null, 50, 0),
      'integral'                   => array(null, 50, 0),
      'keepLastValue'              => array(null, 50, 0),
      'legendValue'                => array('"', 50, 0),
      'limit'                      => array('#', 50, 0),
      'lineWidth'                  => array('#', 90, 0),
      'logarithm'                  => array('#?', 50, 0),
      'lowestAverage'              => array('#', 50, 0),
      'lowestCurrent'              => array('#', 50, 0),
      'maximumAbove'               => array('#', 50, 0),
      'maximumBelow'               => array('#', 50, 0),
      'maxSeries'                  => array('-*', 50, 0),
      'minimumAbove'               => array('#', 50, 0),
      'minSeries'                  => array('-*', 50, 0),
      'mostDeviant'                => array('#<', 50, 0),
      'movingAverage'              => array('#', 50, 0),
      'movingMedian'               => array('#', 50, 0),
      'multiplySeries'             => array('-*', 50, 0),
      'nonNegativeDerivative'      => array('#?', 50, 0),
      'nPercentile'                => array('#', 50, 0),
      'offset'                     => array('#', 50, 0),
      'percentileOfSeries'         => array(array('#', '-?'), 50, 0),
      'rangeOfSeries'              => array('-*', 50, 0),
      'removeAbovePercentile'      => array('#', 50, 0),
      'removeAboveValue'           => array('#', 50, 0),
      'removeBelowPercentile'      => array('#', 50, 0),
      'removeBelowValue'           => array('#', 50, 0),
      'scale'                      => array('#', 75, 0),
      'scaleToSeconds'             => array('#', 75, 0),
      'secondYAxis'                => array(null, 50, 0),
      'smartSummarize'             => array(array('"', '"?'), 50, 0),
      'sortByMaxima'               => array(null, 50, 0),
      'sortByMinima'               => array(null, 50, 0),
      'stacked'                    => array(null, 50, 0),
      'stdev'                      => array(array('#', '#?'), 50, 0),
      'substr'                     => array(array('#', '#?'), 50, 1),
      'summarize'                  => array(array('"', '"?', '-?'), 50, 0),
      'sumSeries'                  => array('-*', 50, 0),
      'sumSeriesWithWildcards'     => array('#*', 50, 0),
      'timeShift'                  => array('"', 50, 0),
      'transformNull'              => array('#?', 50, 0),
    );


  /**
   * Data generation function.
   *
   * These are similar to the normal manipulation functions but differ in that
   * they generate a base series rather than manipulating an existing series
   * or combination of series.
   *
   * @var array
   */
  static protected $generators = array(
      'constantLine'       => array('#', 1, false),
      'randomWalkFunction' => array('"', 1, false),
      'sinFunction'        => array(array('"', '-?'), 1, false),
      'threshold'          => array(array('#', '"?', '"?'), 1, true),
      'timeFunction'       => array('"', 1, false),
    );


  /**
   * Function name aliases.
   *
   * @param array
   */
  static protected $aliases = array(
      'avg'        => 'averageSeries',
      'cacti'      => 'cactiStyle',
      'centile'    => 'nPercentile',
      'counter'    => 'nonNegativeDerivative',
      'impulse'    => 'drawAsInfinite',
      'line'       => 'drawAsInfinite',
      'max'        => 'maxSeries',
      'min'        => 'minSeries',
      'null'       => 'transformNull',
      'randomWalk' => 'randomWalkFunction',
      'sin'        => 'sinFunction',
      'sum'        => 'sumSeries',
      'time'       => 'timeFunction',
    );


  /**
   * Find the canonical name for a target.
   *
   * The value may be an alias or it may differ in case from the true
   * target name.
   *
   * @param string $name Target to lookup
   * @return string Proper name of target or false if not found
   */
  static public function canonicalName ($name) {
    static $lookupMap;
    if (null == $lookupMap) {
      // lazily construct the lookup map
      $tmp = array();
      foreach (self::$functions as $func => $conf) {
        $tmp[mb_strtolower($func)] = $func;
      }
      foreach (self::$aliases as $alias => $func) {
        $tmp[mb_strtolower($alias)] = $func;
      }
      $lookupMap = $tmp;
    }

    // convert to lowercase and strip "delimiter" characters
    $name = strtr(mb_strtolower($name), '_.-', '');
    if (array_key_exists($name, $lookupMap)) {
      return $lookupMap[$name];
    } else {
      return false;
    }
  } //end canonicalName


  /**
   * Get the call specification for a function.
   *
   * @param string $name Function name
   * @return Graphite_Graph_CallSpec Function specification or null if not
   *    found
   */
  static public function callSpec ($name) {
    $name = self::canonicalName($name);
    if (false === $name) {
      return null;
    }
    $spec = self::$functions[$name];
    return new Graphite_Graph_CallSpec($name, $spec[0], $spec[1], $spec[2]);
  }


  /**
   * Compare two function names for sort ordering based on priority.
   *
   * @param string $a First function name
   * @param string $b Second function name
   * @return int Less than, equal to, or greater than zero if the first
   *    argument is considered to be respectively less than, equal to, or
   *    greater than the second.
   */
  static public function cmp ($a, $b) {
    $aCfg = self::$functions[$a];
    $bCfg = self::$functions[$b];
    return $aCfg[1] - $bCfg[1];
  }


  /**
   * Construction disallowed.
   */
  private function __construct () {
    // no-op
  }

} //end Graphite_Graph_Functions
