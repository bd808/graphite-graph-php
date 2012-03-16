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
    $g = new Graphite_GraphBuilder();

    $got = $g->title('CPU IRQ Usage')
        ->vtitle('percent')
        ->from('-2days')
        ->width(100)
        ->height(100)
        ->area('stacked')
        ->prefix('com.example.foo')
        ->prefix('munin.cpu')
        ->metric('irq', array(
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'red',
            'alias' => 'IRQ',
          ))
        ->metric('softirq', array(
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'yellow',
            'alias' => 'Batched IRQ',
          ))
        ->endPrefix()
        ->url();

    $this->assertValidQueryStringChars($got);

    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),%22red%22),%22IRQ%22)&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),%22yellow%22),%22Batched+IRQ%22)', $got);
  } //end testDsl


  public function testAltDsl () {
    $g = new Graphite_GraphBuilder();
    $g->title = 'CPU IRQ Usage';
    $g->vtitle = 'percent';
    $g->from = '-2days';
    $g->width = 100;
    $g->height = 100;
    $g->area = 'stacked';
    $g->metric('irq', array(
        'data' => 'irq',
        'derivative' => true,
        'scale' => 0.001,
        'color' => 'red',
        'alias' => 'IRQ',
      ));
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(color(scale(derivative(irq),0.001),%22red%22),%22IRQ%22)', $g->url);
  } //end testAltDsl


  /**
   * Given: a resaonablu complex ini file
   * Expect: a well formed query string
   */
  public function testIni () {
    $g = new Graphite_GraphBuilder();
    $g->prefix('com.example.foo')
      ->ini(dirname(__FILE__) . '/testIni.ini');
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),%22red%22),%22IRQ%22)&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),%22yellow%22),%22Batched+IRQ%22)&target=alias(color(drawAsInfinite(puppet.time.total),%22blue%22),%22Puppet+Run%22)', $g->url);
  } //end testIni


  /**
   * Given: a minimal DSL usage
   * Expect: a well formed query string
   */
  public function testDefaults () {
    $g = new Graphite_GraphBuilder();
    $g->metric('sample', array('data' => 'sample'));
    $this->assertEquals('target=alias(sample,%22Sample%22)', $g->url);
  } //end testDefaults


  /**
   * Given: the target element is set
   * Expect: the query string contains the supplied target
   */
  public function testExplicitTarget () {
    $g = new Graphite_GraphBuilder();
    $got = $g->metric('irq', array(
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
    $g->metric('sample', array('data' => 'sample'));
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
        'series' => 'sample',
        'critical' => array(100),
        'warning' => array(75),
      ));
    $this->assertEquals('target=alias(color(holtWintersForecast(sample),%22blue%22),%22Sample+Forecast%22)&target=alias(dashed(color(holtWintersConfidenceBands(sample),%22grey%22),5.0),%22Sample+Confidence%22)&target=alias(color(holtWintersConfidenceAbberation(keepLastValue(sample)),%22orange%22),%22Sample+Aberration%22)&target=alias(dashed(color(threshold(100),%22red%22),5.0),%22Sample+Critical%22)&target=alias(dashed(color(threshold(75),%22orange%22),5.0),%22Sample+Warning%22)&target=alias(color(sample,%22yellow%22),%22Sample%22)', $g->url);
  } //end testForecast


  /**
   * Assert that the given query string only contains RFC-3986 valid
   * characters.
   * @param string $qs Query string to validate
   */
  protected function assertValidQueryStringChars ($qs) {
    // character classes from https://www.ietf.org/rfc/rfc3986.txt
    $unreserved = '[a-zA-Z0-9\-\._~]';
    $pct_encoded = '%[0-9a-fA-F]{2}';
    $sub_delims = '[!\$&\'\(\)\*\+,;=]';
    $pchar = "{$unreserved}|{$pct_encoded}|{$sub_delims}|[:@]";
    $query = "{$pchar}|/|\?";

    $this->assertRegExp("#^({$query})*$#", $qs,
        'Expected only RFC-3986 valid characters');
  } //end assertValidQueryStringChars


  /**
   * Parse a query string.
   *
   * Php's craptastic query string parsing (parse_str) can't deal with
   * multiple parms having the same name unless they have an array indictator.
   * So lame that it hurts. Why Rasmus? Why?
   *
   * Other "quirks" of Php's parse_str() parsing function are also avoided.
   * - Php's parser munges parameter names containing certain characters.
   * It converts periods and spaces in the name portion of a pair to
   * underscores.
   * - Php doesn't support semicolon separation of pairs as suggested in
   * RFC-1866.
   *
   * @param string $qs Query string
   * @return array Dictionary of parameters and values encoded by the
   *    query string. Parameters occuring multiple times will have an array of
   *    values in the order that those values were seen.
   */
  protected function parseQueryString ($qs) {
    $vars = array();
    // per RFC-1866 we support both ampersand and semicolon delimited pairs
    foreach (preg_split('/[&;]/') as $pair) {
      // only the first equals is significant
      $parts = explode('=', $pair, 2);
      if (2 != count($parts)) {
        // non-strict parsing allows "flag" elements to omit the trailing =
        $parts[] = '';
      }

      $name = urldecode($parts[0]);
      $value = urldecode($value);

      if (is_set($vars[$name])) {
        // have existing data for this name
        if (!is_array($vars[$name])) {
          $vars[$name] = array($vars[$name]);
        }
        $vars[$name][] = $value;

      } else {
        $vars[$name] = $value;
      }
    } //end foreach

    return $vars;
  } //end parseQueryString

} //end Graphite_GraphBuilderTest
