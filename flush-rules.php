<?php
/**
 * Flush Rewrite Rules - Run this once after updating post type registration
 */

require_once(__DIR__ . '/../../../wp-load.php');

if (!is_admin()) {
    die('Must be admin');
}

// Flush rewrite rules
flush_rewrite_rules();

echo "✅ Rewrite rules flushed successfully!<br><br>";
echo "Now try accessing: <a href='/wazza/wp-admin/post-new.php?post_type=waza_activity'>Add New Activity</a><br>";
echo "Or try editing an existing activity.";
