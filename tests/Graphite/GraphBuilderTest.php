<?php
/**
 * @package Graphite
 */

/**
 * @package Graphite
 */
class Graphite_GraphBuilderTest extends PHPUnit_Framework_TestCase {

  /**
   * Given: a moderately complex DSL usage
   * Expect: a well formed query string
   */
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
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&until=now&width=100&height=100&bgcolor=000000&fgcolor=FFFFFF&areaMode=stacked&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),%22red%22),%22IRQ%22)&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),%22yellow%22),%22Batched+IRQ%22)', $got);
  } //end testDsl


  public function testAltDsl () {
    $g = new Graphite_GraphBuilder();
    $g->title = 'CPU IRQ Usage';
    $g->vtitle = 'percent';
    $g->width = 100;
    $g->height = 100;
    $g->from = '-2days';
    $g->area = 'stacked';
    $g->description = 'A really cool graph';
    $g->field('irq', array(
        'data' => 'irq',
        'derivative' => true,
        'scale' => 0.001,
        'color' => 'red',
        'alias' => 'IRQ',
      ));
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&until=now&width=100&height=100&bgcolor=000000&fgcolor=FFFFFF&areaMode=stacked&target=alias(color(scale(derivative(irq),0.001),%22red%22),%22IRQ%22)', $g->url);
  } //end testAltDsl


  /**
   * Given: a resaonablu complex ini file
   * Expect: a well formed query string
   */
  public function testIni () {
    $g = new Graphite_GraphBuilder(dirname(__FILE__) . '/testIni.ini',
        array('width' => 800, 'height' => 400),
        array( 'hostname' => 'com.example.foo'));
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&until=now&width=100&height=100&bgcolor=000000&fgcolor=FFFFFF&areaMode=stacked&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),%22red%22),%22IRQ%22)&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),%22yellow%22),%22Batched+IRQ%22)&target=alias(color(drawAsInfinite(com.example.foo.puppet.time.total),%22blue%22),%22Puppet+Run%22)', $g->url);
  } //end testIni


  /**
   * Given: a minimal DSL usage
   * Expect: a well formed query string
   */
  public function testDefaults () {
    $g = new Graphite_GraphBuilder();
    $this->assertEquals(500, $g->width, 'Default width');
    $this->assertEquals(250, $g->height(), 'Default height');
    $g->field('sample', array('data' => 'sample'));
    $this->assertEquals('from=-1hour&until=now&width=500&height=250&bgcolor=000000&fgcolor=FFFFFF&areaMode=none&target=alias(sample,%22Sample%22)', $g->url);
  } //end testDefaults


  /**
   * Given: a service without required setup
   * Expect: an exception
   * @expectedException Graphite_ConfigurationException
   * @expectedExceptionMessage Hostname must be defined for services
   */
  public function testServiceWithoutHostname () {
    $g = new Graphite_GraphBuilder();
    $g->service('munin', 'cpu');
  } //end testServiceWithoutHostname


  /**
   * given: duplicate field declarations
   * expect: an exception
   * @expectedException graphite_configurationexception
   * @expectedExceptionMessage field named sample already exists
   */
  public function testDuplicateFields () {
    $g = new Graphite_Graphbuilder();
    $g->field('sample', array('data' => 'sample'));
    $g->field('sample', array('data' => 'sample2'));
  } //end testDuplicateFields


  /**
   * given: field with no data or target
   * expect: an exception
   * @expectedException graphite_configurationexception
   * @expectedExceptionMessage field sample does not have any data
   */
  public function testMissingDataAndTarget () {
    $g = new Graphite_GraphBuilder();
    $g->field('sample', array());
    $g->url();
  } //end testMissingDataAndTarget


  /**
   * Given: the suppress property is enabled
   * Expect: a null query string
   */
  public function testSuppress () {
    $g = new Graphite_GraphBuilder(null, array('suppress' => true));
    $g->field('sample', array('data' => 'sample'));
    $this->assertEquals(null, $g->url);
  } //end testSuppress


  /**
   * Given: the target element is set
   * Expect: the query string contains the supplied target
   */
  public function testExplicitTarget () {
    $g = new Graphite_GraphBuilder();
    $got = $g->field('irq', array(
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'red',
            'alias' => 'IRQ',
            'target' => 'explict_target(my.target)',
          ))
        ->url();
    $this->assertContains('target=explict_target%28my.target%29', $got);
  } //end testExplicitTarget


  /**
   * Given: a format on the url call
   * Expect: a well formed query string with a format
   */
  public function testFormat () {
    $g = new Graphite_GraphBuilder();
    $g->field('sample', array('data' => 'sample'));
    $this->assertContains('format=json', $g->url('json'));
    $this->assertContains('format=xml', $g->url('xml'));
    $this->assertContains('format=csv', $g->url('csv'));
  } //end testFormat

  /**
   * Given: a basic forecast call
   * Expect: a well formed query string
   */
  public function testForecast () {
    $g = new Graphite_GraphBuilder();
    $g->forecast('sample', array(
        'data' => 'sample',
        'critical' => array(100),
        'warning' => array(75),
      ));
    $this->assertEquals('from=-1hour&until=now&width=500&height=250&bgcolor=000000&fgcolor=FFFFFF&areaMode=none&target=alias(color(holtWintersForecast%28sample%29,%22blue%22),%22Sample+Forecast%22)&target=alias(dashed(color(holtWintersConfidenceBands%28sample%29,%22grey%22),5.0),%22Sample+Confidence%22)&target=alias(color(holtWintersConfidenceAbberation%28keepLastValue%28sample%29%29,%22orange%22),%22Sample+Aberration%22)&target=alias(dashed(color(threshold%28100%29,%22red%22),5.0),%22Sample+Critical%22)&target=alias(dashed(color(threshold%2875%29,%22orange%22),5.0),%22Sample+Warning%22)&target=alias(color(sample,%22yellow%22),%22Sample%22)', $g->url);
  } //end testForecast

} //end Graphite_GraphBuilderTest
