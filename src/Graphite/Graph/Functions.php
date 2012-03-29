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
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_Graph_Functions {

  /**
   * Metric manipulation functions.
   *
   * @var array
   */
  static protected $validFunctions = array(
    // args, priority, isAlias
    // args   == 0: no args
    //        == 1..N: verbatum args
    //        == string: format spec
    //          ~ '"': quote arg
    //          ~ '?': optional arg
    //          ~ '<': arg comes before series
    //          ~ '*': var args (one or more)
    //        == array(): positional args as above
    'alias' => array('"', 99, true),
    'aliasByNode' => array('*', 50, true),
    'aliasSub' => array(array('"','"'), 50, true),
    'alpha' => array(1, 50, false),
    'areaBetween' => array(0, 50, false),
    'asPercent' => array('?', 50, false),
    'averageAbove' => array(1, 50, false),
    'averageBelow' => array(1, 50, false),
    'averageSeries' => array(0, 50, false),
    'averageSeriesWithWildcards' => array('*', 50, false),
    'cactiStyle' => array(0, 100, false),
    'color' => array('"', 98, false),
    'cumulative' => array(0, 50, false),
    'currentAbove' => array(1, 50, false),
    'currentBelow' => array(1, 50, false),
    'dashed' => array('?', 50, false),
    'derivative' => array(0, 50, false),
    'diffSeries' => array('*', 50, false),
    'divideSeries' => array(1, 50, false),
    'drawAsInfinite' => array(0, 50, false),
    'events' => array(0, 1, false),
    'exclude' => array('"', 50, false),
    'groupByNode' => array(array(1, '"'), 50, true),
    'highestAverage' => array(1, 50, false),
    'highestCurrent' => array(1, 50, false),
    'highestMax' => array(1, 50, false),
    'hitcount' => array('"', 50, false),
    'holtWintersAberration' => array('?', 50, false),
    'holtWintersConfidenceBands' => array('?', 50, false),
    'holtWintersForecast' => array(0, 50, false),
    'integral' => array(0, 50, false),
    'keepLastValue' => array(0, 50, false),
    'legendValue' => array('"', 50, false),
    'limit' => array(1, 50, false),
    'lineWidth' => array(1, 90, false),
    'logarithm' => array('?', 50, false),
    'lowestAverage' => array(1, 50, false),
    'lowestCurrent' => array(1, 50, false),
    'maxSeries' => array(0, 50, false),
    'maximumAbove' => array(1, 50, false),
    'maximumBelow' => array(1, 50, false),
    'minSeries' => array(0, 50, false),
    'minimumAbove' => array(1, 50, false),
    'mostDeviant' => array('<', 50, false),
    'movingAverage' => array(1, 50, false),
    'movingMedian' => array(1, 50, false),
    'multiplySeries' => array('*', 50, false),
    'nPercentile' => array(1, 50, false),
    'nonNegativeDerivative' => array('?', 50, false),
    'offset' => array(1, 50, false),
    'percentileOfSeries' => array(array(1, '?'), 50, false),
    'rangeOfSeries' => array(0, 50, false),
    'removeAbovePercentile' => array(1, 50, false),
    'removeAboveValue' => array(1, 50, false),
    'removeBelowPercentile' => array(1, 50, false),
    'removeBelowValue' => array(1, 50, false),
    'scale' => array(1, 75, false),
    'secondYAxis' => array(0, 50, false),
    'smartSummarize' => array(array('"', '"?'), 50, false),
    'sortByMaxima' => array(0, 50, false),
    'sortByMinima' => array(0, 50, false),
    'stacked' => array(0, 50, false),
    'stdev' => array(array(1, '?'), 50, false),
    'substr' => array(array(1, '?'), 50, true),
    'sumSeries' => array('*', 50, false),
    'sumSeriesWithWildcards' => array('*', 50, false),
    'summarize' => array(array('"', '"?', '?'), 50, false),
    'timeShift' => array('"', 50, false),
  );

  /**
   * @var array
   */
  static protected $generators = array(
    'constantLine' => array('"', 1, false),
    'randomWalkFunction' => array('"', 1, false),
    'sinFunction' => array(array('"', '?'), 1, false),
    'threshold' => array(array(1, '"', '"'), 1, true),
    'timeFunction' => array('"', 1, false),
  );


  /**
   * Function name aliases.
   *
   * @param array
   */
  static protected $functionAliases = array(
    'sum' => 'sumSeries',
    'avg' => 'averageSeries',
    'max' => 'maxSeries',
    'min' => 'minSeries',
    'cacti' => 'cactiStyle',
    'centile' => 'npercentile',
    'line' => 'drawAsInfinite',
    'impulse' => 'drawAsInfinite',
    'time' => 'timeFunction',
    'sin' => 'sinFunction',
    'randomWalk' => 'randomWalkFunction',
    'group' => 'groupByNode',
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
      foreach (self::$validFunctions as $func => $conf) {
        $tmp[mb_strtolower($func)] = $func;
      }
      foreach (self::$functionAliases as $alias => $func) {
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
    $spec = self::$validFunctions[$name];
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
    $aCfg = self::$validFunctions[$a];
    $bCfg = self::$validFunctions[$b];
    return $aCfg[1] - $bCfg[1];
  }


  /**
   * Construction disallowed.
   */
  private function __construct () {
    // no-op
  }

} //end Graphite_Graph_Functions
