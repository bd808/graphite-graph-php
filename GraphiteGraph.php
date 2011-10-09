<?php
/**
 * @package 
 */

/**
 *
 * @package 
 * @author Bryan Davis <bd808@bd808.com>
 * @version SVN: $Id: skeleton.php 81 2007-07-11 15:04:33Z bpd $
 * @copyright 2011 Bryan Davis. All Rights Reserved.
 */
class GraphiteGraph {

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
   * @param string $file Graph description file
   * @param array $overrides Default settings for graph
   * @param array $info Settings for service blocks
   */
  public function __construct ($file, $overrides=array(), $info=array()) {
    $this->props = array_merge($this->props, $overrides);
    $this->info = $info;
    $this->load($file);
  }


  public function __get ($name) {
    if ('url' == $name) {
      return $this->url();

    } else if (array_key_exists($name, $this->props)) {
      return $this->props[$name];
    }
  } //end __get


  public function __set ($name, $val) {
    if (array_key_exists($name, $this->props)) {
      $this->props[$name] = $val;
    }
  } //end __set


  /**
   * Load a graph description file.
   * @param string $file Path to file
   * @return void
   */
  protected function load ($file) {
    $this->series = array();
    $g = $this;
    // read php file
    require $file;
    // use data from file to construct graph dsl
  }


  /**
   * Start a service block.
   * @param string $service Name of service
   * @param string $data Data collection of interest
   * @return GraphiteGraph Self, for message chaining
   */
  public function service ($service, $data) {
    if (!isset($this->info['hostname'])) {
      throw new Exception("Hostname must be defined for services");
    }
    $this->service = array('service' => $service, 'data' => $data);
    return $this;
  } //end service


  /**
   * End service block.
   * @return GraphiteGraph Self, for message chaining
   */
  public function endService () {
    $this->service = null;
  } //end endService

  /**
   * Add a data series to the graph.
   * @param string $name Name of series
   * @param array $opts Series options
   * @return GraphiteGraph Self, for message chaining
   */
  public function field ($name, $opts) {
    if (isset($this->series[$name])) {
      throw new Exception(
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
   * @return string Query string to append to grpahite url to render this 
   * graph
   */
  public function url ($format=null) {
    if ($this->props['suppress']) {
      return null;
    }

    $parts = array();
    $colors = array();

    foreach (array('title', 'vtitle', 'from', 'width', 'height') as $item) {
      $parts[] = "{$item}={$this->props[$item]}";
    }

    foreach ($this->series as $name => $conf) {
      if (isset($conf['target']) && $conf['target']) {
        $parts[] = "target={$conf['target']}";

      } else {
        if (!isset($conf['data'])) {
          throw new Exception(
              "field {$name} does not have any data associated with it");
        }

        $gt = $conf['data'];
        if (isset($conf['derivative'])) {
          $gt = "derivative({$gt})";
        }
        if (isset($conf['scale'])) {
          $gt = "scale({$gt},{$conf['scale']})";
        }
        if (isset($conf['line'])) {
          $gt = "drawAsInfinite({$gt})";
        }

        if (isset($conf['alias'])) {
          $alias = $conf['alias'];
        } else {
          $alias = mb_convert_case($name, MB_CASE_TITLE);
        }
        $gt = "alias({$gt},\"{$alias}\")";
        $parts[] = "target={$gt}";
      } //end if/else

      if (isset($conf['color'])) {
        $colors[] = $conf['color'];
      }
    } //end foreach

    if ($colors) {
      $parts[] = 'colorList=' . implode(',', $colors);
    }

    if ($format) {
      $parts[] = "format={$format}";
    }

    return implode('&', $parts);
  } //end url

} //end GraphiteGraph
