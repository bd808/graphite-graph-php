<?php
/**
 * @package Graphite
 */

/**
 * @package Graphite
 */
class Graphite_GraphBuilderTest extends PHPUnit_Framework_TestCase {

  public function testDsl () {
    $g = new Graphite_GraphBuilder(null,
        array('width' => 800, 'height' => 400),
        array( 'hostname' => 'com.example.foo'));

    $got = $g->title('CPU IRQ Usage')
        ->vtitle('percent')
        ->width(100)
        ->height(100)
        ->from('-2days')
        ->area('stacked')
        ->description('A really cool graph')
        ->service('munin', 'cpu')
        ->field('irq', array(
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'red',
            'alias' => 'IRQ',
          ))
        ->field('softirq', array(
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'yellow',
            'alias' => 'Batched IRQ',
          ))
        ->endService()
        ->url();
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(scale(derivative(com.example.foo.munin.cpu.irq),0.001),%22IRQ%22)&target=alias(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),%22Batched+IRQ%22)&colorList=red,yellow', $got);
  } //end testDsl

} //end Graphite_GraphBuilderTest
