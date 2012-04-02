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
  protected $order;

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
  public function __construct ($name, $signature, $order=50, $alias=false) {
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
   * @return bool True if this function aliases the series, false otherwise.
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
   * How many args are required for this function?
   *
   * @return int Required argument count
   */
  protected function requiredArgs () {
    $req = 0;
    if ($this->takesArgs()) {
      foreach ($this->signature as $argDesc) {
        if (!self::argIsOptional($argDesc)) {
          $req += 1;
        }
      }
    }
    return $req;
  } //end requiredArgs


  /**
   * How many args are possible for this function?
   *
   * @return int Possible argument count
   */
  protected function maxArgs () {
    $args = 0;
    if ($this->takesArgs()) {
      foreach ($this->signature as $argDesc) {
        if (self::argIsWildcard($argDesc)) {
          $args = PHP_INT_MAX;
          break;
        }
        $args += 1;
      }
    }
    return $args;
  } //end maxArgs


  /**
   * Does the given argument have modifiers?
   *
   * @param string $arg Argument description to check
   * @return bool True if wildcard, false otherwise
   */
  static protected function argHasMods ($arg) {
    return null !== $arg && strlen($arg) > 1;
  }


  /**
   * Is the given argument a wildcard?
   *
   * @param string $arg Argument description to check
   * @return bool True if wildcard, false otherwise
   */
  static protected function argIsWildcard ($arg) {
    return self::argHasMods($arg) && '*' === $arg[1];
  }


  /**
   * Is the given argument optional?
   *
   * @param string $arg Argument description to check
   * @return bool True if optional, false otherwise
   */
  static protected function argIsOptional ($arg) {
    return self::argHasMods($arg) && '?' === $arg[1];
  }


  /**
   * Format the call for use as a target.
   *
   * Arguments to the function can be provided as an array or using a varadic
   * argument style.
   * <code>
   * // args provided as array
   * $out = $callSpec->asString('*', array(1, 2, 3));
   *
   * // varadic args
   * $out = $callSpec->asString('*', 1, 2, 3);
   * </code>
   *
   * @param string $series Series to apply function to
   * @param array $args Arguments to function
   * @return string Formatted function call
   */
  public function asString ($series, $args /*, ...*/) {
    // The default first arg to any function is the current series
    $callArgs = array($series);

    // check for varadic call
    if (func_num_args() > 1 && !is_array($args)) {
      $args = func_get_args();
      // shift the base series off
      array_shift($args);
    }

    if ($this->maxArgs() > 1 && count($args) == 1 && is_string($args[0])) {
      // single string argument given to a function that accepts multiple
      // arguments. See if we can split it on commas and get more input.
      // TODO: this could be smarter (escape comma, don't split quoted
      // strings)
      $parts = explode(',', $args[0], $this->maxArgs());
      if (count($parts) > 1) {
        $args = array_map('trim', $parts);
      }
    }

    if ($this->takesArgs()) {
      foreach ($this->signature as $argDesc) {
        $type = $argDesc[0];
        $mod = (strlen($argDesc) > 1)? $argDesc[1]: '-';

        switch ($mod) {
          case '<':
              // arg comes before series
              $arg = array_shift($args);
              array_unshift($callArgs, self::format($arg, $type));
              break;

          case '?':
              // optional arg
              $arg = array_shift($args);
              if (null !== $arg && '' !== $arg && !is_bool($arg)) {
                $callArgs[] =  self::format($arg, $type);
              }
              break;

          case '*':
              // var args
              if (1 == count($args) && (
                  '1' === $args[0] || true === $args[0])) {
                // empty varargs from ini
                // no-op, we alread added series

              } else {
                // format all of the remaining args and append to call
                // this would be so much prettier in php 5.3
                $formattedArgs =  array_map(create_function(
                    '$a',
                    sprintf(self::FMT_FORMAT_ARG, __CLASS__, $type)
                  ),
                  $args);
                $callArgs = array_merge($callArgs, $formattedArgs);

                // empty out args
                $args = array();
              }
              break;

          default:
              // verbatum arg
              $arg = array_shift($args);
              $callArgs[] = self::format($arg, $type);
              break;
        } //end switch
      } //end foreach
    } //end if

    // throw out any nulls in $callArgs
    $callArgs = array_filter($callArgs,
        create_function('$a', 'return null !== $a;'));

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
          if (is_bool($arg)) {
            // booleans turn into null args
            $formatted = null;
          } else {
            // strip any enclosing single/double quotes
            $arg = preg_replace('/^(\'|")?(.*)(?(1)\1|)$/', '$2', $arg);

            // quote with single quotes
            $formatted = "'{$arg}'";
          }
          break;

      case '#':
          // number
          if (is_numeric($arg)) {
            if (stripos(strtoupper((string) $arg), 'E')) {
              // graphite doesn't like floats in scientific notation
              $arg = sprintf('%.8f', $arg);
            }

          } else {
            $arg = null;
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
    return $a->order - $b->order;
  }

} //end Graphite_Graph_CallSpec
