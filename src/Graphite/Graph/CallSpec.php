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
   * - <var>$signature</var> is a single value or array of <i>argument
   *   specs</i>.
   * - <var>$order</var> is an integer between 1 and 99 used to sort functions
   *   when nesting.
   * - <var>$alias</var> is truthy if this function provides a legend alias.
   *
   * An argument spec is a string specifying the output type of the argument
   * and an optional modifier.
   *
   * Output type is one of:
   * - - : Verbatum
   * - " : Quoted String
   * - # : Numeric
   * - ^ : Boolean
   * - ! : Flag parameter (null to omit from call, non-null to include)
   *
   * Modifier is one of:
   * - - : Verbatum (default if omitted)
   * - ? : Argument is optional
   * - * : Variadic args
   * - < : Argument preceeds series in call
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
      // convert single arg to list of size 1
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
    return (null !== $this->signature[0]);
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
        $type = $sig[0];
        $mod = (strlen($sig) > 1)? $sig[1]: '-';
        switch ($mod) {
          case '<':
              // arg comes before series
              array_unshift($callArgs, self::format($args[$idx], $type));
              break;

          case '?':
              // optional arg
              // TODO: make sure we have an ini test for this
              if (isset($args[$idx]) && !is_bool($args[$idx])) {
                $callArgs[] =  self::format($args[$idx], $type);
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
                  sprintf(self::FMT_FORMAT_ARG, __CLASS__, $type)
                  ),
                  $args);
              $callArgs = array_merge($callArgs, $formattedArgs);
              break;

          default:
              // verbatum arg
              $callArgs[] = self::format($args[$idx], $type);
              break;
        } //end switch
      } //end foreach
    } //end if

    return "{$this->name}(" . implode(',', $callArgs) . ")";
  } //end asString


  /**
   * Format an argument as a specified type.
   *
   * <var>$type</var> is one of:
   * - - : Verbatum
   * - " : Quoted String
   * - # : Numeric
   * - ^ : Boolean
   * - ! : Flag parameter (null to omit from call, non-null to include)
   *
   * @param mixed $arg Argument to format
   * @param string $type Output type
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
          $formatted = ((bool) $arg)? 'True': 'False';
          break;

      case '!':
          // flag
          $formatted = ((bool) $arg)? 'True': null;
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
