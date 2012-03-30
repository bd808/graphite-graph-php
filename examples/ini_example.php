<?php
/**
 * Example usage of ini file graph template with variable expansion.
 */

require_once dirname(__FILE__) . '/../src/autoload.php';

$g = Graphite_GraphBuilder::builder()
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
