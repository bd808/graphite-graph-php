<?php

namespace Graphite\Graph;

/**
 * Description of the call specification for a Graphite function.
 *
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class CallSpec
{
    /**
     * Sprintf format string for creating a lambda function to format a list of
     * arguments.
     *
     * @var string
     */
    const FMT_FORMAT_ARG = 'return %s::format($a, \'%s\');';
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
     * Does this function wrap a series?
     *
     * @var bool
     */
    protected $usesSeries;

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
     * @param bool $generator Is this function a generator?
     */
    public function __construct(
        $name,
        $signature,
        $order = 50,
        $alias = false,
        $generator = false
    ) {
        $this->name = $name;
        $this->signature = $signature;
        if (is_scalar($this->signature)) {
            // convert single arg to list of size 1
            $this->signature = [$this->signature];
        }
        $this->order = $order;
        $this->isAlias = $alias;
        $this->usesSeries = !((bool) $generator);
    }

    /**
     * Does this function provide an alias for the series?
     *
     * @return bool True if this function aliases the series, false otherwise.
     */
    public function isAlias()
    {
        return $this->isAlias;
    }

    /**
     * Does this function require arguments other than a series?
     *
     * @return bool True if the function requires arguments, false otherwise.
     */
    public function takesArgs()
    {
        return (null !== $this->signature[0]);
    }

    /**
     * How many args are required for this function?
     *
     * @return int Required argument count
     */
    public function requiredArgs()
    {
        $req = 0;
        if ($this->takesArgs()) {
            foreach ($this->signature as $argDesc) {
                if (!self::argIsOptional($argDesc)) {
                    $req += 1;
                }
            }
        }

        return $req;
    }

    /**
     * How many args are possible for this function?
     *
     * @return int Possible argument count
     */
    public function maxArgs()
    {
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
    }

    /**
     * Get the Nth argument spec.
     *
     * @param int $idx Argument index
     * @return string Argument specification
     */
    public function getArg($idx)
    {
        return $this->signature[$idx];
    }

    /**
     * Does the given argument have modifiers?
     *
     * @param string $arg Argument description to check
     * @return bool True if wildcard, false otherwise
     */
    public static function argHasMods($arg)
    {
        return null !== $arg && mb_strlen($arg) > 1;
    }

    /**
     * Is the given argument a wildcard?
     *
     * @param string $arg Argument description to check
     * @return bool True if wildcard, false otherwise
     */
    public static function argIsWildcard($arg)
    {
        return self::argHasMods($arg) && '*' === $arg[1];
    }

    /**
     * Is the given argument optional?
     *
     * @param string $arg Argument description to check
     * @return bool True if optional, false otherwise
     */
    public static function argIsOptional($arg)
    {
        return self::argHasMods($arg) && '?' === $arg[1];
    }

    /**
     * Is the given argument optional?
     *
     * @param string $arg Argument description to check
     * @return bool True if optional, false otherwise
     */
    public static function argIsString($arg)
    {
        return null !== $arg && mb_strlen($arg) > 0 && '"' === $arg[0];
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
     * If this call is a generator the $series argument will be ignored.
     *
     * @param string $series Series to apply function to
     * @param array $args Arguments to function
     * @return string Formatted function call
     */
    public function asString($series, $args /*, ...*/)
    {
        $callArgs = [];
        if ($this->usesSeries) {
            // The default first arg to any function is the current series
            $callArgs[] = $series;
        }

        // check for varadic call
        if (func_num_args() > 1 && !is_array($args)) {
            $args = func_get_args();
            // shift the base series off
            array_shift($args);
        }

        if ($this->maxArgs() > 1 && count($args) == 1 && is_string($args[0])) {
            // single string argument given to a function that accepts multiple
            // arguments. See if we can split it on commas and get more input.
            if (false !== mb_strpos($args[0], ',')) {
                $parts = self::parseArgString($args[0]);
                if (count($parts) > 1) {
                    $args = array_map('trim', $parts);
                }
            }
        }

        if ($this->takesArgs()) {
            foreach ($this->signature as $argDesc) {
                $type = $argDesc[0];
                $mod = (mb_strlen($argDesc) > 1) ? $argDesc[1] : '-';

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
                            $callArgs[] = self::format($arg, $type);
                        }
                        break;
                    case '*':
                        // var args
                        if (1 == count($args) && (
                                '1' === $args[0] || true === $args[0])
                        ) {
                            // empty varargs from ini
                            // no-op, we alread added series
                        } else {
                            // format all of the remaining args and append to call
                            // this would be so much prettier in php 5.3
                            $formattedArgs = array_map(
                                create_function(
                                    '$a',
                                    sprintf(self::FMT_FORMAT_ARG, __CLASS__, $type)
                                ),
                                $args
                            );
                            $callArgs = array_merge($callArgs, $formattedArgs);

                            // empty out args
                            $args = [];
                        }
                        break;
                    default:
                        // verbatum arg
                        $arg = array_shift($args);
                        $callArgs[] = self::format($arg, $type);
                        break;
                }
            }
        }

        // throw out any nulls in $callArgs
        $callArgs = array_filter(
            $callArgs,
            create_function('$a', 'return null !== $a;')
        );

        return "{$this->name}(" . implode(',', $callArgs) . ')';
    }

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
    public static function format($arg, $type)
    {
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
                    if (mb_stripos(mb_strtoupper((string) $arg), 'E')) {
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
                $formatted = ((bool) $arg) ? 'True' : 'False';
                break;
            case '!':
                // flag
                $formatted = ((bool) $arg) ? 'True' : null;
                break;
        }

        return $formatted;
    }

    /**
     * Compare two CallSpecs for sort ordering.
     *
     * @param CallSpec $a First spec
     * @param CallSpec $b Second spec
     * @return int Less than, equal to, or greater than zero if the first
     *    argument is considered to be respectively less than, equal to, or
     *    greater than the second.
     */
    public static function cmp($a, $b)
    {
        return $a->order - $b->order;
    }

    /**
     * Parse a string into an array of discrete arguments.
     *
     * String is split on commas but recognizes single and double quoted
     * strings.
     *
     * @param string $str Argument string to parse
     * @return array Split arguments
     */
    public static function parseArgString($str)
    {
        // escapable chars that we will pay special attention to
        static $escapable = ['"', ',', "'"];

        $args = [];
        $token = '';
        for ($ptr = 0, $end = mb_strlen($str); $ptr < $end; $ptr++) {
            $next = $str[$ptr];
            switch ($next) {
                case '\\':
                    // found an escape marker
                    // decide if it should be emitted or used to escape special
                    // meaning of next char in the stream.
                    $peek = $str[$ptr + 1];
                    if (in_array($peek, $escapable, true)) {
                        // add escaped char to our token and advance the stream pointer
                        $token .= $peek;
                        $ptr++;
                    } else {
                        $token .= $next;
                    }
                    break;
                case '\'':
                case '"':
                    // look for next matching quote
                    $match = self::findNextOccurrance($str, $next, $ptr);
                    if (false === $match) {
                        // no match found so add the char to the token
                        $token .= $next;
                    } else {
                        // grab content between quotes
                        $chunk = mb_substr($str, $ptr + 1, $match - $ptr - 1);

                        // remove any escaped quotes and add to token
                        $token .= str_replace("\\{$next}", $next, $chunk);

                        // advance ptr past closing quote
                        $ptr = $match;
                    }
                    break;
                case ',':
                    // comma closes current token.
                    $args[] = $token;
                    $token = '';
                    break;
                default:
                    $token .= $next;
                    break;
            }
        }

        // add final token to args
        $args[] = $token;

        return $args;
    }

    /**
     * Find the next occurance of a character in a source string that isn't
     * preceeded by an escape marker.
     *
     * @param string $str Source string
     * @param string $char Character to scan for
     * @param int $start Offset of initial $char to match
     * @return int Offset of next unescaped $char or false if not found
     */
    public static function findNextOccurrance($str, $char, $start)
    {
        $n = mb_strpos($str, $char, $start + 1);
        if (false === $n) {
            return $n;
        }
        if ('\\' === $str[$n - 1]) {
            return self::findNextOccurrance($str, $char, $n);
        }

        return $n;
    }
}
