<?php
/**
 * Test Multi-Step Booking Flow
 * 
 * This file tests the complete multi-step modal booking flow:
 * 1. Click calendar day → See slots in modal
 * 2. Click slot → See Step 1 (Summary)
 * 3. Click Next → See Step 2 (User Details: Name, Phone, Email)
 * 4. Fill details → Click Next → See Step 3 (Review & Payment)
 * 5. Submit → Process booking
 */

require_once '../../../wp-load.php';

// Only for admins
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Multi-Step Booking Flow - Waza Studio</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .test-header h1 {
            margin: 0 0 10px 0;
            color: #4F46E5;
        }
        .test-checklist {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .test-checklist h2 {
            margin-top: 0;
            color: #333;
        }
        .test-checklist ul {
            list-style: none;
            padding: 0;
        }
        .test-checklist li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .test-checklist li:before {
            content: '☐ ';
            margin-right: 8px;
            color: #4F46E5;
            font-weight: bold;
        }
        .calendar-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .instructions {
            background: #FFF7ED;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            margin-top: 0;
            color: #92400E;
        }
        .success-criteria {
            background: #ECFDF5;
            border-left: 4px solid #10B981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-criteria h3 {
            margin-top: 0;
            color: #065F46;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1>🎯 Multi-Step Booking Flow Test</h1>
        <p>Test the complete modal-based, multi-step booking process with simplified registration.</p>
    </div>

    <div class="test-checklist">
        <h2>Test Checklist</h2>
        <ul>
            <li>Modal opens when clicking a calendar day with available slots</li>
            <li>Slots are displayed in the modal with proper styling</li>
            <li>Clicking a slot transitions to Step 1 (Summary) within the same modal</li>
            <li>Progress indicator shows: Step 1 active, Steps 2-3 inactive</li>
            <li>Step 1 shows activity details, date, time, instructor, and price</li>
            <li>Clicking "Next" transitions to Step 2 (User Details)</li>
            <li>Step 2 shows only: Name, Phone, Email fields</li>
            <li>Account creation is pre-checked with auto-password generation message</li>
            <li>Form validates required fields before allowing next step</li>
            <li>Clicking "Review & Pay" transitions to Step 3 (Confirmation)</li>
            <li>Step 3 shows booking summary and payment method</li>
            <li>Progress indicator updates correctly for each step</li>
            <li>"Previous" button works and returns to earlier steps</li>
            <li>"Back to Slots" button returns to slot selection modal</li>
            <li>All transitions happen smoothly without page reload</li>
        </ul>
    </div>

    <div class="instructions">
        <h3>📋 Testing Instructions</h3>
        <ol>
            <li><strong>Step 1:</strong> Click on a calendar day with available slots (green background)</li>
            <li><strong>Step 2:</strong> Verify slots appear in a modal popup</li>
            <li><strong>Step 3:</strong> Click on any available slot</li>
            <li><strong>Step 4:</strong> Verify you see the booking summary (Step 1 of 3)</li>
            <li><strong>Step 5:</strong> Click "Next" to go to user details form</li>
            <li><strong>Step 6:</strong> Fill in Name, Phone, and Email (note: password is auto-generated)</li>
            <li><strong>Step 7:</strong> Try clicking "Review & Pay" without filling fields (should show validation errors)</li>
            <li><strong>Step 8:</strong> Fill all required fields and click "Review & Pay"</li>
            <li><strong>Step 9:</strong> Verify Step 3 shows complete booking review</li>
            <li><strong>Step 10:</strong> Test "Previous" button to go back</li>
            <li><strong>Step 11:</strong> Test "Back to Slots" from Step 1</li>
        </ol>
    </div>

    <div class="success-criteria">
        <h3>✅ Success Criteria</h3>
        <ul>
            <li>✓ No page reloads - everything happens in modals</li>
            <li>✓ Progress indicator accurately reflects current step</li>
            <li>✓ Form only asks for Name, Phone, Email (simplified)</li>
            <li>✓ Password is auto-generated (no manual password field shown)</li>
            <li>✓ Smooth transitions between steps with fade animations</li>
            <li>✓ Validation prevents moving forward with incomplete data</li>
            <li>✓ Navigation buttons appear/disappear correctly</li>
            <li>✓ Can navigate backward through steps</li>
            <li>✓ Modal can return to slot selection</li>
        </ul>
    </div>

    <div class="calendar-container">
        <h2>Calendar</h2>
        <?php echo do_shortcode('[waza_booking_calendar]'); ?>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
