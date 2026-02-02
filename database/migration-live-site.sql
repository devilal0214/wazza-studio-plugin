-- =====================================================
-- Waza Booking Plugin - Live Site Migration
-- Run this SQL on http://wazastudio.com/ database
-- =====================================================

-- Check and add image_url column to waza_slots table
ALTER TABLE `wp_waza_slots` 
ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(500) NULL DEFAULT NULL AFTER `price`;

-- Check and add original_price column to waza_slots table
ALTER TABLE `wp_waza_slots` 
ADD COLUMN IF NOT EXISTS `original_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `price`;

-- Check and add sale_price column to waza_slots table
ALTER TABLE `wp_waza_slots` 
ADD COLUMN IF NOT EXISTS `sale_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `original_price`;

-- =====================================================
-- Optional: Create promo codes table (if needed in future)
-- =====================================================
CREATE TABLE IF NOT EXISTS `wp_waza_promo_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`),
  KEY `valid_dates` (`valid_from`,`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Verification Queries (run after migration to check)
-- =====================================================
-- SHOW COLUMNS FROM `wp_waza_slots`;
-- SELECT COUNT(*) FROM `wp_waza_slots` WHERE image_url IS NOT NULL;
-- SELECT COUNT(*) FROM `wp_waza_slots` WHERE sale_price IS NOT NULL;

-- =====================================================
-- Notes:
-- 1. Replace 'wp_' with your actual WordPress table prefix if different
-- 2. The promo codes table is optional - only create if you need discount functionality
-- 3. Backup your database before running this migration
-- 4. All new columns allow NULL values to maintain existing data
-- =====================================================
