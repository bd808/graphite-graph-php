<?php

namespace Graphite;

/**
 * Ini file parser.
 *
 * Utility class which performs Mustache-like template expansion on a file
 * before parsing it into a PHP array structure.
 *
 * Tokens in the ini file are strings that start with `{{` and end wtih `}}`.
 * The exact text inside the delimiters becomes the token. These tokens can
 * be replaced with alternate content by passing an array of replacement
 * values to IniParser::parse(). The array should use the exact token
 * text as the key and the replacement value as the value for each substition
 * to be made. Any tokens which do not appear as keys in the replacement
 * variables array will be used verbatium in the output. This allows using a
 * default value as the token itself.
 *
 * PHP's parse_ini_string()/parse_ini_file() functions are used to parse the
 * ini file. There are several things to be aware of as a result of this.
 * - Semicolons (;) should be used for comments.
 * - Unquoted numerics are parsed as php integers thus numbers starting by 0
 *   are evaluated as octals and numbers starting by 0x are evaluated as
 *   hexadecimals.
 * - If a value in the ini file contains any non-alphanumeric characters it
 *   needs to be enclosed in double-quotes (").
 * - Values enclosed in double quotes can contain new lines.
 * - Values not enclosed in double quotes are subject to constant expansion.
 *   This means that values matching the names of constants defined at the time
 *   the ini file is parsed will be replaced with the value of the constant.
 * - There are reserved words which must not be used as keys for ini files.
 *   These include: null, yes, no, true, false, on, off, none. Values null, no
 *   and false results in "", yes and true results in "1". Characters
 *   ?{}|&~![()^" must not be used anywhere in the key and have a special
 *   meaning in the value.
 *
 * Example:
 * <code>
 * ; example ini file
 * [section]
 * key1 = "{{TOKEN1}}"
 * key2 = "{{TOKEN2}}"
 * </code>
 *
 * <code>
 * <?php
 * $ini = IniParser::parse('/path/to/file.ini', array(
 *    'TOKEN1' => 'replacement 1',
 *    'TOKEN2' => 'replacement 2',
 *  ));
 * </code>
 *
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 * @see parse_ini_string
 * @see parse_ini_file
 */
class IniParser
{
    /**
     * Regular expression used to find substituion tokens in the ini file.
     * @var string
     */
    const RE_PARSE = '/
    \\\\{{          # backslash escaped start marker
    |               # OR
    \\\\}}          # backslash escaped end marker
    |               # OR
    (?<!\\\\)       # not after a backslash
    {{              # token start marker
    (?P<label>      # start capture "label"
      [^{]          # first char of token is anything but a brace
      .*?           # non-greedy match of anything
    )               # end capture "label"
    (?<!\\\\)       # not after a backslash
    }}              # token end marker
    /sxS';          // dotall, extended, analyze
    /**
     * Ini file contents.
     * @var string
     */
    private $iniString;
    /**
     * Substitution variables.
     * @var array
     */
    private $vars;

    /**
     * Constructor.
     *
     * @param string $file File path
     * @param array $vars Substitution variables
     */
    private function __construct($file, $vars)
    {
        $this->iniString = file_get_contents($file);
        $this->vars = $vars;
    }

    /**
     * Parse and expand the ini file.
     * @return array Ini contents
     */
    private function expand()
    {
        $expanded = preg_replace_callback(
            self::RE_PARSE,
            [$this, 'substitute'],
            $this->iniString
        );

        if (function_exists('parse_ini_string')) {
            return parse_ini_string($expanded, true);
        }
        // php 5.2 doesn't have parse_ini_string()
        $tmp = tempnam(sys_get_temp_dir(), __CLASS__);
        file_put_contents($tmp, $expanded);
        $parsed = parse_ini_file($tmp, true);
        unlink($tmp);

        return $parsed;
    }

    /**
     * Substitute a match with data found in our variables.
     * @param array $match Regex matches
     * @return string Variable value or original key if no match found
     */
    public function substitute($match)
    {
        if ('\\{{' == $match[0] || '\\}}' == $match[0]) {
            // strip escape char
            return mb_substr($match[0], 1);
        }

        $label = $match['label'];
        $label = strtr($label, ['\\{{' => '{{', '\\}}' => '}}']);
        if (isset($this->vars[$label])) {
            return $this->vars[$label];
        }

        return $label;
    }

    /**
     * Parse an ini file and return an array of it's contents.
     *
     * @param string $file Path to ini file
     * @param array $vars Variable values to substiute in the file
     * @return array Parsed ini data
     */
    public static function parse($file, $vars = null)
    {
        if (null === $vars) {
            return parse_ini_file($file, true);
        }

        $p = new self($file, $vars);

        return $p->expand();
    }
}
