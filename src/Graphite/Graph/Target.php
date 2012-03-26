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
   * Metric manipulation functions.
   *
   * @var array
   */
  static protected $validFunctions = array(
    // args, priority
    // args   == 0: no args
    //        == 1..N: verbatum args
    //        == string: format spec
    //          ~ '"': quote arg
    //          ~ '?': optional arg
    //          ~ '<': arg comes before series
    //          ~ '*': var args (one or more)
    //        == array(): positional args as above
    'alias' => array('"', 99),
    'aliasByNode' => array('*', 50),
    'aliasSub' => array(array('"','"'), 50),
    'alpha' => array(1, 50),
    'areaBetween' => array(0, 50),
    'asPercent' => array('?', 50),
    'averageAbove' => array(1, 50),
    'averageBelow' => array(1, 50),
    'averageSeries' => array(0, 50),
    'averageSeriesWithWildcards' => array('*', 50),
    'cactiStyle' => array(0, 100),
    'color' => array('"', 98),
    'cumulative' => array(0, 50),
    'currentAbove' => array(1, 50),
    'currentBelow' => array(1, 50),
    'dashed' => array('?', 50),
    'derivative' => array(0, 50),
    'diffSeries' => array('*', 50),
    'divideSeries' => array(1, 50),
    'drawAsInfinite' => array(0, 50),
    'events' => array(0, 1),
    'exclude' => array('"', 50),
    'groupByNode' => array(array(1, '"'), 50),
    'highestAverage' => array(1, 50),
    'highestCurrent' => array(1, 50),
    'highestMax' => array(1, 50),
    'hitcount' => array('"', 50),
    'holtWintersAberration' => array('?', 50),
    'holtWintersConfidenceBands' => array('?', 50),
    'holtWintersForecast' => array(0, 50),
    'integral' => array(0, 50),
    'keepLastValue' => array(0, 50),
    'legendValue' => array('"', 50),
    'limit' => array(1, 50),
    'lineWidth' => array(1, 90),
    'logarithm' => array('?', 50),
    'lowestAverage' => array(1, 50),
    'lowestCurrent' => array(1, 50),
    'maxSeries' => array(0, 50),
    'maximumAbove' => array(1, 50),
    'maximumBelow' => array(1, 50),
    'minSeries' => array(0, 50),
    'minimumAbove' => array(1, 50),
    'mostDeviant' => array('<', 50),
    'movingAverage' => array(1, 50),
    'movingMedian' => array(1, 50),
    'multiplySeries' => array('*', 50),
    'nPercentile' => array(1, 50),
    'nonNegativeDerivative' => array('?', 50),
    'offset' => array(1, 50),
    'percentileOfSeries' => array(array(1, '?'), 50),
    'rangeOfSeries' => array(0, 50),
    'removeAbovePercentile' => array(1, 50),
    'removeAboveValue' => array(1, 50),
    'removeBelowPercentile' => array(1, 50),
    'removeBelowValue' => array(1, 50),
    'scale' => array(1, 75),
    'secondYAxis' => array(0, 50),
    'smartSummarize' => array(array('"', '"?'), 50),
    'sortByMaxima' => array(0, 50),
    'sortByMinima' => array(0, 50),
    'stacked' => array(0, 50),
    'stdev' => array(array(1, '?'), 50),
    'substr' => array(array(1, '?'), 50),
    'sumSeries' => array('*', 50),
    'sumSeriesWithWildcards' => array('*', 50),
    'summarize' => array(array('"', '"?', '?'), 50),
    'timeShift' => array('"', 50),
  );

  /**
   * @var array
   */
  static protected $generators = array(
    'constantLine' => array('"', 1),
    'randomWalkFunction' => array('"', 1),
    'sinFunction' => array(array('"', '?'), 1),
    'threshold' => array(array(1, '"', '"'), 1),
    'timeFunction' => array('"', 1),
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
  );


  /**
   * Compare two function names for sort ordering based on priority.
   *
   * @param string $a First function name
   * @param string $b Second function name
   * @return an integer less than, equal to, or greater than zero if the first
   *    argument is considered to be respectively less than, equal to, or
   *    greater than the second.
   */
  static public function functionPriorityCmp ($a, $b) {
    $aCfg = self::$validFunctions[$a];
    $bCfg = self::$validFunctions[$b];
    return $aCfg[1] - $bCfg[1];
  }


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
      $funcName = self::canonicalName($key);
      if ($funcName) {
        $funcs[$funcName] = $args;
      }
    }
    // sort the found functions by priority
    uksort($funcs, array(__CLASS__, 'functionPriorityCmp'));

    // start from the provided series
    $target = $conf['series'];

    // build up target string
    foreach ($funcs as $name => $args) {
      list($spec, $priority) = self::$validFunctions[$name];
      if (is_scalar($args)) {
        $args = array($args);
      }

      if (0 === $spec) {
        // no args to function
        $target = "{$name}({$target})";

      } else {
        if (is_scalar($spec)) {
          $spec = array($spec);
        }

        $tArgs = array($target);
        foreach ($spec as $idx => $type) {
          switch ($type) {
            case '"':
              // quote arg
              $tArgs[] = "'{$args[$idx]}'";
              break;

            case '<':
              // arg comes before series
              array_unshift($tArgs, $args[$idx]);
              break;

            case '?':
              // optional arg
              if (isset($args[$idx]) && !is_bool($args[$idx])) {
                $tArgs[] = $args[$idx];
              }
              break;

            case '*':
              // var args
              $tArgs = array_merge($targs, $args);
              break;

            default:
              // verbatum arg
              $tArgs[] = $args[$idx];
              break;
          } //end switch
        } //end foreach $spec

        $target = "{$name}(" . implode(',', $tArgs) . ")";
      }
    } //end foreach $funcs

    return $target;
  } //end generateTarget


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

} //end Graphite_Graph_Target
