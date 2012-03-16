<?php
/**
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

/**
 * Ini file parser.
 *
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_IniParser {

  /**
   * Ini file contents.
   * @var string
   */
  protected $iniString;

  /**
   * Substitution variables.
   * @var array
   */
  protected $vars;


  /**
   * Constructor.
   *
   * @param string $file File path
   * @param array $vars Substitution variables
   */
  protected function __construct ($file, $vars) {
    $this->iniString = file_get_contents($file);
    $this->vars = $vars;
  }


  /**
   * Parse and expand the ini file.
   * @return array Ini contents
   */
  protected function expand () {
    // TODO: document substitution syntax
    // TODO: document php ini weirdness
    // http://php.net/manual/en/function.parse-ini-string.php

    $expanded = preg_replace_callback(
      '/{{(?P<key>.*?)}}/', array($this, 'substitute'), $this->iniString);

    return parse_ini_string($expanded, true);
  } //end expand


  /**
   * Substitute a match with data found in our variables.
   * @param array $match Regex matches
   * @return string Variable value or original key if no match found
   */
  public function substitute ($match) {
    $key = $match['key'];
    if (isset($this->vars[$key])) {
      return $this->vars[$key];
    } else {
      return $key;
    }
  } //end substitute


  /**
   * Parse an ini file and return an array of it's contents.
   *
   * @param string $file Path to ini file
   * @param array $vars Variable values to substitue in the file
   * @return array Parsed ini data
   */
  static public function parse ($file, $vars=null) {
    if (null === $vars) {
      return parse_ini_file($file, true);
    }

    $p = new Graphite_IniParser($file, $vars);
    return $p->expand();
  }
} //end Graphite_IniParser
