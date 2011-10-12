<?php
/**
 * Copyright (c) 2011, Bryan Davis and contributors
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *   a. Redistributions of source code must retain the above copyright notice,
 *      this list of conditions and the following disclaimer.
 *
 *   b. Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package Graphite
 */


/**
 * DSL for creating graph desciptions for <a 
 * href="http://graphite.wikidot.com/">Graphite</a>.
 *
 * @package Graphite
 * @author Bryan Davis <bd808@bd808.com>
 * @version SVN: $Id: skeleton.php 81 2007-07-11 15:04:33Z bpd $
 * @copyright 2011 Bryan Davis and contributors. All Rights Reserved.
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
  public function __construct ($file=null, $overrides=array(), $info=array()) {
    $this->props = array_merge($this->props, $overrides);
    $this->info = $info;
    $this->load($file);
  }


  /**
   * Start a service block.
   * @param string $service Name of service
   * @param string $data Data collection of interest
   * @return Graphite_GraphBuilder Self, for message chaining
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
      $parts[] = $item . '=' . htmlentities($this->props[$item]);
    }
    $parts[] = 'areaMode=' . htmlentities($this->props['area']);

    foreach ($this->series as $name => $conf) {
      if (isset($conf['target']) && $conf['target']) {
        $parts[] = 'target=' . htmlentities($conf['target']);

      } else {
        if (!isset($conf['data'])) {
          throw new Exception(
              "field {$name} does not have any data associated with it");
        }

        $gt = htmlentities($conf['data']);
        if (isset($conf['derivative'])) {
          $gt = "derivative({$gt})";
        }
        if (isset($conf['scale'])) {
          $scale = htmlentities($conf['scale']);
          $gt = "scale({$gt},{$scale})";
        }
        if (isset($conf['line'])) {
          $gt = "drawAsInfinite({$gt})";
        }

        if (isset($conf['alias'])) {
          $alias = $conf['alias'];
        } else {
          $alias = ucfirst($name);
        }
        $alias = htmlentities($alias);
        $gt = "alias({$gt},&quot;{$alias}&quot;)";
        $parts[] = "target={$gt}";
      } //end if/else

      if (isset($conf['color'])) {
        $colors[] = $conf['color'];
      }
    } //end foreach

    if ($colors) {
      $parts[] = 'colorList=' . htmlentities(implode(',', $colors));
    }

    if ($format) {
      $format = htmlentities($format);
      $parts[] = "format={$format}";
    }

    return implode('&', $parts);
  } //end url


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


  public function __call ($name, $args) {
    if (array_key_exists($name, $this->props)) {
      if ($args) {
        $this->props[$name] = $args[0];
        return $this;
      } else {
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
