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
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Graphite_GraphBuilder {

  /**
   * Properties for graph.
   * @var array
   */
  protected $props = array(
      'title' => '',
      'vtitle' => '',
      'width' => 500,
      'height' => 250,
      'from' => '-1hour',
      'suppress' => false,
      'description' => null,
      'area' => 'none',
    );

  /**
   * Global graph information.
   * @var array
   */
  protected $info;

  /**
   * Service information.
   * @var array
   */
  protected $service;

  /**
   * Configuration for each series that will be rendered or retrieved.
   * @var array
   */
  protected $series;


  /**
   * Constructor.
   * @param string $file Graph description file (null for none)
   * @param array $overrides Default settings for graph
   * @param array $info Settings for service blocks
   */
  public function __construct ($file=null, $overrides=null, $info=null) {
    if (is_array($overrides)) {
      $this->props = array_merge($this->props, $overrides);
    }
    $this->info = (is_array($info))? $info: array();
    $this->load($file);
  }


  /**
   * Start a service block.
   * @param string $service Name of service
   * @param string $data Data collection of interest
   * @return Graphite_GraphBuilder Self, for message chaining
   * @throws Graphite_ConfigurationException if info[hostname] is not defined
   */
  public function service ($service, $data) {
    if (!isset($this->info['hostname'])) {
      throw new Graphite_ConfigurationException(
          "Hostname must be defined for services");
    }
    $this->service = array('service' => $service, 'data' => $data);
    return $this;
  } //end service


  /**
   * End service block.
   * @return Graphite_GraphBuilder Self, for message chaining
   */
  public function endService () {
    $this->service = null;
    return $this;
  } //end endService


  /**
   * Add a data series to the graph.
   * @param string $name Name of data field to graph
   * @param array $opts Series options
   * @return Graphite_GraphBuilder Self, for message chaining
   * @throws Graphite_ConfigurationException If name duplicates existing field
   */
  public function field ($name, $opts=array()) {
    if (isset($this->series[$name])) {
      throw new Graphite_ConfigurationException(
          "A field named {$name} already exists for this graph.");
    }
    $defaults = array();
    if ($this->service) {
      $defaults['data'] = "{$this->info['hostname']}." .
          "{$this->service['service']}.{$this->service['data']}.{$name}";
    }
    $this->series[$name] = array_merge($defaults, $opts);

    return $this;
  } //end field


  /**
   * Generate a graphite graph description url.
   * @param string $format Format to export data in (null for graph)
   * @return string Query string to append to graphite url to render this
   *  graph
   *  @throws Graphite_ConfigurationException If required data is missing
   */
  public function url ($format=null) {
    if ($this->props['suppress']) {
      return null;
    }

    $parms = array();
    $colors = array();

    foreach (array('title', 'vtitle', 'from', 'width', 'height') as $item) {
      if ($this->props[$item]) {
        $parms[] = $item . '=' . urlencode($this->props[$item]);
      }
    }
    $parms[] = 'areaMode=' . urlencode($this->props['area']);

    foreach ($this->series as $name => $conf) {
      $target = self::generateTarget($name, $conf);
      $parms[] = "target={$target}";

      if (isset($conf['color'])) {
        $colors[] = urlencode($conf['color']);
      }
    } //end foreach

    if ($colors) {
      $parms[] = 'colorList=' . implode(',', $colors);
    }

    if ($format) {
      $format = urlencode($format);
      $parms[] = "format={$format}";
    }

    return implode('&', $parms);
  } //end url


  /**
   * Generate the target parameter for a given field.
   * @param string $name Field name
   * @param array $field Field configuration
   * @return string Target parameter
   * @throws Graphite_ConfigurationException If neither data nor target is set 
   * in conf
   */
  protected static function generateTarget ($name, $conf) {
    if (isset($conf['target']) && $conf['target']) {
      // explict target has been provided by the user
      $target = urlencode($conf['target']);

    } else if (!isset($conf['data'])) {
      throw new Graphite_ConfigurationException(
          "field {$name} does not have any data associated with it.");

    } else {
      $target = urlencode($conf['data']);

      if (isset($conf['derivative'])) {
        $target = "derivative({$target})";
      }

      if (isset($conf['scale'])) {
        $scale = urlencode($conf['scale']);
        $target = "scale({$target},{$scale})";
      }

      if (isset($conf['line'])) {
        $target = "drawAsInfinite({$target})";
      }

      if (isset($conf['alias'])) {
        $alias = $conf['alias'];

      } else {
        $alias = ucfirst($name);
      }
      $alias = urlencode($alias);
      $target = "alias({$target},%22{$alias}%22)";

    } //end if/else

    return $target;
  } //end generateTarget


  /**
   * Handle attempts to read from non-existant members.
   *
   * Looks for $name in properties and returns value if found.
   *
   * @param string $name Member name
   * @return mixed Property value
   */
  public function __get ($name) {
    if ('url' == $name) {
      return $this->url();

    } else if (array_key_exists($name, $this->props)) {
      return $this->props[$name];
    }
  } //end __get


  /**
   * Handle attempts to write to non-existant members.
   *
   * Looks for $name in properties and sets value if found.
   *
   * @param string $name Member name
   * @param mixed $val Value to set
   * @return void
   */
  public function __set ($name, $val) {
    if (array_key_exists($name, $this->props)) {
      $this->props[$name] = $val;
    }
  } //end __set


  /**
   * Handle attempts to call non-existant methods.
   *
   * Looks for $name in properties and gets/sets value if found.
   *
   * @param string $name Method name
   * @param array $args Invocation arguments
   * @return mixed Property value or self
   */
  public function __call ($name, $args) {
    if (array_key_exists($name, $this->props)) {
      if (count($args) > 0) {
        // set property and return self for chaining
        $this->props[$name] = $args[0];
        return $this;

      } else {
        // return property
        return $this->props[$name];
      }
    }
  } //end __call


  /**
   * Load a graph description file.
   * @param string $file Path to file
   * @return void
   */
  protected function load ($file) {
    $this->series = array();
    if (null !== $file) {
      $ini = parse_ini_file($file, true, INI_SCANNER_RAW);

      // first section is graph description
      $graph = array_shift($ini);
      foreach ($graph as $key => $value) {
        $this->$key($value);
      }

      $services = array();
      foreach ($ini as $name => $data) {
        // look for services first
        if (isset($data[':is_service'])) {
          $services[$name] = $data;
          continue;
        }

        // it must be a field
        if (isset($data[':use_service'])) {
          $svcData = $services[$data[':use_service']];
          $svcName = (isset($svcData['service']))?
              $svcData['service']: $data[':use_service'];

          $this->service($svcName, $svcData['data']);
        }

        $fieldName = (isset($data['field']))? $data['field']: $name;
        $this->field($fieldName, $data);
        $this->endService();

      } //end foreach
    } //end if
  } //end load

} //end Graphite_GraphBuilder
