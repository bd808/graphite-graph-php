<?php
/**
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */


/**
 * Simple autoloader that converts underscore and backslash to directories.
 * @param string $class Class to load
 * @return void
 */
function graphite_graph_autoload ($class) {
  require dirname(__FILE__) . '/' . strtr($class, '_\\', '//') . '.php';
}

spl_autoload_register('graphite_graph_autoload');
