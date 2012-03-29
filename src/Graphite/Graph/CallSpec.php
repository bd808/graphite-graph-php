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
  public $name;

  /**
   * Description of function arguments.
   *
   * @var array
   */
  public $signature;

  /**
   * Sort order.
   *
   * @var int
   */
  public $sortOrder;

  /**
   * Does this function provide an alias for the series?
   *
   * @var bool
   */
  public $isAlias;


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

  public function takesArgs () {
    return (0 !== $this->getArg(0));
  }

  public function formatArgs ($args) {
    $tArgs = array();
    if ($this->takesArgs()) {
      foreach ($this->signature as $idx => $type) {
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
              $tArgs = array_merge($tArgs, $args);
              break;

          default:
              // verbatum arg
              $tArgs[] = $args[$idx];
              break;
        } //end switch
      } //end foreach
    } //end if

    return $tArgs;
  } //end formatArgs

  public function getArg ($idx) {
    return (isset($this->signature[$idx]))? $this->signature[$idx]: null;
  }

  public function arg0 () {
    return $this->getArg(0);
  }
  public function arg1 () {
    return $this->getArg(1);
  }
  public function arg2 () {
    return $this->getArg(0);
  }

  /**
   * Handle attempts to read from non-existant members.
   *
   * @param string $name Member name
   * @return mixed Setting value or null if not found
   */
  public function __get ($name) {
    echo __METHOD__ . "([{$name}])\n";
    if (method_exists($this, $name)) {
      echo "aliasing...\n";
      var_dump($this->$name());
      return $this->$name();
    }
    return null;
  } //end __get


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
