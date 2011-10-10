<?php
require_once 'GraphiteGraph.php';

$g = new GraphiteGraph(null,
      array('width' => 800, 'height' => 400),
      array( 'hostname' => 'com.example.foo'));

echo $g->title('CPU IRQ Usage')
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
    ->url(), "\n";
