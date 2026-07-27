-- ==========================================
-- 1. USERS
-- Password hash placeholder: '$2y$10$e8T/...' (Bcrypt example)
-- Assumes location_ids correspond to the order in locations.sql
-- password: mySecretPassword123
-- ==========================================
INSERT INTO users (user_id, phone_number, email, first_name, last_name, username, password_hash, role, assigned_area, home_location_id) VALUES
(1, '+639301239988', 'mgatmaitan@lissentialmanila.ph', 'Max', 'Gatmaitan', 'SysAdmin_Max', '$2y$10$K7Z2/eUvV8b4fW9M9X3rOe0W6u1gS0fH3k2mR5zE9q7w8e9r0t1y2', 'Admin', 'NCR All Zones', 46), -- Diliman, QC
(2, '+639201112233', 'rmendoza@marikina.gov.ph', 'Rosario', 'Mendoza', 'LGU_Marikina_09', '$2y$10$O0N8u5mX3zV1w7p4q9e2rO0k4s8t5v6w7x8y9z0a1b2c3d4e5f6g7', 'Official', 'Marikina', 115), -- Marikina Heights
(3, '+639171234567', 'greenarcher01@gmail.com', 'Green', 'Archer', 'GreenArcher_01', '$2y$10$U4b9x2z1v8w7m6p5q3e4rO2k8s5t9v0w1x2y3z4a5b6c7d8e9f0g1', 'Student', NULL, 8), -- Taft Avenue, Manila
(4, '+639189876543', 'mdelossantos@gmail.com', 'Michael', 'delos Santos', 'MichaelJackson', '$2y$10$F3m7u2n1o8p9q0v5w4e3rO4k6s1t2v3w4x5y6z7a8b9c0d1e2f3g4', 'Student', NULL, 131), -- Alabang, Muntinlupa
(5, '+639192223333', 'commuter.manila@gmail.com', 'Maria', 'Santos', 'ManilaCommuter', '$2y$10$R9k4u1n8o7p2q5v0w3e1rO6k3s9t5v4w2x1y0z9a8b7c6d5e4f3g2', 'Student', NULL, 80), -- Ortigas, Pasig
(6, '+639215556666', 'nightowl@yahoo.com', 'Kevin', 'Reyes', 'NightOwl_Driver', '$2y$10$A2m8u4n9o3p7q1v6w5e0rO8k5s2t1v0w9x8y7z6a5b4c3d2e1f0g9', 'Student', NULL, 47); -- Katipunan, QC

-- ==========================================
-- 2. SAVED LOCATIONS
-- ==========================================
INSERT INTO saved_locations (user_id, location_id) VALUES
(3, 80),  -- GreenArcher saved Ortigas
(4, 8),   -- MichaelJackson saved Taft Avenue
(5, 131), -- ManilaCommuter saved Alabang
(6, 47);  -- NightOwl_Driver saved Katipunan


-- ==========================================
-- 3. THREADS
-- Created by Official (user_id = 2) or Admin (user_id = 1)
-- ==========================================
INSERT INTO threads (thread_id, title, location_id, category_id, created_by, status, description, total_reports, verified_reports, unverified_reports, created_at) VALUES
(1, 'Taft Avenue Localized Flooding', 8, 3, 2, 'Resolved', 'Torrential rain caused gutter-deep flooding outside DLSU.', 1, 1, 0, '2026-07-21 14:14:00'),
(2, 'SLEX Southbound Traffic Congestion', 131, 2, 2, 'Active', 'Stalled vehicle causing major back-ups near Alabang exit.', 1, 1, 0, '2026-07-08 09:30:00'),
(3, 'Ortigas Ave Eastbound Multi-Vehicle Collision', 80, 1, 2, 'Active', 'Three-car collision near Meralco Ave intersection.', 1, 1, 0, '2026-07-20 07:45:00'),
(4, 'Katipunan Ave Emergency Road Repair', 47, 4, 2, 'Active', 'DPWH re-blocking overnight near Ateneo Gate 3.', 1, 0, 1, '2026-07-20 22:15:00');


-- ==========================================
-- 4. REPORTS
-- Exactly matching the HTML posts in user-home.php
-- ==========================================
INSERT INTO reports (report_id, user_id, thread_id, location_id, category_id, title, description, upvote_count, comment_count, status, verification_remarks, verified_by, verified_at, created_at) VALUES
(1, 3, 1, 8, 3, 'Gutter-deep flooding outside DLSU after sudden downpour', 'Heavy torrential rain over the last 30 minutes has caused localized flooding along Taft Ave, specifically northbound in front of De La Salle University. Light vehicles are slowing down significantly to navigate the water. Gutter-deep, passable but moving very slowly.', 52, 2, 'Resolved', 'Water has receded, area clear.', 2, '2026-07-21 15:30:00', '2026-07-21 14:14:00'),

(2, 4, 2, 131, 2, 'Traffic congested Near Alabang SLEX Southbound', 'Heavy traffic buildup on SLEX Southbound near the Alabang exit. Appears to be caused by a stalled vehicle blocking the rightmost lane. Traffic is backing up approximately 3km. Avoid this route and use alternative roads. MMDA on the scene.', 34, 2, 'Verified', 'Confirmed by MMDA patrol officer on site.', 2, '2026-07-08 09:40:00', '2026-07-08 09:30:00'),

(3, 5, 3, 80, 1, 'Multi-vehicle collision near Meralco Avenue intersection', 'A three-car fender bender has blocked two center lanes eastbound on Ortigas Ave right before Meralco Ave. Major bottleneck forming all the way back to EDSA-Ortigas flyover. Enforcers are currently redirecting flow, but expect at least a 20-30 minute delay.', 87, 3, 'Verified', 'Verified via CCTC footage.', 2, '2026-07-20 08:00:00', '2026-07-20 07:45:00'),

(4, 6, 4, 47, 4, 'Emergency re-blocking near Ateneo Gate 3 Northbound', 'DPWH has unexpectedly blocked off the leftmost lane for urgent asphalt repairs just past the flyover. Heavy machinery is occupying the lane. Tailback is already reaching Aurora Boulevard underpass. Expect slow-moving traffic until early morning.', 18, 2, 'Pending', NULL, NULL, NULL, '2026-07-20 22:15:00');


-- ==========================================
-- 5. MEDIA ATTACHMENTS
-- Exact media references used in the HTML carousel container
-- ==========================================
INSERT INTO media_attachments (media_id, report_id, file_url, file_type) VALUES
-- Report 1 Attachments
(1, 1, '../../assets/report_media/media1-1.jfif', 'photo'),

-- Report 2 Attachments (Carousel)
(2, 2, '../../assets/report_media/media2-1.jpg', 'photo'),
(3, 2, '../../assets/report_media/media2-2.jfif', 'photo'),
(4, 2, '../../assets/report_media/media2-3.jpg', 'photo'),
(5, 2, '../../assets/report_media/media2-4.mp4', 'video'),

-- Report 3 Attachments
(6, 3, '../../assets/report_media/media3-1.jfif', 'photo'),

-- Report 4 Attachments
(7, 4, '../../assets/report_media/media4-1.png', 'photo');


-- ==========================================
-- 6. COMMENTS
-- ==========================================
INSERT INTO comments (comment_id, user_id, report_id, comment_text, created_at) VALUES
-- Comments on Report 1 (Taft Flooding)
(1, 4, 1, 'Water is receding quickly now that the rain stopped, but traffic is still tight.', '2026-07-21 14:40:00'),
(2, 5, 1, 'LRT-1 is your best friend today. Don''t even bother booking a ride-hailing app right now.', '2026-07-21 14:50:00'),

-- Comments on Report 2 (Alabang Traffic)
(3, 5, 2, 'Can confirm, stuck here right now. Moving at a literal crawl. Take the Skyway if you can.', '2026-07-08 09:35:00'),
(4, 6, 2, 'Tow truck just arrived as of 9:45 AM, hopefully this clears up before the lunch rush.', '2026-07-08 09:46:00'),

-- Comments on Report 3 (Ortigas Collision)
(5, 3, 3, 'Just passed by on a motorcycle, it’s a mess. Side streets are starting to clog up too.', '2026-07-20 07:50:00'),
(6, 6, 3, 'Is the yellow lane clear for buses? Or is the whole stretch dead?', '2026-07-20 07:55:00'),
(7, 4, 3, 'Better to take Shaw Blvd or C5 if you are heading towards Antipolo/Cainta.', '2026-07-20 08:02:00'),

-- Comments on Report 4 (Katipunan Re-blocking)
(8, 3, 4, 'Why do they always do this during weekdays? Even at night, Katipunan is packed.', '2026-07-20 22:30:00'),
(9, 5, 4, 'Thanks for the heads up, avoiding this for my graveyard shift commute.', '2026-07-20 22:45:00');


-- ==========================================
-- 7. UPVOTES
-- ==========================================
INSERT INTO upvotes (user_id, report_id) VALUES
(4, 1), (5, 1), (6, 1), -- Upvotes for Report 1
(3, 2), (5, 2),          -- Upvotes for Report 2
(3, 3), (4, 3), (6, 3), -- Upvotes for Report 3
(3, 4);                 -- Upvotes for Report 4


-- ==========================================
-- 8. ADVISORIES
-- ==========================================
INSERT INTO advisories (posted_by, location_id, title, content, is_active, created_at) VALUES
(2, 8, 'Taft Avenue Drainage Clearing Notice', 'DPWH personnel will conduct localized drainage cleaning tonight from 10:00 PM to 4:00 AM.', TRUE, '2026-07-21 16:00:00'),
(1, NULL, 'Metropolitan Emergency Flood Alert', 'All municipal units placed on high alert due to torrential monsoonal rains.', TRUE, '2026-07-21 12:00:00');


-- ==========================================
-- 9. AUDIT LOGS
-- ==========================================
INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES
(2, 'Resolve Report', 'report', 1, 'Marked Taft Avenue flooding report as resolved.', '192.168.1.50', '2026-07-21 15:30:00'),
(2, 'Verify Report', 'report', 2, 'Verified SLEX traffic report after officer site check.', '192.168.1.50', '2026-07-08 09:40:00'),
(2, 'Verify Report', 'report', 3, 'Verified Ortigas multi-vehicle accident using traffic camera feed.', '192.168.1.50', '2026-07-20 08:00:00');