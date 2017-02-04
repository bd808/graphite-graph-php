<?php

namespace Graphite\Graph;

use PHPUnit_Framework_TestCase;

/**
 * @package Graphite
 * @subpackage Graph
 */
class SeriesTest extends \PHPUnit_Framework_TestCase {

  /**
   * Given: a moderately complex DSL usage
   * Expect: a well formed query string
   */
  public function test_first_alias_wins () {
    $spec = array(
        "series" => "something.prod.*.requests.count",
        "aliasByNode" => 3,
        "cactistyle" => true,
        "alias" => "App Hits"
      );
    $this->assertEquals(
        'cactiStyle(aliasByNode(something.prod.*.requests.count,3))',
        Series::generate($spec));
  } //end testFirstAliasWins


  /**
   * Given: a wildcard function and a truthy ini value
   * Expect: no args other than series to function call
   */
  public function test_wildcard_no_args () {
    $spec = array(
        "series" => "something.prod.*.requests.count",
        "sum" => '1',
      );
    $this->assertEquals(
        'sumSeries(something.prod.*.requests.count)',
        Series::generate($spec));
  }

  /**
   * Given: a generator call with no arguments
   * Expect: a series using the generator with the alias as the only arg
   */
  public function test_generator_no_args () {
    $b = Series::builder()
        ->random()
        ->alias('Noise');
    $this->assertEquals("randomWalkFunction('Noise')", $b->build());
  }

  /**
   * Given: a generator call with arguments
   * Expect: a series using the generator
   */
  public function test_generator_with_args () {
    $b = Series::builder()
        ->threshold(123.456, "omgwtfbbq", "red");
    $this->assertEquals("threshold(123.456,'omgwtfbbq','red')", $b->build());
  }

} //end SeriesTest
