<?php
require_once( dirname(dirname(dirname(dirname( __FILE__ )))) . '/wp-load.php' );
global $wpdb;

$table_name = $wpdb->prefix . 'waza_bookings';
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");

echo "<h1>Table: $table_name</h1>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . $col->Field . "</td>";
    echo "<td>" . $col->Type . "</td>";
    echo "<td>" . $col->Null . "</td>";
    echo "<td>" . $col->Key . "</td>";
    echo "<td>" . $col->Default . "</td>";
    echo "<td>" . $col->Extra . "</td>";
    echo "</tr>";
}
echo "</table>";
