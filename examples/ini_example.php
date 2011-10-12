<?php
require_once dirname(__FILE__) . '/../src/autoload.php';

$g = new Graphite_GraphBuilder('cpu_irq.ini',
    array('width' => 800, 'height' => 400),
    array( 'hostname' => 'com.example.foo'));
echo $g->url, "\n";
