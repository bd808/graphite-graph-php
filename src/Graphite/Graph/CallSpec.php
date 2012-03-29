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
   * Sprintf format string for creating a lambda function to format a list of
   * arguments.
   *
   * @var string
   */
  const FMT_FORMAT_ARG = 'return %s::format($a, "%s");';


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
   * @todo Document argument spec
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
      foreach ($this->signature as $idx => $sig) {
        switch ($sig[0]) {
          case '<':
              // arg comes before series
              array_unshift($callArgs, self::format($args[$idx], $sig[1]));
              break;

          case '?':
              // optional arg
              // TODO: make sure we have an ini test for this
              if (isset($args[$idx]) && !is_bool($args[$idx])) {
                $callArgs[] =  self::format($args[$idx], $sig[1]);
              }
              break;

          case '*':
              // var args
              // TODO: check to see if there is only True to wrap the
              // current series.

              // format all of the remaining args and append to call
              // this would be so much prettier in php 5.3
              $formattedArgs =  array_map(create_function(
                  '$a',
                  sprintf(self::FMT_FORMAT_ARG, __CLASS__, $sig[1])
                  ),
                  $args);
              $callArgs = array_merge($callArgs, $formattedArgs);
              break;

          default:
              // verbatum arg
              $callArgs[] = self::format($args[$idx], $sig[1]);
              break;
        } //end switch
      } //end foreach
    } //end if

    return "{$this->name}(" . implode(',', $callArgs) . ")";
  } //end asString


  /**
   * Format an argument as a specified type.
   *
   * @param mixed $arg Argument to format
   * @param string $type Type: " = string, # = number, ^ = bool
   * @return mixed Formatted argument
   */
  static public function format ($arg, $type) {
    $formatted = $arg;
    switch ($type) {
      case '"':
          // quoted string
          $formatted = "'{$arg}'";
          break;

      case '#':
          // number
          if (stripos(strtoupper((string) $arg), 'E')) {
            // graphite doesn't like floats in scientific notation
            $arg = sprintf('%.8f', $arg);
          }
          $formatted = $arg;
          break;

      case '^':
          // boolean
          $formatted = ($arg)? 'True': 'False';
          break;
    } //end switch

    return $formatted;
  } //end format


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
