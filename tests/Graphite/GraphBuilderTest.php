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
        ->qs();

    $this->assertValidQueryStringChars($got);

    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),\'red\'),\'IRQ\')&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),\'yellow\'),\'Batched+IRQ\')', $got);
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
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(color(scale(derivative(irq),0.001),\'red\'),\'IRQ\')', $g->qs);
  } //end testAltDsl


  /**
   * Given: a resaonablu complex ini file
   * Expect: a well formed query string
   */
  public function testIni () {
    $g = new Graphite_GraphBuilder();
    $g->prefix('com.example.foo')
      ->ini(dirname(__FILE__) . '/testIni.ini');
    $this->assertEquals('title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),\'red\'),\'IRQ\')&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),\'yellow\'),\'Batched+IRQ\')&target=alias(color(drawAsInfinite(puppet.time.total),\'blue\'),\'Puppet+Run\')', $g->qs);
  } //end testIni


  /**
   * Given: a minimal DSL usage
   * Expect: a well formed query string
   */
  public function testDefaults () {
    $g = new Graphite_GraphBuilder();
    $g->metric('sample', array('data' => 'sample'));
    $this->assertValidQueryStringChars($g->qs);
    $this->assertEquals('target=alias(sample,\'Sample\')', $g->qs);
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
        ->qs();
    $this->assertValidQueryStringChars($got);
    $this->assertContains('target=explict_target(my.target)', $got);
  } //end testExplicitTarget


  /**
   * Given: a format on the qs call
   * Expect: a well formed query string with a format
   */
  public function testFormat () {
    $g = new Graphite_GraphBuilder();
    $g->metric('sample', array('data' => 'sample'));
    $this->assertContains('format=json', $g->qs('json'));
    $this->assertContains('format=xml', $g->qs('xml'));
    $this->assertContains('format=csv', $g->qs('csv'));
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
    $this->assertEquals('target=alias(color(holtWintersForecast(sample),\'blue\'),\'Sample+Forecast\')&target=alias(color(dashed(holtWintersConfidenceBands(sample)),\'grey\'),\'Sample+Confidence\')&target=alias(color(holtWintersConfidenceAbberation(keepLastValue(sample)),\'orange\'),\'Sample+Aberration\')&target=alias(color(dashed(threshold(100)),\'red\'),\'Sample+Critical\')&target=alias(color(dashed(threshold(75)),\'orange\'),\'Sample+Warning\')&target=alias(color(sample,\'yellow\'),\'Sample\')', $g->qs);
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
