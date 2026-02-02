<?php
/**
 * QR Scanner Verification Script
 * 
 * Upload this file to the plugin root and access via:
 * https://wazastudio.com/wp-content/plugins/waza-studio-app/verify-qr-scanner.php
 * 
 * This will show if the latest QRScannerManager.php file is loaded
 */

// Load WordPress
$wp_load = __DIR__ . '/../../../wp-load.php';
require_once($wp_load);

if (!current_user_can('manage_options')) {
    die('Access denied. Please log in as administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Scanner Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #2271b1; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 QR Scanner Verification</h1>
    
    <h2>1. Check if QRScannerManager class exists</h2>
    <?php
    if (class_exists('WazaBooking\Admin\QRScannerManager')) {
        echo '<p class="success">✅ QRScannerManager class exists</p>';
        
        // Check if the method exists
        $reflection = new ReflectionClass('WazaBooking\Admin\QRScannerManager');
        
        echo '<h2>2. Check Methods</h2>';
        $methods = ['init', 'verify_qr_code', 'mark_attendance', 'get_scanner_stats'];
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                echo "<p class='success'>✅ Method <code>{$method}()</code> exists</p>";
            } else {
                echo "<p class='error'>❌ Method <code>{$method}()</code> NOT FOUND</p>";
            }
        }
        
        // Check file modification time
        $file_path = WAZA_BOOKING_PLUGIN_DIR . 'src/Admin/QRScannerManager.php';
        if (file_exists($file_path)) {
            $mod_time = filemtime($file_path);
            $mod_date = date('Y-m-d H:i:s', $mod_time);
            echo "<div class='info'><strong>File last modified:</strong> {$mod_date}</div>";
            
            // Check if file contains the latest fixes
            $content = file_get_contents($file_path);
            
            echo '<h2>3. Check for Latest Fixes</h2>';
            
            // Check for timezone conversion
            if (strpos($content, 'Convert UTC datetime to site timezone') !== false) {
                echo '<p class="success">✅ Timezone conversion code found</p>';
            } else {
                echo '<p class="error">❌ Timezone conversion code NOT FOUND</p>';
            }
            
            // Check for stats method
            if (strpos($content, 'function get_scanner_stats') !== false) {
                echo '<p class="success">✅ get_scanner_stats() method found</p>';
            } else {
                echo '<p class="error">❌ get_scanner_stats() method NOT FOUND</p>';
            }
            
            // Check for insertAdjacentElement
            if (strpos($content, 'insertAdjacentElement') !== false) {
                echo '<p class="success">✅ insertAdjacentElement (display below scanner) found</p>';
            } else {
                echo '<p class="error">❌ insertAdjacentElement NOT FOUND</p>';
            }
            
            // Check for validation_message
            if (strpos($content, 'validation_message') !== false) {
                echo '<p class="success">✅ validation_message code found</p>';
            } else {
                echo '<p class="error">❌ validation_message NOT FOUND</p>';
            }
            
            // Check for parseFloat fix
            if (strpos($content, 'parseFloat(booking.total_amount || 0)') !== false) {
                echo '<p class="success">✅ parseFloat amount fix found</p>';
            } else {
                echo '<p class="error">❌ parseFloat amount fix NOT FOUND</p>';
            }
        }
        
    } else {
        echo '<p class="error">❌ QRScannerManager class NOT FOUND</p>';
    }
    ?>
    
    <h2>4. Test Timezone Conversion</h2>
    <?php
    // Test timezone conversion
    $test_utc = '2026-01-22 06:30:00'; // 6:30 AM UTC
    $dt = new DateTime($test_utc, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
    
    echo '<div class="info">';
    echo "<strong>WordPress Timezone:</strong> " . wp_timezone_string() . "<br>";
    echo "<strong>Current Time (IST):</strong> " . current_time('Y-m-d H:i:s') . "<br>";
    echo "<strong>Test conversion:</strong><br>";
    echo "UTC: {$test_utc}<br>";
    echo "IST: " . $dt->format('Y-m-d H:i:s') . " (" . $dt->format('F j, Y g:i A') . ")";
    echo '</div>';
    ?>
    
    <h2>5. Check Database for Test Booking</h2>
    <?php
    global $wpdb;
    $booking = $wpdb->get_row("
        SELECT b.*, s.start_datetime, s.end_datetime 
        FROM {$wpdb->prefix}waza_bookings b
        LEFT JOIN {$wpdb->prefix}waza_slots s ON b.slot_id = s.id
        ORDER BY b.id DESC LIMIT 1
    ");
    
    if ($booking) {
        echo '<div class="info">';
        echo "<strong>Latest Booking ID:</strong> {$booking->id}<br>";
        echo "<strong>User Name:</strong> {$booking->user_name}<br>";
        echo "<strong>User Phone:</strong> " . ($booking->user_phone ?: 'Not set') . "<br>";
        echo "<strong>Total Amount:</strong> ₹" . number_format($booking->total_amount, 2) . "<br>";
        echo "<strong>Slot Start (UTC in DB):</strong> {$booking->start_datetime}<br>";
        
        // Convert to IST
        $start_dt = new DateTime($booking->start_datetime, new DateTimeZone('UTC'));
        $start_dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        echo "<strong>Slot Start (IST converted):</strong> " . $start_dt->format('F j, Y g:i A') . "<br>";
        echo '</div>';
    } else {
        echo '<p>No bookings found in database.</p>';
    }
    ?>
    
    <h2>6. Action Required</h2>
    <div class="info">
        <strong>If you see any ❌ errors above:</strong><br>
        1. Download the latest <code>QRScannerManager.php</code> from GitHub<br>
        2. Upload to: <code>/wp-content/plugins/waza-studio-app/src/Admin/</code><br>
        3. Clear browser cache and server cache (if any)<br>
        4. Refresh this page to verify<br><br>
        
        <strong>If all checks pass (✅) but scanner still has issues:</strong><br>
        1. Clear browser cache (Ctrl+Shift+Delete)<br>
        2. Hard refresh scanner page (Ctrl+F5)<br>
        3. Check browser console (F12) for JavaScript errors<br>
        4. Try in incognito/private mode
    </div>
    
    <hr>
    <p><em>Generated: <?php echo date('Y-m-d H:i:s'); ?></em></p>
</body>
</html>
