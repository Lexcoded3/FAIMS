-- ============================================
-- SAFE FORUM TOPICS MIGRATION SCRIPT
-- ============================================
-- This script:
-- 1. Backs up old topics to archive tables
-- 2. Deletes related data (replies, likes, images, tags)
-- 3. Inserts new meaningful topics
-- 4. Preserves data if needed

-- ============================================
-- STEP 1: BACKUP OLD DATA (Just in case)
-- ============================================

-- Backup old topics
CREATE TABLE forum_topics_backup AS 
SELECT * FROM forum_topics 
WHERE id > 0;

-- Backup old replies
CREATE TABLE forum_replies_backup AS 
SELECT * FROM forum_replies 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Backup old likes
CREATE TABLE forum_topic_likes_backup AS 
SELECT * FROM forum_topic_likes 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Backup old images
CREATE TABLE forum_topic_images_backup AS 
SELECT * FROM forum_topic_images 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Backup old tags
CREATE TABLE forum_topic_tags_backup AS 
SELECT * FROM forum_topic_tags 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Verify backups created
SELECT 
  'forum_topics_backup' as table_name, COUNT(*) as row_count FROM forum_topics_backup
UNION ALL
SELECT 'forum_replies_backup', COUNT(*) FROM forum_replies_backup
UNION ALL
SELECT 'forum_topic_likes_backup', COUNT(*) FROM forum_topic_likes_backup
UNION ALL
SELECT 'forum_topic_images_backup', COUNT(*) FROM forum_topic_images_backup
UNION ALL
SELECT 'forum_topic_tags_backup', COUNT(*) FROM forum_topic_tags_backup;

-- ============================================
-- STEP 2: DELETE DEPENDENT DATA (in order)
-- ============================================

-- Delete replies first (references topic_id)
DELETE FROM forum_replies 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Delete likes (references topic_id)
DELETE FROM forum_topic_likes 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Delete images (references topic_id)
DELETE FROM forum_topic_images 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Delete tags (references topic_id)
DELETE FROM forum_topic_tags 
WHERE topic_id IN (SELECT id FROM forum_topics);

-- Delete topics themselves
DELETE FROM forum_topics WHERE id > 0;

-- Verify all deleted
SELECT 
  'forum_topics' as table_name, COUNT(*) as remaining_rows FROM forum_topics
UNION ALL
SELECT 'forum_replies', COUNT(*) FROM forum_replies
UNION ALL
SELECT 'forum_topic_likes', COUNT(*) FROM forum_topic_likes
UNION ALL
SELECT 'forum_topic_images', COUNT(*) FROM forum_topic_images
UNION ALL
SELECT 'forum_topic_tags', COUNT(*) FROM forum_topic_tags;

-- ============================================
-- STEP 3: INSERT NEW MEANINGFUL TOPICS
-- ============================================

-- Reset auto-increment to 1
ALTER TABLE forum_topics AUTO_INCREMENT = 1;

-- ==========================================
-- CATEGORY 1: Crop Production
-- ==========================================
INSERT INTO forum_topics (category_id, user_id, title, content, status, created_at, views) VALUES
(1, 1, 'Best timing for maize planting in Mukono district', 
'I am planning to plant maize this season. What is the recommended planting window for Mukono district? Should I wait for more rain or start now?', 
'active', '2026-02-10 10:30:00', 45),

(1, 1, 'Dealing with army worms on maize - organic solution',
'My maize crop is being attacked by army worms. Has anyone tried organic pesticides? What products are available in Uganda that work well?',
'active', '2026-02-12 14:15:00', 82),

(1, 1, 'Soil preparation for improved potato yields',
'I want to increase my potato yield this season. What is the best way to prepare the soil? Should I add compost or manure?',
'active', '2026-02-13 09:00:00', 56),

(1, 1, 'Intercropping maize with beans - experiences?',
'I have heard that planting beans between maize rows can improve soil and give extra income. Anyone done this successfully? What spacing works best?',
'active', '2026-02-14 11:45:00', 38),

(1, 1, 'Fighting fall armyworm - new resistance issue',
'I noticed some armyworms surviving after spraying. Are we seeing resistance developing? What rotation of pesticides do you recommend?',
'active', '2026-02-15 16:20:00', 67),

-- ==========================================
-- CATEGORY 2: Livestock Management
-- ==========================================
(2, 1, 'Best dairy cattle breeds for small-scale farmers in Uganda',
'I want to start a small dairy farm. Which breeds are good for our climate and easy to manage? Jersey, Holstein, or local breeds?',
'active', '2026-02-11 08:30:00', 91),

(2, 1, 'Affordable feed options for chickens during dry season',
'Feed prices are very high during the dry season. What cheap alternatives can I use to feed my 200 chickens without losing production?',
'active', '2026-02-13 13:00:00', 54),

(2, 1, 'Treating Newcastle disease in village chickens',
'Several of my chickens died this week with twisted necks and green droppings. Is this Newcastle? How do I treat survivors and prevent spread?',
'active', '2026-02-14 07:15:00', 78),

(2, 1, 'Goat breeding - improving meat quality',
'I have Boer goats but sometimes get poor quality meat. What feeding program gives best meat quality? Any mineral supplements needed?',
'active', '2026-02-16 10:00:00', 42),

(2, 1, 'Vaccinating cattle - schedule and costs',
'What vaccines do cows need and when? Is it worth the cost? Where can I get affordable vaccines in Kampala region?',
'active', '2026-02-17 15:30:00', 65),

-- ==========================================
-- CATEGORY 3: Market & Pricing
-- ==========================================
(3, 1, 'Current tomato prices and market demand - February 2026',
'Planning to plant tomatoes next week. What are current wholesale prices? Is there good demand or is the market flooded?',
'active', '2026-02-10 12:00:00', 103),

(3, 1, 'Best time to sell maize - waiting vs immediate sale',
'I harvested 50 bags of maize. Should I sell now or wait for prices to go up? What usually happens to maize prices after harvest?',
'active', '2026-02-12 09:45:00', 87),

(3, 1, 'Irish potatoes - which market pays best?',
'I have 2 tons of potatoes. Should I sell to Nakasero market, wholesalers, or export companies? What are pros and cons of each?',
'active', '2026-02-14 06:30:00', 71),

(3, 1, 'Starting vegetable supply to schools and hospitals',
'I want to supply cabbages and onions to local schools. How do I approach them? What prices do they negotiate to? Anyone doing this?',
'active', '2026-02-15 14:20:00', 48),

(3, 1, 'Coffee prices trending down - should farmers worry?',
'Coffee prices have dropped 15% this month. As a coffee farmer, should I reduce production or wait for recovery?',
'active', '2026-02-16 11:00:00', 62),

-- ==========================================
-- CATEGORY 4: Agri-Business & Finance
-- ==========================================
(4, 1, 'SACCO loans for farming - experiences and tips',
'I want to borrow 5 million from my SACCO to expand farming. Has anyone done this? What is the process and interest rate?',
'active', '2026-02-11 16:45:00', 94),

(4, 1, 'Farm record keeping - what system works best?',
'I am losing track of my expenses and income. What is a simple system for keeping farm records? Excel or mobile app?',
'active', '2026-02-13 10:15:00', 58),

(4, 1, 'Bank loans for farmers - better than SACCO?',
'Commercial banks are now offering farm loans. Are they better than SACCO? What are the interest rates and conditions?',
'active', '2026-02-14 13:30:00', 76),

(4, 1, 'Calculating production costs correctly - where do I start?',
'I don''t know if I''m making profit or losing money. How do I calculate real production costs including my labor?',
'active', '2026-02-15 09:00:00', 51),

(4, 1, 'Crop insurance in Uganda - is it worth it?',
'I heard about insurance for crops. Is it available in Uganda? What does it cover and how much does it cost?',
'active', '2026-02-16 14:45:00', 44),

-- ==========================================
-- CATEGORY 5: Technology & Tools
-- ==========================================
(5, 1, 'Best weather apps for farmers in Uganda',
'I need to predict rain for planting decisions. Which weather apps are most accurate for Uganda? Are they free?',
'active', '2026-02-10 07:00:00', 112),

(5, 1, 'Drip irrigation systems - which brand is reliable?',
'I want to install drip irrigation on 0.5 acres. What systems are available in Uganda? How much does a complete setup cost?',
'active', '2026-02-12 15:30:00', 89),

(5, 1, 'Solar-powered water pump for farm irrigation',
'Solar pumps seem useful but are they reliable? How many hours of sun do they need? Any brands recommended for Uganda?',
'active', '2026-02-13 11:20:00', 73),

(5, 1, 'Soil testing services in Uganda - where and cost',
'Should I test my soil before planting? Where can I get soil testing done in Kampala? How much does it cost and how long for results?',
'active', '2026-02-14 08:45:00', 55),

(5, 1, 'Using mobile money to buy farm inputs - M-PESA vouchers',
'I saw farm input shops accepting M-PESA. Is it safe? What inputs are available? Are prices the same as cash payment?',
'active', '2026-02-17 12:00:00', 37);

-- ============================================
-- STEP 4: VERIFY MIGRATION
-- ============================================

-- Check new topics count by category
SELECT 
  fc.name as category,
  COUNT(ft.id) as topic_count,
  MAX(ft.views) as max_views,
  MIN(ft.views) as min_views,
  AVG(ft.views) as avg_views
FROM forum_topics ft
JOIN forum_categories fc ON ft.category_id = fc.id
GROUP BY ft.category_id, fc.name
ORDER BY ft.category_id;

-- Check total
SELECT 
  'Total Topics' as info,
  COUNT(*) as count
FROM forum_topics;

-- Show sample of new topics
SELECT id, category_id, title, views, created_at 
FROM forum_topics 
ORDER BY id 
LIMIT 10;

-- ============================================
-- OPTIONAL: IF YOU WANT TO RESTORE FROM BACKUP
-- ============================================
-- RESTORE CODE (only run if needed):
/*

-- Delete current data
DELETE FROM forum_topics;
DELETE FROM forum_replies;
DELETE FROM forum_topic_likes;
DELETE FROM forum_topic_images;
DELETE FROM forum_topic_tags;

-- Restore from backups
INSERT INTO forum_topics SELECT * FROM forum_topics_backup;
INSERT INTO forum_replies SELECT * FROM forum_replies_backup;
INSERT INTO forum_topic_likes SELECT * FROM forum_topic_likes_backup;
INSERT INTO forum_topic_images SELECT * FROM forum_topic_images_backup;
INSERT INTO forum_topic_tags SELECT * FROM forum_topic_tags_backup;

*/

-- ============================================
-- END OF MIGRATION SCRIPT
-- ============================================