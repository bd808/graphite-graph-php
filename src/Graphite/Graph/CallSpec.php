<?php
/**
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

/**
 * Description of the call specification for a Graphite function.
 *
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_Graph_CallSpec {

  /**
   * Canonical name of the function.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of function arguments.
   *
   * @var array
   */
  protected $signature;

  /**
   * Sort order.
   *
   * @var int
   */
  protected $sortOrder;

  /**
   * Does this function provide an alias for the series?
   *
   * @var bool
   */
  protected $isAlias;


  /**
   * Constructor.
   *
   * @param string $name Function name
   * @param mixed $signature Argument spec
   * @param int $order Sort order
   * @param bool $alias Does this function provide an alias?
   */
  public function __construct ($name, $signature, $order, $alias) {
    $this->name = $name;
    $this->signature = $signature;
    if (is_scalar($this->signature)) {
      $this->signature = array($this->signature);
    }
    $this->order = $order;
    $this->isAlias = $alias;
  } //end __construct


  /**
   * Does this function provide an alias for the series?
   *
   * @return bol True if this function aliases the series, false otherwise.
   */
  public function isAlias () {
    return $this->isAlias;
  }


  /**
   * Does this function require arguments other than a series?
   *
   * @return bool True if the function requires arguments, false otherwise.
   */
  protected function takesArgs () {
    return (0 !== $this->signature[0]);
  }


  /**
   * Format the call for use as a target.
   *
   * @param string $series Series to apply function to
   * @param array $args Arguments to function
   * @return string Formatted function call
   */
  public function asString ($series, $args) {
    $callArgs = array($series);
    if ($this->takesArgs()) {
      foreach ($this->signature as $idx => $type) {
        switch ($type) {
          case '"':
              // quote arg
              $callArgs[] = "'{$args[$idx]}'";
              break;

          case '<':
              // arg comes before series
              array_unshift($callArgs, $args[$idx]);
              break;

          case '?':
              // optional arg
              if (isset($args[$idx]) && !is_bool($args[$idx])) {
                $callArgs[] = $args[$idx];
              }
              break;

          case '*':
              // var args
              $callArgs = array_merge($callArgs, $args);
              break;

          default:
              // verbatum arg
              $callArgs[] = $args[$idx];
              break;
        } //end switch
      } //end foreach
    } //end if

    return "{$this->name}(" . implode(',', $callArgs) . ")";
  } //end asString


  /**
   * Compare two CallSpecs for sort ordering.
   *
   * @param CallSpec $a First spec
   * @param CallSpec $b Second spec
   * @return int Less than, equal to, or greater than zero if the first
   *    argument is considered to be respectively less than, equal to, or
   *    greater than the second.
   */
  static public function cmp ($a, $b) {
    return $a->sortOrder - $b->sortOrder;
  }

} //end Graphite_Graph_CallSpec
