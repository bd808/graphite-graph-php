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
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_GraphBuilder {

  /**
   * Valid graph URI parameters.
   *
   * @var array
   * @see http://readthedocs.org/docs/graphite/en/latest/url-api.html
   */
  static protected $validParams = array(
    // request level
    'cacheTimeout',
    'from',
    'graphType',
    'jsonp',
    'local',
    'noCache',
    'until',

    // all graph types
    'bgcolor',
    'colorList',
    'fgcolor',
    'fontBold',
    'fontItalic',
    'fontName',
    'fontSize',
    'height',
    'margin',
    'outputFormat',
    'template',
    'width',
    'yAxisSide',

    // line graph
    'areaAlpha',
    'areaMode',
    'drawNullAsZero',
    'graphOnly',
    'hideAxes',
    'hideGrid',
    'hideLegend',
    'hideLegend',
    'hideYAxis',
    'leftColor',
    'leftDashed',
    'leftWidth',
    'lineMode',
    'lineWidth',
    'logBase',
    'majorGridLineColor',
    'max',
    'min',
    'minorGridLineColor',
    'minorY',
    'minXStep',
    'rightColor',
    'rightDashed',
    'rightWidth',
    'thickness',
    'title',
    'tz',
    'vtitle',
    'xFormat',
    'yLimit',
    'yLimitLeft',
    'yLimitRight',
    'yMax',
    'yMaxLeft',
    'yMaxRight',
    'yMin',
    'yMinLeft',
    'yMinRight',
    'yStep',
    'yStepLeft',
    'yStepRight',
    'yUnitSystem',

    // pie graph
    'pieLabels',
    'pieMode',
    'valueLabels',
    'valueLabelsMin',
  );

  /**
   * Mapping from property names to Graphite parameter names.
   * @var array
   */
  static protected $paramAliases = array(
    'area'    => 'areaMode',
    'axes'    => 'hideAxes',
    'grid'    => 'hideGrid',
    'legend'  => 'hideLegend',
    'pie'     => 'pieMode',
  );


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

    $cname = self::canonicalParamName($name);
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
    $cname = self::canonicalParamName($name);
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

    $opts['series'] = 'threshold(' . urlencode($opts['value']) . ')';
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
    $opts['series'] = urlencode($opts['series']);

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
   * Generate a graphite graph description query string.
   * @param string $format Format to export data in (null for graph)
   * @return string Query string to append to graphite url to render this
   *  graph
   * @throws Graphite_ConfigurationException If required data is missing
   */
  public function qs ($format=null) {
    $parms = array();

    foreach ($this->settings as $name => $value) {
      $parms[] = urlencode($name) . '=' . urlencode($value);
    }

    foreach ($this->targets as $target) {
      $parms[] = 'target=' .
          urlencode(Graphite_TargetBuilder::generateTarget($target));
    } //end foreach

    if ($format) {
      $parms[] = 'format=' . urlencode($format);
    }

    return implode('&', $parms);
  } //end qs

  /**
   * Alias for qs().
   *
   * @deprecated
   * @see qs()
   */
  public function url ($format=null) {
    return $this->qs($format);
  } //end url


  /**
   * Load a graph description file.
   *
   * @param string $file Path to file
   * @param array $vars Variables to substitute in the ini file
   * @return void
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

  } //end ini


  /**
   * Find the canonical name for a parameter.
   *
   * The value may be an alias or it may differ in case from the true
   * parameter name.
   *
   * @param string $name Parameter to lookup
   * @return string Proper name of parameter or false if not found
   */
  static public function canonicalParamName ($name) {
    static $lookupMap;
    if (null == $lookupMap) {
      // lazily construct the lookup map
      $tmp = array();
      foreach (self::$validParams as $param) {
        $tmp[mb_strtolower($param)] = $param;
      }
      foreach (self::$paramAliases as $alias => $param) {
        $tmp[mb_strtolower($alias)] = $param;
      }
      $lookupMap = $tmp;
    }

    // convert to lowercase and strip "delimiter" characters
    $name = strtr(mb_strtolower($name), '_.-', '');
    if (array_key_exists($name, $lookupMap)) {
      return $lookupMap[$name];
    } else {
      return false;
    }
  } //end canonicalParamName


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

} //end Graphite_GraphBuilder
