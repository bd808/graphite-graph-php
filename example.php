<?php
require_once 'GraphiteGraph.php';

$g = new GraphiteGraph('example.graph',
    array('width' => 800, 'height' => 400),
    array( 'hostname' => 'com.example.foo'));
echo $g->url, "\n";
