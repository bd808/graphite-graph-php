<?php

namespace Graphite\Graph;

/**
 * Utility for target functions.
 *
 * Functions are used to transform, combine and perform calculations on series
 * data.
 *
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 * @link http://readthedocs.org/docs/graphite/en/latest/functions.html
 */
class Functions
{
    /**
     * Metric manipulation function specifications.
     *
     * Each specification is shorthand for constructing a
     * CallSpec stored as an array. This is a tuple of:
     * (call signature, sort order, alias flag)
     *
     * @var array
     * @see CallSpec::__construct
     */
    protected static $functions = [
        'alias' => ['"', 99, 1],
        'aliasByNode' => ['#*', 50, 1],
        'aliasSub' => [['"', '"'], 50, 1],
        'alpha' => ['#', 50, 0],
        'areaBetween' => [null, 50, 0],
        'asPercent' => ['#?', 50, 0],
        'averageAbove' => ['#', 50, 0],
        'averageBelow' => ['#', 50, 0],
        'averageSeries' => [null, 50, 0],
        'averageSeriesWithWildcards' => ['#*', 50, 0],
        'cactiStyle' => [null, 100, 0],
        'color' => ['"', 98, 0],
        'cumulative' => [null, 50, 0],
        'currentAbove' => ['#', 50, 0],
        'currentBelow' => ['#', 50, 0],
        'dashed' => ['#?', 50, 0],
        'derivative' => [null, 50, 0],
        'diffSeries' => ['-*', 50, 0],
        'divideSeries' => ['-', 50, 0],
        'drawAsInfinite' => [null, 50, 0],
        'exclude' => ['"', 50, 0],
        'group' => ['-*', 50, 0],
        'groupByNode' => [['#', '"'], 50, 1],
        'highestAverage' => ['#', 50, 0],
        'highestCurrent' => ['#', 50, 0],
        'highestMax' => ['#', 50, 0],
        'hitcount' => ['"', 50, 0],
        'holtWintersAberration' => ['#?', 50, 0],
        'holtWintersConfidenceArea' => ['#?', 50, 0],
        'holtWintersConfidenceBands' => ['#?', 50, 0],
        'holtWintersForecast' => [null, 50, 0],
        'integral' => [null, 50, 0],
        'keepLastValue' => [null, 50, 0],
        'legendValue' => ['"', 50, 0],
        'limit' => ['#', 50, 0],
        'lineWidth' => ['#', 90, 0],
        'logarithm' => ['#?', 50, 0],
        'lowestAverage' => ['#', 50, 0],
        'lowestCurrent' => ['#', 50, 0],
        'maximumAbove' => ['#', 50, 0],
        'maximumBelow' => ['#', 50, 0],
        'maxSeries' => ['-*', 50, 0],
        'minimumAbove' => ['#', 50, 0],
        'minSeries' => ['-*', 50, 0],
        'mostDeviant' => ['#<', 50, 0],
        'movingAverage' => ['#', 50, 0],
        'movingMedian' => ['#', 50, 0],
        'multiplySeries' => ['-*', 50, 0],
        'nonNegativeDerivative' => ['#?', 50, 0],
        'nPercentile' => ['#', 50, 0],
        'offset' => ['#', 50, 0],
        'percentileOfSeries' => [['#', '-?'], 50, 0],
        'rangeOfSeries' => ['-*', 50, 0],
        'removeAbovePercentile' => ['#', 50, 0],
        'removeAboveValue' => ['#', 50, 0],
        'removeBelowPercentile' => ['#', 50, 0],
        'removeBelowValue' => ['#', 50, 0],
        'scale' => ['#', 75, 0],
        'scaleToSeconds' => ['#', 75, 0],
        'secondYAxis' => [null, 50, 0],
        'smartSummarize' => [['"', '"?'], 50, 0],
        'sortByMaxima' => [null, 50, 0],
        'sortByMinima' => [null, 50, 0],
        'stacked' => [null, 50, 0],
        'stdev' => [['#', '#?'], 50, 0],
        'substr' => [['#', '#?'], 50, 1],
        'summarize' => [['"', '"?', '-?'], 50, 0],
        'sumSeries' => ['-*', 50, 0],
        'sumSeriesWithWildcards' => ['#*', 50, 0],
        'timeShift' => ['"', 50, 0],
        'transformNull' => ['#?', 50, 0],
    ];
    /**
     * Function name aliases.
     *
     * @param array
     */
    protected static $aliases = [
        'avg' => 'averageSeries',
        'cacti' => 'cactiStyle',
        'centile' => 'nPercentile',
        'counter' => 'nonNegativeDerivative',
        'impulse' => 'drawAsInfinite',
        'inf' => 'drawAsInfinite',
        'max' => 'maxSeries',
        'min' => 'minSeries',
        'null' => 'transformNull',
        'sum' => 'sumSeries',
    ];

    /**
     * Find the canonical name for a function.
     *
     * The value may be an alias or it may differ in case from the true
     * function name.
     *
     * @param string $name Function to lookup
     * @return string Proper name of function or false if not found
     */
    public static function canonicalName($name)
    {
        static $lookupMap;
        if (null == $lookupMap) {
            // lazily construct the lookup map
            $tmp = [];
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
        }

        return false;
    }

    /**
     * Get the call specification for a function.
     *
     * @param string $name Function name
     * @return CallSpec Function specification or null if not
     *    found
     */
    public static function callSpec($name)
    {
        $name = self::canonicalName($name);
        if (false === $name) {
            return null;
        }
        $spec = self::$functions[$name];

        return new CallSpec($name, $spec[0], $spec[1], $spec[2]);
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
    public static function cmp($a, $b)
    {
        $aCfg = self::$functions[$a];
        $bCfg = self::$functions[$b];

        return $aCfg[1] - $bCfg[1];
    }

    /**
     * Construction disallowed.
     */
    private function __construct()
    {
        // no-op
    }
}
