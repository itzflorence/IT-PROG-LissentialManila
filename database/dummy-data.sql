/*
==========================================
RESET SEEDED TABLES
Makes this file safe to re-run without PRIMARY KEY conflicts.
==========================================
*/
DELETE FROM notifications;
DELETE FROM audit_logs;
DELETE FROM advisories;
DELETE FROM upvotes;
DELETE FROM comments;
DELETE FROM media_attachments;
DELETE FROM reports;
DELETE FROM threads;
DELETE FROM saved_locations;
DELETE FROM users;

ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE audit_logs AUTO_INCREMENT = 1;
ALTER TABLE advisories AUTO_INCREMENT = 1;
ALTER TABLE upvotes AUTO_INCREMENT = 1;
ALTER TABLE comments AUTO_INCREMENT = 1;
ALTER TABLE media_attachments AUTO_INCREMENT = 1;
ALTER TABLE reports AUTO_INCREMENT = 1;
ALTER TABLE threads AUTO_INCREMENT = 1;
ALTER TABLE saved_locations AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

/*
==========================================
1. USERS
Password hashes are valid bcrypt hashes for all seeded users
Assumes location_ids correspond to the order in locations.sql
assigned_location_id must reference locations.location_id (NULL for non-official accounts)
password: pass123
==========================================
*/
INSERT INTO users (user_id, phone_number, email, first_name, last_name, username, password_hash, role, assigned_location_id, home_location_id) VALUES
(1, '+639301239988', 'mgatmaitan@lissentialmanila.ph', 'Max', 'Gatmaitan', 'SysAdmin_Max', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Admin', NULL, 46),
(2, '+639201112233', 'rmendoza@marikina.gov.ph', 'Rosario', 'Mendoza', 'LGU_Marikina_09', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Official', 115, 115),
(3, '+639171234567', 'greenarcher01@gmail.com', 'Green', 'Archer', 'GreenArcher_01', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 8),
(4, '+639189876543', 'mdelossantos@gmail.com', 'Michael', 'delos Santos', 'MichaelJackson', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 131),
(5, '+639192223333', 'commuter.manila@gmail.com', 'Maria', 'Santos', 'ManilaCommuter', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 80),
(6, '+639215556666', 'nightowl@yahoo.com', 'Kevin', 'Reyes', 'NightOwl_Driver', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 47);

/*
==========================================
2. SAVED LOCATIONS
==========================================
*/
INSERT INTO saved_locations (user_id, location_id) VALUES
(3, 80),
(4, 8),
(5, 131),
(6, 47);


/*
==========================================
3. THREADS
Created by Official (user_id = 2) or Admin (user_id = 1)
==========================================
*/
INSERT INTO threads (thread_id, title, location_id, category_id, created_by, status, description, total_reports, verified_reports, unverified_reports, created_at) VALUES
(1, 'Taft Avenue Localized Flooding', 8, 3, 2, 'Resolved', 'Torrential rain caused gutter-deep flooding outside DLSU.', 1, 1, 0, '2026-07-21 14:14:00'),
(2, 'SLEX Southbound Traffic Congestion', 131, 2, 2, 'Active', 'Stalled vehicle causing major back-ups near Alabang exit.', 1, 1, 0, '2026-07-08 09:30:00'),
(3, 'Ortigas Ave Eastbound Multi-Vehicle Collision', 80, 1, 2, 'Active', 'Three-car collision near Meralco Ave intersection.', 1, 1, 0, '2026-07-20 07:45:00'),
(4, 'Katipunan Ave Emergency Road Repair', 47, 4, 2, 'Active', 'DPWH re-blocking overnight near Ateneo Gate 3.', 1, 0, 1, '2026-07-20 22:15:00');


/*
==========================================
4. REPORTS
Exactly matching the HTML posts in user-home.php
==========================================
*/
INSERT INTO reports (report_id, user_id, thread_id, location_id, category_id, title, description, upvote_count, comment_count, status, verification_remarks, verified_by, verified_at, created_at) VALUES
(1, 3, 1, 8, 3, 'Gutter-deep flooding outside DLSU after sudden downpour', 'Heavy torrential rain over the last 30 minutes has caused localized flooding along Taft Ave, specifically northbound in front of De La Salle University. Light vehicles are slowing down significantly to navigate the water. Gutter-deep, passable but moving very slowly.', 52, 2, 'Resolved', 'Water has receded, area clear.', 2, '2026-07-21 15:30:00', '2026-07-21 14:14:00'),

(2, 4, 2, 131, 2, 'Traffic congested Near Alabang SLEX Southbound', 'Heavy traffic buildup on SLEX Southbound near the Alabang exit. Appears to be caused by a stalled vehicle blocking the rightmost lane. Traffic is backing up approximately 3km. Avoid this route and use alternative roads. MMDA on the scene.', 34, 2, 'Verified', 'Confirmed by MMDA patrol officer on site.', 2, '2026-07-08 09:40:00', '2026-07-08 09:30:00'),

(3, 5, 3, 80, 1, 'Multi-vehicle collision near Meralco Avenue intersection', 'A three-car fender bender has blocked two center lanes eastbound on Ortigas Ave right before Meralco Ave. Major bottleneck forming all the way back to EDSA-Ortigas flyover. Enforcers are currently redirecting flow, but expect at least a 20-30 minute delay.', 87, 3, 'Verified', 'Verified via CCTC footage.', 2, '2026-07-20 08:00:00', '2026-07-20 07:45:00'),

(4, 6, 4, 47, 4, 'Emergency re-blocking near Ateneo Gate 3 Northbound', 'DPWH has unexpectedly blocked off the leftmost lane for urgent asphalt repairs just past the flyover. Heavy machinery is occupying the lane. Tailback is already reaching Aurora Boulevard underpass. Expect slow-moving traffic until early morning.', 18, 2, 'Pending', NULL, NULL, NULL, '2026-07-20 22:15:00');


/*
==========================================
5. MEDIA ATTACHMENTS
Exact media references used in the HTML carousel container
==========================================
*/
INSERT INTO media_attachments (media_id, report_id, file_url, file_type) VALUES
(1, 1, '../../assets/report_media/media1-1.jfif', 'photo'),

(2, 2, '../../assets/report_media/media2-1.jpg', 'photo'),
(3, 2, '../../assets/report_media/media2-2.jfif', 'photo'),
(4, 2, '../../assets/report_media/media2-3.jpg', 'photo'),
(5, 2, '../../assets/report_media/media2-4.mp4', 'video'),

(6, 3, '../../assets/report_media/media3-1.jfif', 'photo'),

(7, 4, '../../assets/report_media/media4-1.png', 'photo');


/*
==========================================
6. COMMENTS
==========================================
*/
INSERT INTO comments (comment_id, user_id, report_id, comment_text, created_at) VALUES
(1, 4, 1, 'Water is receding quickly now that the rain stopped, but traffic is still tight.', '2026-07-21 14:40:00'),
(2, 5, 1, 'LRT-1 is your best friend today. Don''t even bother booking a ride-hailing app right now.', '2026-07-21 14:50:00'),

(3, 5, 2, 'Can confirm, stuck here right now. Moving at a literal crawl. Take the Skyway if you can.', '2026-07-08 09:35:00'),
(4, 6, 2, 'Tow truck just arrived as of 9:45 AM, hopefully this clears up before the lunch rush.', '2026-07-08 09:46:00'),

(5, 3, 3, 'Just passed by on a motorcycle, it’s a mess. Side streets are starting to clog up too.', '2026-07-20 07:50:00'),
(6, 6, 3, 'Is the yellow lane clear for buses? Or is the whole stretch dead?', '2026-07-20 07:55:00'),
(7, 4, 3, 'Better to take Shaw Blvd or C5 if you are heading towards Antipolo/Cainta.', '2026-07-20 08:02:00'),

(8, 3, 4, 'Why do they always do this during weekdays? Even at night, Katipunan is packed.', '2026-07-20 22:30:00'),
(9, 5, 4, 'Thanks for the heads up, avoiding this for my graveyard shift commute.', '2026-07-20 22:45:00');


/*
==========================================
7. UPVOTES
==========================================
*/
INSERT INTO upvotes (user_id, report_id) VALUES
(4, 1), (5, 1), (6, 1),
(3, 2), (5, 2),
(3, 3), (4, 3), (6, 3),
(3, 4);


/*
==========================================
8. ADVISORIES
==========================================
*/
INSERT INTO advisories (posted_by, location_id, title, content, is_active, created_at) VALUES
(2, 8, 'Taft Avenue Drainage Clearing Notice', 'DPWH personnel will conduct localized drainage cleaning tonight from 10:00 PM to 4:00 AM.', TRUE, '2026-07-21 16:00:00'),
(1, NULL, 'Metropolitan Emergency Flood Alert', 'All municipal units placed on high alert due to torrential monsoonal rains.', TRUE, '2026-07-21 12:00:00');


/*
==========================================
9. AUDIT LOGS
==========================================
*/
INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES
(2, 'Resolve Report', 'report', 1, 'Marked Taft Avenue flooding report as resolved.', '192.168.1.50', '2026-07-21 15:30:00'),
(2, 'Verify Report', 'report', 2, 'Verified SLEX traffic report after officer site check.', '192.168.1.50', '2026-07-08 09:40:00'),
(2, 'Verify Report', 'report', 3, 'Verified Ortigas multi-vehicle accident using traffic camera feed.', '192.168.1.50', '2026-07-20 08:00:00');