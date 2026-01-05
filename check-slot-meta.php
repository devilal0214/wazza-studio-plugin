<?php
/**
 * Check Slot Meta Keys
 * Diagnoses why slots aren't showing in calendar
 */

require_once __DIR__ . '/../../../wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

global $wpdb;

echo "=== SLOT META KEY DIAGNOSTIC ===\n\n";

// Get all slots
$slots = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_date, p.post_status 
    FROM {$wpdb->posts} p 
    WHERE p.post_type = 'waza_slot' 
    ORDER BY p.post_date DESC 
    LIMIT 10
");

echo "Found " . count($slots) . " slots total\n\n";

if (empty($slots)) {
    echo "ERROR: No slots found in database!\n";
    echo "Post types in database:\n";
    $types = $wpdb->get_results("SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_status = 'publish'");
    foreach ($types as $type) {
        echo "  - " . $type->post_type . "\n";
    }
    exit;
}

foreach ($slots as $slot) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "SLOT ID: {$slot->ID}\n";
    echo "Title: {$slot->post_title}\n";
    echo "Status: {$slot->post_status}\n";
    echo "Created: {$slot->post_date}\n";
    echo "\nMeta Data:\n";
    
    // Get all meta for this slot
    $meta = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_key",
        $slot->ID
    ));
    
    if (empty($meta)) {
        echo "  ⚠️  NO META DATA FOUND!\n";
    } else {
        foreach ($meta as $m) {
            // Highlight waza-specific meta
            $prefix = (strpos($m->meta_key, '_waza_') === 0 || strpos($m->meta_key, 'waza_') === 0) ? '✓' : ' ';
            echo "  {$prefix} {$m->meta_key} = {$m->meta_value}\n";
        }
    }
    
    echo "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== META KEY SUMMARY ===\n\n";

// Get all unique meta keys used
$all_meta_keys = $wpdb->get_results("
    SELECT DISTINCT pm.meta_key, COUNT(*) as count
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.post_type = 'waza_slot'
    GROUP BY pm.meta_key
    ORDER BY pm.meta_key
");

echo "All meta keys used for waza_slot post type:\n";
foreach ($all_meta_keys as $key) {
    echo "  - {$key->meta_key} (used in {$key->count} slots)\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== EXPECTED VS ACTUAL ===\n\n";

$expected_keys = [
    '_waza_start_date',
    '_waza_start_time', 
    '_waza_end_time',
    '_waza_activity_id',
    '_waza_instructor_id',
    '_waza_capacity',
    '_waza_booked_seats'
];

echo "Expected meta keys:\n";
foreach ($expected_keys as $key) {
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE p.post_type = 'waza_slot' AND pm.meta_key = %s",
        $key
    ));
    
    $status = $exists > 0 ? "✓ FOUND ($exists slots)" : "✗ MISSING";
    echo "  {$status} {$key}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== DATE QUERY TEST ===\n\n";

$today = current_time('Y-m-d');
$future = date('Y-m-d', strtotime('+30 days'));

echo "Testing date range: {$today} to {$future}\n\n";

// Test different date formats
$date_formats = [
    'Y-m-d' => $today,
    'Y-m-d H:i:s' => current_time('Y-m-d H:i:s'),
    'm/d/Y' => current_time('m/d/Y'),
    'd/m/Y' => current_time('d/m/Y')
];

foreach ($date_formats as $format => $value) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE p.post_type = 'waza_slot' 
         AND p.post_status = 'publish'
         AND pm.meta_key = '_waza_start_date'
         AND pm.meta_value = %s",
        $value
    ));
    
    echo "Format '{$format}' ({$value}): {$count} slots\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "=== SAMPLE META VALUES ===\n\n";

$sample = $wpdb->get_results("
    SELECT pm.meta_key, pm.meta_value, COUNT(*) as count
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.post_type = 'waza_slot'
    AND pm.meta_key IN ('_waza_start_date', '_waza_start_time', '_waza_end_time')
    GROUP BY pm.meta_key, pm.meta_value
    ORDER BY pm.meta_key, pm.meta_value
    LIMIT 20
");

foreach ($sample as $s) {
    echo "{$s->meta_key}: '{$s->meta_value}' ({$s->count} slots)\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
