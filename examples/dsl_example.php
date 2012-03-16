<?php
/**
 * Example usage of the DSL API to construct a graph of memory usage.
 */

require_once dirname(__FILE__) . '/../src/autoload.php';

$g = new Graphite_GraphBuilder();

$g->title('Memory')
  ->vtitle('Mbytes')
  ->width(800)
  ->height(600)
  ->bgcolor('white')
  ->fgcolor('black')
  ->from('-2days')
  ->area('stacked')
  ->prefix('collectd')
  ->prefix('com.example.host')
  ->prefix('snmp')
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
    <img src="http://graphite.example.com/render?<?php echo $g->url(); ?>">
  </body>
</html>
