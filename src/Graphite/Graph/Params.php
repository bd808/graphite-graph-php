<?php

namespace Graphite\Graph;

/**
 * Utility for graph level parameters.
 *
 * @package Graphite
 * @subpackage Graph
 * @author Bryan Davis <bd808@bd808.com>
 * @copyright 2012 Bryan Davis and contributors. All Rights Reserved.
 * @license http://www.opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
class Params {

  /**
   * Valid graph URI parameters.
   *
   * @var array
   * @see http://readthedocs.org/docs/graphite/en/latest/url-api.html
   */
  static protected $params = array(
    // request level
    'cacheTimeout'       => '#',
    'from'               => '-',
    'graphType'          => '-',
    'jsonp'              => '-',
    'local'              => '-',
    'noCache'            => '!',
    'until'              => '-',

    // all graph types
    'bgcolor'            => '-',
    'colorList'          => '-',
    'fgcolor'            => '-',
    'fontBold'           => '^',
    'fontItalic'         => '^',
    'fontName'           => '-',
    'fontSize'           => '#',
    'height'             => '#',
    'margin'             => '#',
    'outputFormat'       => '-',
    'template'           => '-',
    'width'              => '#',
    'yAxisSide'          => '-',

    // line graph
    'areaAlpha'          => '#',
    'areaMode'           => '-',
    'drawNullAsZero'     => '!',
    'graphOnly'          => '^',
    'hideAxes'           => '^',
    'hideGrid'           => '^',
    'hideLegend'         => '^',
    'hideYAxis'          => '^',
    'leftColor'          => '-',
    'leftDashed'         => '!',
    'leftWidth'          => '#',
    'lineMode'           => '-',
    'lineWidth'          => '#',
    'logBase'            => '#',
    'majorGridLineColor' => '-',
    'minorGridLineColor' => '-',
    'minorY'             => '#',
    'minXStep'           => '#',
    'rightColor'         => '-',
    'rightDashed'        => '!',
    'rightWidth'         => '#',
    'thickness'          => '#',
    'title'              => '-',
    'tz'                 => '-',
    'vtitle'             => '-',
    'xFormat'            => '-',
    'yLimit'             => '#',
    'yLimitLeft'         => '#',
    'yLimitRight'        => '#',
    'yMax'               => '#',
    'yMaxLeft'           => '#',
    'yMaxRight'          => '#',
    'yMin'               => '#',
    'yMinLeft'           => '#',
    'yMinRight'          => '#',
    'yStep'              => '#',
    'yStepLeft'          => '#',
    'yStepRight'         => '#',
    'yUnitSystem'        => '-',

    // pie graph
    'pieLabels'          => '-',
    'pieMode'            => '-',
    'valueLabels'        => '-',
    'valueLabelsMin'     => '#',
  );


  /**
   * Mapping from property names to Graphite parameter names.
   * @var array
   */
  static protected $aliases = array(
    'area'   => 'areaMode',
    'axes'   => 'hideAxes',
    'grid'   => 'hideGrid',
    'legend' => 'hideLegend',
    'pie'    => 'pieMode',
    'max'    => 'yMax',
    'min'    => 'yMin',
  );


  /**
   * Find the canonical name for a parameter.
   *
   * The value may be an alias or it may differ in case from the true
   * parameter name.
   *
   * @param string $name Parameter to lookup
   * @return string Proper name of parameter or false if not found
   */
  static public function canonicalName ($name) {
    static $lookupMap;
    if (null == $lookupMap) {
      // lazily construct the lookup map
      $tmp = array();
      foreach (self::$params as $param => $type) {
        $tmp[mb_strtolower($param)] = $param;
      }
      foreach (self::$aliases as $alias => $param) {
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
  } //end canonicalName


  /**
   * Format a parameter value.
   *
   * @param string $name Parameter name
   * @param string $value Value to format
   * @return mixed Formatted value
   * @see CallSpec::format
   */
  static public function format ($name, $value) {
    $type = self::$params[$name];
    return CallSpec::format($value, $type);
  } //end format


  /**
   * Construction disallowed.
   */
  private function __construct () {
    // no-op
  }

} //end Params

