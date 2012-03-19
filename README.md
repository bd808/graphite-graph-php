Graphite_GraphBuilder
=====================

[Graphite_GraphBuilder][] is a DSL and ini-based templating language to assist
in constructing query strings for use with [Graphite][].

[![Build Status][ci-status]][ci-home]

About
-----

[Graphite][] provides several interfaces for creating graphs and dashboards,
but one of its powerful features is an [API][url-api] for generating graphs
and retrieving raw data. This allows easy embedding of graphs in custom
dashboards and other applications.

The process of describing complex graphs is however cumbersome at best.
Graphite_GraphBuilder attempts to reduce the complexity of embedding Graphite
graphs in [PHP][] based applications.


Examples
--------
### DSL Usage
```php
<?php
$g = new Graphite_GraphBuilder(array('width'=>800, 'height'=>600));

$g->title('Memory')
  ->vtitle('Mbytes')
  ->bgcolor('white')
  ->fgcolor('black')
  ->from('-2days')
  ->area('stacked')
  ->prefix('metrics.com.example.host')
  ->prefix('collectd.snmp')
  ->metric('memory-free', array(
    'cactistyle' => true,
    'color' => '00c000',
    'alias' => 'Free',
    'scale' => '0.00000095367',
  ))
  ->metric('memory-used', array(
    'cactistyle' => true,
    'color' => 'c00000',
    'alias' => 'Used',
    'scale' => '0.00000095367',
  ))
  ;
?>
<!DOCTYPE html>
<html>
  <head>
    <title></title>
  <head>
  <body>
    <img src="http://graphite.example.com/render?<?php echo $g->qs(); ?>">
  </body>
</html>
```

### Ini Usage
```ini
title = "Network Traffic {{IF_DESC}}"
vtitle = "Bits Per Sec"
bgcolor = "{{white}}"
fgcolor = "333333"
line_mode = "staircase"

[snmp]
:is_prefix = 1
prefix = "collectd.snmp"

[interface-octets]
:is_prefix = 1
; prefixes can use other prefixes if that makes keeping track easier
:prefix = "snmp"
prefix = "if_octets-{{IF}}"

[rx]
:prefix = "interface-octets"
nonnegativederivative = true
color = "green"
alias = "Inbound"

[tx]
:prefix = "interface-octets"
nonnegativederivative = true
scale = -1
color = "blue"
alias = "Outbound"

[95th_in-out]
:prefix = "interface-octets"
metric = "*"
nonnegativederivative = true
sumseries = true
npercentile = 95
color = "red"
dashed = 10
alias = "95th Percentile in+out"
```

```php
<?php
$g = new Graphite_GraphBuilder(array('width'=>800, 'height'=>600));
$g->prefix('metrics.com.example.host')
  ->ini('interface.ini', array('IF' => 'Tunnel0', 'IF_DESC' => 'tu0'));
?>
<!DOCTYPE html>
<html>
  <head>
    <title></title>
  <head>
  <body>
    <img src="http://graphite.example.com/render?<?php echo $g->qs(); ?>">
  </body>
</html>
```

Credits
-------
Written by [Bryan Davis][bd808] with support from [Keynetics][].

Inspired by https://github.com/ripienaar/graphite-graph-dsl/

---
[Graphite_GraphBuilder]: https://github.com/bd808/graphite-graph-php/
[Graphite]: http://graphite.wikidot.com/
[url-api]: http://readthedocs.org/docs/graphite/en/latest/url-api.html
[PHP]: http://php.net/
[ci-status]: https://secure.travis-ci.org/bd808/graphite-graph-php.png
[ci-home]: http://travis-ci.org/bd808/graphite-graph-php
[bd808]: http://bd808.github.com/
[Keynetics]: http://keynetics.com/
