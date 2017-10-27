<?php
/**
 * Example usage of ini file graph template with variable expansion.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$g = Graphite\GraphBuilder::builder()
    ->prefix('com.example.host')
    ->ini('interface.ini', array('IF' => 'Tunnel0', 'IF_DESC' => 'tu0'));
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
