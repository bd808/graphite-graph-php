<?php
/**
 * Example usage of the DSL API to construct a graph of memory usage.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$g = Graphite\GraphBuilder::builder()
    ->title('Memory')
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
    ->series('memory-free', array(
        'cactistyle' => true,
        'color' => '00c000',
        'alias' => 'Free',
        'scale' => '0.00000095367',
      ))
    ->series('memory-used', array(
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
    <img src="http://graphite.example.com/render?<?php echo $g; ?>">
  </body>
</html>
