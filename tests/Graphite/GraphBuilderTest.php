<?php

namespace Graphite;

class GraphBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Given: a moderately complex DSL usage
     * Expect: a well formed query string
     */
    public function test_dsl_funcs()
    {
        $g = new GraphBuilder();

        $got = $g->title('CPU IRQ Usage')
            ->vtitle('percent')
            ->from('-2days')
            ->width(100)
            ->height(100)
            ->area('stacked')
            ->prefix('com.example.foo')
            ->prefix('munin.cpu')
            ->series('irq', [
                'derivative' => true,
                'scale' => 0.001,
                'color' => 'red',
                'alias' => 'IRQ',
            ])
            ->buildSeries('softirq')
            ->derivative(true)
            ->scale(0.001)
            ->color('yellow')
            ->alias('Batched IRQ')
            ->build()
            ->endPrefix()
            ->build();

        $this->assertValidQueryStringChars($got);

        $this->assertEquals(
            'title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked'
            . '&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),\'red\'),\'IRQ\')'
            . '&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),\'yellow\'),'
            . '\'Batched+IRQ\')',
            $got
        );
    }

    public function test_dsl_members()
    {
        $g = new GraphBuilder();
        $g->title = 'CPU IRQ Usage';
        $g->vtitle = 'percent';
        $g->from = '-2days';
        $g->width = 100;
        $g->height = 100;
        $g->area = 'stacked';
        $g->metric('irq', [
            'data' => 'irq',
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'red',
            'alias' => 'IRQ',
        ]);
        $this->assertEquals(
            'title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked'
            . '&target=alias(color(scale(derivative(irq),0.001),\'red\'),\'IRQ\')',
            $g->build()
        );
    }

    /**
     * Given: a reasonably complex ini file
     * Expect: a well formed query string
     */
    public function test_ini_load()
    {
        $g = GraphBuilder::builder()
            ->prefix('com.example.foo')
            ->ini($this->iniPath('test_ini_load.ini'));
        $this->assertEquals(
            'title=CPU+IRQ+Usage&vtitle=percent&from=-2days&width=100&height=100&areaMode=stacked'
            . '&target=alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),\'red\'),\'IRQ\')'
            . '&target=alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),\'yellow\'),' .
            '\'Batched+IRQ\')&target=alias(color(drawAsInfinite(puppet.time.total),\'blue\'),\'Puppet+Run\')',
            (string) $g->build()
        );
    }

    /**
     * Given: a minimal DSL usage
     * Expect: a well formed query string
     */
    public function test_defaults()
    {
        $g = new GraphBuilder();
        $g->metric('sample', ['data' => 'sample']);
        $this->assertValidQueryStringChars($g->build());
        $this->assertEquals('target=alias(sample,\'Sample\')', $g->build());
    }

    /**
     * Given: the target element is set
     * Expect: the query string contains the supplied target
     */
    public function test_explicit_target()
    {
        $g = new GraphBuilder();
        $got = $g->metric('irq', [
            'derivative' => true,
            'scale' => 0.001,
            'color' => 'red',
            'alias' => 'IRQ',
            'target' => 'explict_target(my.target)',
        ])
            ->build();
        $this->assertValidQueryStringChars($got);
        $this->assertContains('target=explict_target(my.target)', $got);
    }

    /**
     * Given: a format on the build call
     * Expect: a well formed query string with a format
     */
    public function test_output_format()
    {
        $g = new GraphBuilder();
        $g->metric('sample', ['data' => 'sample']);
        $this->assertContains('format=json', $g->build('json'));
        $this->assertContains('format=xml', $g->build('xml'));
        $this->assertContains('format=csv', $g->build('csv'));
    }

    /**
     * Given: a basic forecast call
     * Expect: a well formed query string
     */
    public function test_forecast()
    {
        $g = new GraphBuilder();
        $g->forecast('sample', [
            'series' => 'sample',
            'critical' => [100],
            'warning' => [75],
        ]);
        $this->assertEquals(
            'target=alias(color(holtWintersForecast(sample),\'blue\'),\'Sample+Forecast\')' .
            '&target=alias(color(dashed(holtWintersConfidenceBands(sample)),\'grey\'),\'Sample+Confidence\')' .
            '&target=alias(color(holtWintersConfidenceAbberation(keepLastValue(sample)),\'orange\'),' .
            '\'Sample+Aberration\')&target=alias(color(dashed(threshold(100)),\'red\'),\'Sample+Critical\')' .
            '&target=alias(color(dashed(threshold(75)),\'orange\'),\'Sample+Warning\')' .
            '&target=alias(color(sample,\'yellow\'),\'Sample\')',
            $g->build()
        );
    }

    /**
     * Given: ini with an aliasing function
     * Expect: default alias is omitted
     */
    public function test_alias_override()
    {
        $g = new GraphBuilder();
        $g->ini($this->iniPath('test_alias_override.ini'));
        $this->assertEquals('target=cactiStyle(aliasByNode(something.prod.*.requests.count,3))&target=*', $g->build());
    }

    /**
     * Given: ini driven config that sets and unsets a boolean parameter
     * Expect: param to be true and then false
     */
    public function test_bool_param_unset()
    {
        $g = GraphBuilder::builder()
            ->ini($this->iniPath('test_bool_param_unset1.ini'));
        $this->assertEquals('drawNullAsZero=True', (string) $g);

        $g->ini($this->iniPath('test_bool_param_unset2.ini'));
        $this->assertEquals('', (string) $g);
    }

    /**
     * Given: ini config with `[]` array syntax
     * Expect: multiple arguments to be processed
     */
    public function test_ini_multiarg_array()
    {
        $g = GraphBuilder::builder()
            ->ini($this->iniPath('test_multiarg_array.ini'));
        $this->assertEquals('target=aliasByNode(my-metric,1,3,5)', (string) $g);
    }

    /**
     * Given: ini config with comma-separated string and multi-arg function
     * Expect: string to be split on commas and processed
     */
    public function test_ini_multiarg_split()
    {
        $g = GraphBuilder::builder()
            ->ini($this->iniPath('test_multiarg_split.ini'));
        $this->assertEquals(
            "target=aliasByNode(my-metric,1,3,5)&target=aliasSub(ip.*TCP*,'%5E.*TCP(%5Cd%2B)','%5C1')",
            (string) $g
        );
    }

    /**
     * Given: ini config using series inheritance
     * Expect: series config values to be inherited
     */
    public function test_inherit()
    {
        $g = GraphBuilder::builder()
            ->ini($this->iniPath('test_inherit_base.ini'))
            ->ini($this->iniPath('test_inherit_extends.ini'));

        $this->assertEquals(
            "target=alias(color(scale(derivative(munin.cpu.irq),0.001),'red'),'IRQ')" .
            "&target=alias(color(scale(derivative(munin.cpu.softirq),0.001),'yellow'),'Batched+IRQ')",
            (string) $g
        );
    }

    /**
     * Given: ini config using generators
     * Expect: targets using generators a a primary data source
     */
    public function test_generator()
    {
        $g = GraphBuilder::builder()
            ->ini($this->iniPath('test_generator.ini'));
        $this->assertEquals(
            "target=alias(constantLine(123.456),'Constantline')&target=timeFunction('The.time.series')" .
            "&target=randomWalkFunction('Random')&target=sinFunction('this.is.the.sin',2)" .
            "&target=threshold(15,'omgwtfbbq','red')&target=alias(events('*'),'Events')",
            (string) $g
        );
    }

    /**
     * Given: ini config using :series
     * Expect: grouped targets
     */
    public function test_group_series()
    {
        $g = GraphBuilder::builder()
            ->prefix('com.example.foo')
            ->ini($this->iniPath('test_group_series.ini'));
        $this->assertEquals(
            'target=' .
            'cactiStyle(group(alias(color(scale(derivative(com.example.foo.munin.cpu.irq),0.001),\'red\'),\'IRQ\'),' .
            'alias(color(scale(derivative(com.example.foo.munin.cpu.softirq),0.001),\'yellow\'),\'Batched+IRQ\')))&' .
            'target=alias(color(drawAsInfinite(puppet.time.total),\'blue\'),\'Puppet+Run\')',
            (string) $g
        );
    }

    /**
     * Given: empty builder
     * Expect: empty response
     */
    public function test_empty()
    {
        $g = GraphBuilder::builder();
        $this->assertEquals('', (string) $g);
    }

    /**
     * Get the path to an ini file.
     * @param string $file File name
     * @return string Path to file
     */
    protected function iniPath($file)
    {
        return __DIR__ . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Assert that the given query string only contains RFC-3986 valid
     * characters.
     * @param string $qs Query string to validate
     */
    protected function assertValidQueryStringChars($qs)
    {
        // character classes from https://www.ietf.org/rfc/rfc3986.txt
        $unreserved = '[a-zA-Z0-9\-\._~]';
        $pct_encoded = '%[0-9a-fA-F]{2}';
        $sub_delims = '[!\$&\'\(\)\*\+,;=]';
        $pchar = "{$unreserved}|{$pct_encoded}|{$sub_delims}|[:@]";
        $query = "{$pchar}|/|\?";

        $this->assertRegExp(
            "#^({$query})*$#",
            $qs,
            'Expected only RFC-3986 valid characters'
        );
    }

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
    protected function parseQueryString($qs)
    {
        $vars = [];
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
                    $vars[$name] = [$vars[$name]];
                }
                $vars[$name][] = $value;
            } else {
                $vars[$name] = $value;
            }
        }

        return $vars;
    }
}
