<?php

namespace Graphite\Graph;

use PHPUnit_Framework_TestCase;

/**
 * @package Graphite
 * @subpackage Graph
 */
class CallSpecTest extends \PHPUnit_Framework_TestCase {

  /**
   * Given: a call spec with the '<' modifier
   * Expect: the first arg to be the hoisted value
   */
  public function test_hoist_modifier () {
    $s = new CallSpec('mostDeviant', '#<');
    $this->assertSame('mostDeviant(1234,*)', $s->asString('*', array(1234)));

    $s = new CallSpec('f', array('-', '-<'));
    $this->assertSame('f(2,*,1)', $s->asString('*', 1, 2));
  }


  /**
   * Given: a call spec with the '?' modifier
   * Expect: empty string, null and bool values to be omitted from call
   */
  public function test_optional_modifier () {
    $s = new CallSpec('f', array('-?'));

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
    $s = new CallSpec('f', array('^'));

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
    $s = new CallSpec('f', array('#'));

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
        new CallSpec('0', array('-'), 1),
        new CallSpec('1', array('-'), 10),
        new CallSpec('2', array('-'), 20),
        new CallSpec('3', array('-'), 30),
        new CallSpec('4', array('-'), 40),
        new CallSpec('5', array('-'), 50),
        new CallSpec('6', array('-'), 99),
      );

    $clone = unserialize(serialize($sorted));
    // make sure we start sorted
    // NOTE: assertSame doesn't work here
    $this->assertEquals($sorted, $clone);

    usort($clone, array(CallSpec::class, 'cmp'));
    $this->assertEquals($sorted, $clone, 'sorted -> sorted');

    $clone = array_reverse($clone);
    usort($clone, array(CallSpec::class, 'cmp'));
    $this->assertEquals($sorted, $clone, 'reverse -> sorted');

    shuffle($clone);
    usort($clone, array(CallSpec::class, 'cmp'));
    $this->assertEquals($sorted, $clone, 'shuffle -> sorted');
  }

  /**
   * Given: a collection of argument strings
   * Expect: proper parsing
   */
  public function test_parse_arg_strings () {
    $this->assertEquals(
        array('1','2','3'),
        CallSpec::parseArgString("1,2,3"));

    $this->assertEquals(
        array('1,2,3'),
        CallSpec::parseArgString("1\\,2\\,3"));

    $this->assertEquals(
        array('1,2,3'),
        CallSpec::parseArgString('"1,2,3"'));

    $this->assertEquals(
        array('1,2,3'),
        CallSpec::parseArgString("'1,2,3'"));

    $this->assertEquals(
        array('1','the number "2"','3'),
        CallSpec::parseArgString("1,the number \\\"2\\\",3"));

    $this->assertEquals(
        array('^.*TCP(\d+)', ' \1'),
        CallSpec::parseArgString("'^.*TCP(\d+)', '\\1'"));

    $this->assertEquals(
        array('1','2','3,4,5','6,7','"8',"'9",'10,11','\\n'),
        CallSpec::parseArgString(
            '1,2,"3,4,5",\'6,7\',\\"8,\\\'9,10\\,11,"\n"'));

  } //end test_parse_arg_strings

} //end CallSpecTest
