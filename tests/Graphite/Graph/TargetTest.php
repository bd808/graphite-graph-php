<?php
/**
 * @package Graphite
 * @subpackage Graph
 */

/**
 * @package Graphite
 * @subpackage Graph
 */
class Graphite_TargetTest extends PHPUnit_Framework_TestCase {

  /**
   * Given: a moderately complex DSL usage
   * Expect: a well formed query string
   */
  public function testFirstAliasWins () {
    $spec = array(
        "series" => "something.prod.*.requests.count",
        "aliasByNode" => 3,
        "cactistyle" => true,
        "alias" => "App Hits"
        );
    $this->assertEquals('cactiStyle(aliasByNode(something.prod.*.requests.count,3))', Graphite_Graph_Target::generate($spec));
  } //end testFirstAliasWins

} //end Graphite_TargetTest
