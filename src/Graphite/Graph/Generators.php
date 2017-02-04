<?php

namespace Graphite\Graph;

/**
 * Utility for target generators.
 *
 * Generators produce source data for a series.
 *
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 * @link http://readthedocs.org/docs/graphite/en/latest/functions.html
 */
class Generators
{
    /**
     * Data generation functions.
     *
     * These are similar to the normal manipulation functions but differ in that
     * they generate a base series rather than manipulating an existing series
     * or combination of series.
     *
     * @var array
     */
    protected static $generators = [
        'constantLine' => ['#', 1, 0],
        'events' => ['"*', 1, 0],
        'randomWalkFunction' => ['"', 1, 1],
        'sinFunction' => [['"', '-?'], 1, 1],
        'threshold' => [['#', '"?', '"?'], 1, 1],
        'timeFunction' => ['"', 1, 1],
    ];
    /**
     * Function name aliases.
     *
     * @param array
     */
    protected static $aliases = [
        'line' => 'constantLine',
        'random' => 'randomWalkFunction',
        'randomWalk' => 'randomWalkFunction',
        'sin' => 'sinFunction',
        'sum' => 'sumSeries',
        'time' => 'timeFunction',
    ];

    /**
     * Find the canonical name for a generator.
     *
     * The value may be an alias or it may differ in case from the true
     * name.
     *
     * @param string $name Generator to lookup
     * @return string Proper name of generator or false if not found
     */
    public static function canonicalName($name)
    {
        static $lookupMap;
        if (null == $lookupMap) {
            // lazily construct the lookup map
            $tmp = [];
            foreach (self::$generators as $func => $conf) {
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
     * Get the call specification for a generator.
     *
     * @param string $name Generator name
     * @return CallSpec Function specification or null if not
     *    found
     */
    public static function callSpec($name)
    {
        $name = self::canonicalName($name);
        if (false === $name) {
            return null;
        }
        $spec = self::$generators[$name];

        return new CallSpec(
            $name,
            $spec[0],
            $spec[1],
            $spec[2],
            true
        );
    }

    /**
     * Construction disallowed.
     */
    private function __construct()
    {
        // no-op
    }
}
