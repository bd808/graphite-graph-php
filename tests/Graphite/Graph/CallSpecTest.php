<?php
/**
 * @package Graphite
 * @subpackage Graph
 */

/**
 * @package Graphite
 * @subpackage Graph
 */
class Graphite_Graph_CallSpecTest extends PHPUnit_Framework_TestCase {

  /**
   * Given: a call spec with the '<' modifier
   * Expect: the first arg to be the hoisted value
   */
  public function test_hoist_modifier () {
    $s = new Graphite_Graph_CallSpec('mostDeviant', '#<');
    $this->assertSame('mostDeviant(1234,*)', $s->asString('*', array(1234)));

    $s = new Graphite_Graph_CallSpec('f', array('-', '-<'));
    $this->assertSame('f(2,*,1)', $s->asString('*', 1, 2));
  }


  /**
   * Given: a call spec with the '?' modifier
   * Expect: empty string, null and bool values to be omitted from call
   */
  public function test_optional_modifier () {
    $s = new Graphite_Graph_CallSpec('f', array('-?'));

    $this->assertSame('f(series)', $s->asString('series', null));
    $this->assertSame('f(series)', $s->asString('series', ''));
    $this->assertSame('f(series)', $s->asString('series', true));
    $this->assertSame('f(series)', $s->asString('series', false));

    $this->assertSame('f(series,0)', $s->asString('series', 0));
    $this->assertSame('f(series,1)', $s->asString('series', 1));
  }


  /**
   * Given: a function expecting a boolean type
   * Expect: standard php bool casting
   */
  public function test_boolean_coersion () {
    $s = new Graphite_Graph_CallSpec('f', array('^'));

    $this->assertSame('f(series,True)', $s->asString('series', true));
    $this->assertSame('f(series,True)', $s->asString('series', '1'));
    $this->assertSame('f(series,True)', $s->asString('series', 1));
    $this->assertSame('f(series,True)', $s->asString('series', '-1'));
    $this->assertSame('f(series,True)', $s->asString('series', -1));
    $this->assertSame('f(series,True)', $s->asString('series', 'most strings'));
    $this->assertSame('f(series,True)', $s->asString('series', array(1)));

    $this->assertSame('f(series,False)', $s->asString('series', false));
    $this->assertSame('f(series,False)', $s->asString('series', 0));
    $this->assertSame('f(series,False)', $s->asString('series', 0.0));
    $this->assertSame('f(series,False)', $s->asString('series', ''));
    $this->assertSame('f(series,False)', $s->asString('series', '0'));
    $this->assertSame('f(series,False)', $s->asString('series', array()));
    $this->assertSame('f(series,False)', $s->asString('series', null));
  }


  /**
   * Given: a function expecting a numeric type
   * Expect: numeric coersion
   */
  public function test_numeric_coersion () {
    $s = new Graphite_Graph_CallSpec('f', array('#'));

    $this->assertSame('f(series,1)', $s->asString('series', 1));
    $this->assertSame('f(series,1)', $s->asString('series', '1'));
    $this->assertSame('f(series,0.1)', $s->asString('series', 1E-1));
    $this->assertSame('f(series,0.1)', $s->asString('series', 1e-1));
    $this->assertSame(
        'f(series,0.00000095)', $s->asString('series', 1/(1024*1024)));

    $this->assertSame('f(series)', $s->asString('series', null));
    $this->assertSame('f(series)', $s->asString('series', 'fruit'));
  }


  /**
   * Given: a list of callspecs
   * Expect: proper sorting
   */
  public function test_sort_order () {
    // php's quicksort isn't stable, so no use checking for that
    // by using dup values in the control.
    $sorted = array(
        new Graphite_Graph_CallSpec('0', array('-'), 1),
        new Graphite_Graph_CallSpec('1', array('-'), 10),
        new Graphite_Graph_CallSpec('2', array('-'), 20),
        new Graphite_Graph_CallSpec('3', array('-'), 30),
        new Graphite_Graph_CallSpec('4', array('-'), 40),
        new Graphite_Graph_CallSpec('5', array('-'), 50),
        new Graphite_Graph_CallSpec('6', array('-'), 99),
      );

    $clone = unserialize(serialize($sorted));
    // make sure we start sorted
    // NOTE: assertSame doesn't work here
    $this->assertEquals($sorted, $clone);

    usort($clone, array('Graphite_Graph_CallSpec', 'cmp'));
    $this->assertEquals($sorted, $clone, 'sorted -> sorted');

    $clone = array_reverse($clone);
    usort($clone, array('Graphite_Graph_CallSpec', 'cmp'));
    $this->assertEquals($sorted, $clone, 'reverse -> sorted');

    shuffle($clone);
    usort($clone, array('Graphite_Graph_CallSpec', 'cmp'));
    $this->assertEquals($sorted, $clone, 'shuffle -> sorted');
  }

} //end Graphite_Graph_CallSpecTest
