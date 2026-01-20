<?php
/**
 * Update existing slots with instructor_id
 * 
 * This script populates the instructor_id column in waza_slots table
 * for existing records that only have the instructor in activity meta
 */

// Load WordPress  
// Path from plugins/waza-studio-app to root: ../../..
$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) {
    die("WordPress not found at: {$wp_load}\n");
}
require_once $wp_load;

// For CLI execution, define a constant to bypass permission checks
define('WP_CLI', true);

global $wpdb;

echo "Updating slots with instructor_id...\n\n";

// Get all slots that don't have instructor_id set
$slots = $wpdb->get_results("
    SELECT s.id, s.activity_id, pm.meta_value as instructor_id
    FROM {$wpdb->prefix}waza_slots s
    INNER JOIN {$wpdb->posts} p ON s.activity_id = p.ID
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_waza_instructor'
    WHERE s.instructor_id IS NULL OR s.instructor_id = 0
");

if (empty($slots)) {
    echo "No slots to update.\n";
    exit;
}

$updated_count = 0;
$skipped_count = 0;

foreach ($slots as $slot) {
    if (empty($slot->instructor_id)) {
        echo "Slot {$slot->id}: No instructor assigned, skipping\n";
        $skipped_count++;
        continue;
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'waza_slots',
        ['instructor_id' => $slot->instructor_id],
        ['id' => $slot->id],
        ['%d'],
        ['%d']
    );
    
    if ($result !== false) {
        echo "Slot {$slot->id}: Updated with instructor ID {$slot->instructor_id}\n";
        $updated_count++;
    } else {
        echo "Slot {$slot->id}: Failed to update\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total slots: " . count($slots) . "\n";
echo "Updated: {$updated_count}\n";
echo "Skipped: {$skipped_count}\n";
echo "\nDone!\n";
