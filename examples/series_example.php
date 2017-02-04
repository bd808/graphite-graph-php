<?php
/**
 * Example usage of the DSL API to construct a graph of memory usage.
 *
 * Graph last 2 days of snmp collected memory utilization on host
 * host.example.com.
 *
 * Show both free and used memory as stacked series after scaling values from
 * bytes to megabytes (or more properly mebibytes).
 *
 * This example shows two different ways that the Graphite_Graph_Series DSL can
 * be used to construct a target series for graphing.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$g = Graphite\GraphBuilder::builder()
    ->title('Memory')
    ->vtitle('MiB')
    ->width(800)
    ->height(600)
    ->bgcolor('white')
    ->fgcolor('black')
    ->from('-2days')
    ->area('stacked')
    ->prefix('collectd')
    ->prefix('com.example.host')
    ->prefix('snmp')
    ->buildSeries('memory-free')
        ->cactistyle()
        ->color('green')
        ->alias('Free')
        ->scale(1 / (1024 * 1024)) // B to MiB
        ->build()
    ->buildSeries('memory-used')
        ->scale(1 / (1024 * 1024))
        ->color('blue')
        ->alias('Used')
        ->cactiStyle()
        ->build()
    ;
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Memory on host.example.com</title>
  </head>
  <body>
    <img src="http://graphite.example.com/render?<?php echo $g; ?>">
  </body>
</html>
