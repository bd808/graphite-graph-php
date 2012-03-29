<?php
/**
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

/**
 * Graphite graph query string generator.
 *
 * DSL and ini file driven API to assist in generating Graphite graph query
 * strings.
 *
 * Example:
 * <code>
 * <?php
 * $g = Graphite_GraphBuilder::builder(array('width' => 600, 'height' => 300))
 *     ->title('Memory')
 *     ->vtitle('Mbytes')
 *     ->bgcolor('white')
 *     ->fgcolor('black')
 *     ->from('-2days')
 *     ->area('stacked')
 *     ->prefix('metrics.collectd')
 *     ->prefix('com.example.host-1')
 *     ->prefix('snmp')
 *     ->metric('memory-free', array(
 *       'cactistyle' => true,
 *       'color' => '00c000',
 *       'alias' => 'Free',
 *       'scale' => '0.00000095367',
 *     ))
 *     ->metric('memory-used', array(
 *       'cactistyle' => true,
 *       'color' => 'c00000',
 *       'alias' => 'Used',
 *       'scale' => '0.00000095367',
 *     ));
 * ?>
 * <img src="http://graphite.example.com/render?<?php echo $g; ?>">
 * </code>
 *
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 * @link http://graphite.wikidot.com/
 * @link http://readthedocs.org/docs/graphite/en/latest/url-api.html
 * @link http://bd808.com/graphite-graph-php/
 */
class Graphite_GraphBuilder {

  /**
   * Graph settings.
   * @var array
   */
  protected $settings;

  /**
   * Metric prefix stack.
   * @var array
   */
  protected $prefixStack;

  /**
   * Configuration for each target that will be rendered or retrieved.
   * @var array
   */
  protected $targets;

  /**
   * Constructor.
   *
   * @param array $settings Default settings for graph
   */
  public function __construct ($settings=null) {
    $this->reset($settings);
  }


  /**
   * Reset builder to empty state.
   *
   * @param array $settings Default settings for graph
   * @return Graphite_GraphBuilder Self, for message chaining
   */
  public function reset ($settings=null) {
    $this->settings = (is_array($settings))? $settings: array();
    $this->prefixStack = array();
    $this->targets = array();

    return $this;
  }


  /**
   * Handle attempts to read from non-existant members.
   *
   * Looks for $name in settings and returns value if found.
   * If the setting isn't valid an E_USER_NOTICE warning will be raised.
   *
   * @param string $name Setting name
   * @return mixed Setting value or null if not found
   */
  public function __get ($name) {
    if ('qs' == $name || 'url' == $name) {
      return $this->qs();
    }

    $cname = Graphite_Graph_Params::canonicalName($name);
    if (array_key_exists($cname, $this->settings)) {
      $val = $this->settings[$cname];

      if ('hide' == mb_substr($cname, 0, 4) &&
          'hide' != mb_substr(mb_strtolower($name), 0, 4)) {
        // invert inverted alias
        $val = !$val;
      }

      return $val;
    }

    if (false === $cname) {
      // invalid request
      $trace = debug_backtrace();
      trigger_error("Undefined property via __get(): {$name} in " .
          "{$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
    }
    return null;
  } //end __get


  /**
   * Handle attempts to write to non-existant members.
   *
   * Sets setting $name=$val if $name is a valid setting.
   * If the setting isn't valid an E_USER_NOTICE warning will be raised.
   *
   * @param string $name Member name
   * @param mixed $val Value to set
   * @return void
   */
  public function __set ($name, $val) {
    $cname = Graphite_Graph_Params::canonicalName($name);
    if (false !== $cname) {
      if ('hide' == mb_substr($cname, 0, 4) &&
          'hide' != mb_substr(mb_strtolower($name), 0, 4)) {
        // invert logical toggle
        $val = !((bool) $val);
      }

      $this->settings[$cname] = $val;
    } else {
      // invalid request
      $trace = debug_backtrace();
      trigger_error("Undefined property via __set(): {$name} in " .
          "{$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
    }
  } //end __set


  /**
   * Handle attempts to call non-existant methods.
   *
   * Looks for $name in settings and gets/sets value if found.
   * If the setting isn't valid an E_USER_NOTICE warning will be raised.
   *
   * @param string $name Method name
   * @param array $args Invocation arguments
   * @return mixed Property value or self
   */
  public function __call ($name, $args) {
    if (count($args) > 0) {
      // set property and return self for chaining
      $this->__set($name, $args[0]);
      return $this;

    } else {
      // return property
      return $this->__get($name);
    }
  } //end __call


  /**
   * Set a prefix to add to subsequent metrics.
   * @param string $prefix Prefix to add
   * @return Graphite_GraphBuilder Self, for message chaining
   */
  public function prefix ($prefix) {
    if ('.' !== mb_substr($prefix, -1)) {
      // ensure that prefix ends with period
      // XXX: are we sure this is a good idea?
      $prefix = "{$prefix}.";
    }
    if ('^' == $prefix[0]) {
      // this is a rooted prefix, don't concat with current
      $prefix = mb_substr($prefix, 1);
    } else {
      // join with the current prefix
      $prefix = "{$this->currentPrefix()}{$prefix}";
    }

    $this->prefixStack[] = $prefix;
    return $this;
  } //end prefix


  /**
   * End prefix block.
   * @return Graphite_GraphBuilder Self, for message chaining
   */
  public function endPrefix () {
    array_pop($this->prefixStack);
    return $this;
  } //end endPrefix


  /**
   * Get the current target prefix.
   *
   * @return string Prefix including trailing period (may be empty string)
   */
  public function currentPrefix () {
    return (false !== end($this->prefixStack))?
        current($this->prefixStack): '';
  }


  /**
   * Add a data series to the graph.
   *
   * @param string $name Name of data metric to graph
   * @param array $opts Series options
   * @return Graphite_GraphBuilder Self, for message chaining
   */
  public function metric ($name, $opts=array()) {
    $defaults = array(
      // default alias is prettied up version of name
      'alias'   => ucwords(strtr($name, '-_.', ' ')),

      // default series is prefixed name
      'series'  => "{$this->currentPrefix()}{$name}",
    );
    $this->targets[] = array_merge($defaults, $opts);

    return $this;
  } //end metric


  /**
   * Draws a straight line on the graph.
   *
   * @param array $opts Line options
   * @return Graphite_GraphBuilder Self, for message chaining
   * @throws Graphite_ConfigurationException If required options are missing
   */
  public function line ($opts) {
    foreach (array('value', 'alias') as $key) {
      if (!isset($opts[$key])) {
        throw new Graphite_ConfigurationException(
          "lines require a {$key}");
      }
    }

    $opts['series'] = "threshold({$opts['value']})";
    unset($opts['value']);

    return $this->metric('line_' . count($this->targets), $opts);
  } //end line


  /**
   * Add forecast, confidence bands, aberrations and metrics using the
   * Holt-Winters Confidence Band prediction model.
   *
   * @param array $opts Line options
   * @return Graphite_GraphBuilder Self, for message chaining
   * @throws Graphite_ConfigurationException If required options are missing
   */
  public function forecast ($name, $opts) {
    if (!isset($opts['series'])) {
      throw new Graphite_ConfigurationException(
        "'series' is required for a Holt-Winters Confidence forecast");
    }
    $opts['series'] = $opts['series'];

    if (!isset($opts['alias'])) {
      $opts['alias'] = ucfirst($name);
    }

    if (!isset($opts['forecast_line']) || $opts['forecast_line']) {
      $args = $opts;
      $args['series'] = "holtWintersForecast({$args['series']})";
      $args['alias'] = "{$args['alias']} Forecast";
      $args['color'] = (isset($args['forecast_color']))?
        $args['forecast_color']: 'blue';
      $this->metric("{$name}_forecast", $args);
    }

    if (!isset($opts['bands_line']) || $opts['bands_line']) {
      $args = $opts;
      $args['series'] = "holtWintersConfidenceBands({$args['series']})";
      $args['alias'] = "{$args['alias']} Confidence";
      $args['color'] = (isset($args['bands_color']))?
        $args['bands_color']: 'grey';
      $args['dashed'] = true;
      $this->metric("{$name}_bands", $args);
    }

    if (!isset($opts['aberration_line']) || $opts['aberration_line']) {
      $args = $opts;
      $args['series'] = "holtWintersConfidenceAbberation(keepLastValue(" .
        $args['series'] . "))";
      $args['alias'] = "{$args['alias']} Aberration";
      $args['color'] = (isset($args['aberration_color']))?
        $args['aberration_color']: 'orange';
      if (isset($args['aberration_second_y']) && $args['aberration_second_y']) {
        $args['second_y_axis'] = true;
      }
      $this->metric("{$name}_aberration", $args);
    }

    if (isset($opts['critical'])) {
      $criticals = $opts['critical'];
      if (!is_array($criticals)) {
        $criticals = explode(',', $criticals);
      }
      foreach ($criticals as $value) {
        $color = (isset($opts['critical_color']))?
          $opts['critical_color']: 'red';
        $caption = "{$opts['alias']} Critical";
        $this->line(array(
          'value' => $value,
          'alias' => $caption,
          'color' => $color,
          'dashed' => true,
        ));
      }
    }

    if (isset($opts['warning'])) {
      $warnings = $opts['warning'];
      if (!is_array($warnings)) {
        $warnings = explode(',', $warnings);
      }
      foreach ($warnings as $value) {
        $color = (isset($opts['warning_color']))?
          $opts['warning_color']: 'orange';
        $caption = "{$opts['alias']} Warning";
        $this->line(array(
          'value' => $value,
          'alias' => $caption,
          'color' => $color,
          'dashed' => true,
        ));
      }
    }

    if (!isset($opts['color'])) {
      $opts['color'] = 'yellow';
    }

    if (!isset($opts['actual_line']) || $opts['actual_line']) {
      $this->metric($name, $opts);
    }
    return $this;
  } //end forecast


  /**
   * Load a graph description file.
   *
   * @param string $file Path to file
   * @param array $vars Variables to substitute in the ini file
   * @return Graphite_GraphBuilder Self, for message chaining
   */
  public function ini ($file, $vars=null) {
    $global = array();
    $prefixes = array();
    $metrics = array();

    $ini = Graphite_IniParser::parse($file, $vars);

    foreach ($ini as $key => $value) {
      if (is_array($value)) {
        // sub-arrays either describe prefixes or metrics
        if (isset($value[':is_prefix'])) {
          $prefixes[$key] = $value;

        } else {
          $metrics[$key] = $value;
        }

      } else {
        // must be a general setting
        $global[$key] = $value;
      }
    }
    unset($ini);

    // resolve all prefixes we found
    foreach ($prefixes as $name => $conf) {
      $prefixes[$name] = self::resolvePrefix($conf, $prefixes);
    }

    // apply global settings
    foreach ($global as $setting => $args) {
      $this->$setting($args);
    }

    // add metrics
    foreach ($metrics as $name => $conf) {
      // TODO: add support for preconfigured "types" like line, forecast, etc

      if (isset($conf[':prefix'])) {
        $this->prefix($prefixes[$conf[':prefix']]);
      }

      // metric name is either given explicitly or inferred from section label
      $metricName = (isset($conf['metric']))? $conf['metric']: $name;

      $this->metric($metricName, $conf);

      if (isset($conf[':prefix'])) {
        $this->endPrefix();
      }
    } //end foreach

    return $this;
  } //end ini


  /**
   * Generate a graphite graph description query string.
   * @param string $format Format to export data in (null for graph)
   * @return string Query string to append to graphite url to render this
   *    graph
   * @throws Graphite_ConfigurationException If required data is missing
   */
  public function build ($format=null) {
    $parms = array();

    foreach ($this->settings as $name => $value) {
      $parms[] = self::qsEncode($name) . '=' . self::qsEncode($value);
    }

    foreach ($this->targets as $target) {
      $parms[] = 'target=' .
        self::qsEncode(Graphite_Graph_Target::generate($target));
    } //end foreach

    if (null !== $format) {
      $parms[] = 'format=' . self::qsEncode($format);
    }

    return implode('&', $parms);
  } //end qs

  /**
   * Alias for build().
   *
   * @deprecated
   * @see build()
   */
  public function qs ($format=null) {
    return $this->build($format);
  } //end qs

  /**
   * Alias for build().
   *
   * @deprecated
   * @see build()
   */
  public function url ($format=null) {
    return $this->build($format);
  } //end url


  /**
   * Convert to string.
   *
   * @return string Query string to append to graphite url to render this
   *    graph
   */
  public function __toString () {
    return $this->build();
  }


  /**
   * Builder factory.
   *
   * @param array $settings Default settings for graph
   */
  static public function builder ($settings=null) {
    return new Graphite_GraphBuilder($settings);
  }


  /**
   * Create a fully qualified prefix for the given configuration.
   *
   * @param mixed $conf Literal prefix or array of config data
   * @return string Literal prefix
   */
  static public function resolvePrefix ($conf, $prefixes) {
    if (is_array($conf)) {
      $prefix = $conf['prefix'];
      if (isset($conf[':prefix'])) {
        // find our parent prefix
        $mom = self::resolvePrefix($prefixes[$conf[':prefix']], $prefixes);
        $prefix = "{$mom}.{$prefix}";
      }
      $conf = $prefix;
    }

    return $conf;
  } //end resolvePrefix


  /**
   * Query string specific uri encoding.
   *
   * Per RFC-3986:
   *   URI producing applications should percent-encode data octets that
   *   correspond to characters in the reserved set unless these characters
   *   are specifically allowed by the URI scheme to represent data in that
   *   component.
   *
   * Php's builtin urlencode function is a general purpose encoder. This means
   * that it takes the most conservative approach to encoding. It
   * percent-encodes all octets that are not in the "unreserved" set
   * (ALPHA / DIGIT / "-" / "." / "_" / "~"). Actually it goes further than
   * this and encodes the tilde as well for no apparent reason other than
   * potential binary compatibility with the output of early non-conforming
   * user-agents.
   *
   * Within the "query" section of a URI there is a broader set of valid
   * characters allowed without percent-encoding:
   * - query         = *( pchar / "/" / "?" )
   * - pchar         = unreserved / pct-encoded / sub-delims / ":" / "@"
   * - unreserved    = ALPHA / DIGIT / "-" / "." / "_" / "~"
   * - pct-encoded   = "%" HEXDIG HEXDIG
   * - sub-delims    = "!" / "$" / "&" / "'" / "(" / ")" /
   *                   "*" / "+" / "," / ";" / "="
   *
   * This encoder will let php do the heavy lifting with urlencode() but will
   * then decode _most_ query allowed characters. We will leave "&", "=", ";"
   * and "+" percent-encoded to preserve delimiters used in the
   * application/x-www-form-urlencoded encoding.
   *
   * What's the point? I could claim that it reduces the size of the encoded
   * string, but my real reason is that it makes the Graphite query strings
   * more readable for debugging.
   *
   * @param string $str String to encode for embedding in the query component
   *    of a URI.
   * @return string RFC-3986 conforming encoded string
   * @see RFC-3986
   * @see RFC-1738
   * @see HTML 4.01 Specification
   */
  static public function qsEncode ($str) {
    static $decode = array(
      '%21' => '!',
      '%24' => '$',
      '%27' => '\'',
      '%28' => '(',
      '%29' => ')',
      '%2A' => '*',
      '%2C' => ',',
      '%2F' => '/',
      '%3A' => ':',
      '%3F' => '?',
      '%40' => '@',
      '%7E' => '~',
    );

    $full = urlencode($str);
    return str_replace(array_keys($decode), array_values($decode), $full);
  } //end qsEncode

} //end Graphite_GraphBuilder
