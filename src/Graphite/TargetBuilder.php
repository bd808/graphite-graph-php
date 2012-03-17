<?php
/**
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

/**
 * DSL for building Graphite graph target components.
 *
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_TargetBuilder {

  /**
   * Metric manipulation functions.
   * @var array
   */
  static protected $validFunctions = array(
    'alias',
    'aliasByNode',
    'aliasSub',
    'alpha',
    'areaBetween',
    'asPercent',
    'averageAbove',
    'averageBelow',
    'averageSeries',
    'averageSeriesWithWildcards',
    'cactiStyle',
    'color',
    'constantLine',
    'cumulative',
    'currentAbove',
    'currentBelow',
    'dashed',
    'derivative',
    'diffSeries',
    'divideSeries',
    'drawAsInfinite',
    'events',
    'exclude',
    'groupByNode',
    'highestAverage',
    'highestCurrent',
    'highestMax',
    'hitcount',
    'holtWintersAberration',
    'holtWintersConfidenceBands',
    'holtWintersForecast',
    'integral',
    'keepLastValue',
    'legendValue',
    'limit',
    'lineWidth',
    'logarithm',
    'lowestAverage',
    'lowestCurrent',
    'maxSeries',
    'maximumAbove',
    'maximumBelow',
    'minSeries',
    'minimumAbove',
    'mostDeviant',
    'movingAverage',
    'movingMedian',
    'multiplySeries',
    'nPercentile',
    'nonNegativeDerivative',
    'offset',
    'randomWalkFunction',
    'rangeOfSeries',
    'removeAbovePercentile',
    'removeAboveValue',
    'removeBelowPercentile',
    'removeBelowValue',
    'scale',
    'secondYAxis',
    'sinFunction',
    'smartSummarize',
    'sortByMaxima',
    'sortByMinima',
    'stacked',
    'stdev',
    'substr',
    'sumSeries',
    'sumSeriesWithWildcards',
    'summarize',
    'threshold',
    'timeFunction',
    'timeShift',
  );

  static protected $functionAliases = array(
    'sum' => 'sumSeries',
    'avg' => 'averageSeries',
    'max' => 'maxSeries',
    'min' => 'minSeries',
    'cacti' => 'cactiStyle',
    'centile' => 'npercentile',
    'line' => 'drawAsInfinite',
    'impulse' => 'drawAsInfinite',
  );

  /**
   * Generate the target parameter for a given configuration.
   * @param array $conf Configuration
   * @return string Target parameter
   * @throws Graphite_ConfigurationException If neither series nor target is set
   * in conf
   */
  static public function generateTarget ($conf) {
    if (isset($conf['target']) && $conf['target']) {
      // explict target has been provided by the user
      return $conf['target'];
    }

    if (!isset($conf['series'])) {
      throw new Graphite_ConfigurationException(
        "metric {$name} does not have any data associated with it.");
    }

    // start from the provided series
    $target = $conf['series'];

    if (isset($conf['derivative']) && $conf['derivative']) {
      $target = "derivative({$target})";
    }

    if (isset($conf['nonnegativederivative']) && $conf['nonnegativederivative']) {
      $target = "nonNegativeDerivative({$target})";
    }

    if ((isset($conf['sumseries']) && $conf['sumseries'])||(isset($conf['sum']) && $conf['sum'])) {
      $target = "sumSeries({$target})";
    }

    if ((isset($conf['averageseries']) && $conf['averageseries'])||(isset($conf['avg']) && $conf['avg'])) {
      $target = "averageSeries({$target})";
    }

    if (isset($conf['npercentile']) && $conf['npercentile']) {
      $target = "nPercentile({$target},{$conf['npercentile']})";
    }

    if (isset($conf['scale'])) {
      $scale = $conf['scale'];
      $target = "scale({$target},{$scale})";
    }

    if (isset($conf['line']) && $conf['line']) {
      $target = "drawAsInfinite({$target})";
    }

    if (isset($conf['color'])) {
      $color = $conf['color'];
      $target = "color({$target},'{$color}')";
    }

    if (isset($conf['dashed']) && $conf['dashed']) {
      if ($conf['dashed'] == 'true') $conf['dashed'] = '5.0';
      $segs = $conf['dashed'];
      $target = "dashed({$target},{$segs})";
    }

    if (isset($conf['second_y_axis']) && $conf['second_y_axis']) {
      $target = "secondYAxis({$target})";
    }

    if (isset($conf['alias'])) {
      $alias = $conf['alias'];

    } else {
      $alias = ucfirst($name);
    }
    $alias = $alias;
    $target = "alias({$target},'{$alias}')";

    if (isset($conf['cactistyle']) && $conf['cactistyle']) {
      $target = "cactiStyle({$target})";
    }

    return $target;
  } //end generateTarget

} //end Graphite_TargetBuilder
