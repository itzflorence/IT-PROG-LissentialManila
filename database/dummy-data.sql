/*
==========================================
EXPANDED DUMMY DATA — Lissential Manila

This file is self-contained and safe to re-run (resets first),
just like the original seed.sql.
==========================================
*/

DELETE FROM notifications;
DELETE FROM audit_logs;
DELETE FROM advisories;
DELETE FROM upvotes;
DELETE FROM resolved_marks;
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
ALTER TABLE resolved_marks AUTO_INCREMENT = 1;
ALTER TABLE comments AUTO_INCREMENT = 1;
ALTER TABLE media_attachments AUTO_INCREMENT = 1;
ALTER TABLE reports AUTO_INCREMENT = 1;
ALTER TABLE threads AUTO_INCREMENT = 1;
ALTER TABLE saved_locations AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

/*
1. USERS (12 total)
password for all seeded accounts: pass123
*/
INSERT INTO users (user_id, phone_number, email, first_name, last_name, username, password_hash, role, assigned_location_id, home_location_id) VALUES
(1, '+639301239988', 'mgatmaitan@lissentialmanila.ph', 'Max', 'Gatmaitan', 'SysAdmin_Max', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Admin', NULL, 46),
(2, '+639201112233', 'rmendoza@marikina.gov.ph', 'Rosario', 'Mendoza', 'LGU_Marikina_09', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Official', 115, 115),
(3, '+639171234567', 'greenarcher01@gmail.com', 'Green', 'Archer', 'GreenArcher_01', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 8),
(4, '+639189876543', 'mdelossantos@gmail.com', 'Michael', 'delos Santos', 'MichaelJackson', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 131),
(5, '+639192223333', 'commuter.manila@gmail.com', 'Maria', 'Santos', 'ManilaCommuter', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 80),
(6, '+639215556666', 'nightowl@yahoo.com', 'Kevin', 'Reyes', 'NightOwl_Driver', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 47),
(7, '+639173334455', 'bcruz.dlsutraffic@gmail.com', 'Bernardo', 'Cruz', 'Traffic_Enforcer_08', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Official', 8, 8),
(8, '+639184445566', 'lfernandez@pasig.gov.ph', 'Liza', 'Fernandez', 'LGU_Pasig_Ortigas', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Official', 80, 80),
(9, '+639225556677', 'carlo.villa@gmail.com', 'Carlo', 'Villanueva', 'CarloV_Manila', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 46),
(10, '+639296667788', 'bianca.ramos@gmail.com', 'Bianca', 'Ramos', 'BiancaR_Marikina', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 115),
(11, '+639177778899', 'joshuatan@gmail.com', 'Joshua', 'Tan', 'JoshTan_Muntinlupa', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 131),
(12, '+639988889900', 'erica.ocampo@gmail.com', 'Erica', 'Ocampo', 'EricaO_QC', '$2y$10$tSGSizISOsH1NqWhYZT24OBF0CUCMmCDAPRinIg8ajE94YvmGMuzW', 'Student', NULL, 47);

/*
2. SAVED LOCATIONS (0-3 per user)
*/
INSERT INTO saved_locations (user_id, location_id) VALUES
(1, 8),
(1, 80),
(2, 47),
(3, 80),
(3, 46),
(4, 8),
(4, 115),
(5, 131),
(5, 47),
(5, 46),
(6, 47),
(6, 80),
(8, 131),
(9, 8),
(9, 47),
(9, 80),
(10, 8),
(10, 131),
(11, 46);

/*
3. THREADS (8 total)
updated_at is set to each thread's latest linked-report activity so "Updated ..." isn't just today's seed run date.
*/
INSERT INTO threads (thread_id, title, location_id, category_id, created_by, status, description, created_at, updated_at) VALUES
(1, 'Taft Avenue Localized Flooding', 8, 3, 2, 'Resolved', 'Torrential rain caused gutter-deep flooding outside DLSU.', '2026-07-21 14:14:00', '2026-07-23 04:07:00'),
(2, 'SLEX Southbound Traffic Congestion', 131, 2, 2, 'Active', 'Stalled vehicle causing major back-ups near Alabang exit.', '2026-07-08 09:30:00', '2026-07-11 17:30:00'),
(3, 'Ortigas Ave Eastbound Multi-Vehicle Collision', 80, 1, 2, 'Active', 'Three-car collision near Meralco Ave intersection.', '2026-07-20 07:45:00', '2026-07-23 08:07:00'),
(4, 'Katipunan Ave Emergency Road Repair', 47, 4, 2, 'Active', 'DPWH re-blocking overnight near Ateneo Gate 3.', '2026-07-20 22:15:00', '2026-07-23 08:11:00'),
(5, 'Marikina Riverbanks Road Construction Delays', 115, 5, 2, 'Active', 'Ongoing riverbank reinforcement project narrowing the road to a single lane.', '2026-07-22 08:00:00', '2026-07-26 03:31:00'),
(6, 'Quiapo Stalled Delivery Truck Blocking Lane', 46, 6, 1, 'Active', 'A delivery truck broke down on the outer lane causing a single-lane bottleneck.', '2026-07-23 16:20:00', '2026-07-25 18:27:00'),
(7, 'LRT-1 Service Disruption Near Taft Station', 8, 8, 7, 'Resolved', 'Signal malfunction temporarily halted LRT-1 operations between Taft and Vito Cruz stations.', '2026-07-24 06:45:00', '2026-07-27 19:07:00'),
(8, 'Broken Traffic Light at Ortigas-Meralco Junction', 80, 7, 8, 'Active', 'Traffic signal malfunctioning during rush hour, causing congestion at the intersection.', '2026-07-25 17:10:00', '2026-07-28 12:11:00');

/*
4. REPORTS
*/
INSERT INTO reports (report_id, user_id, thread_id, location_id, category_id, title, description, upvote_count, comment_count, resolved_count, status, verification_remarks, verified_by, verified_at, created_at) VALUES
(5, 4, 1, 8, 3, 'Ankle-deep flooding reported at Taft Avenue, Manila (near DLSU)', 'Continuous rain has caused ankle-deep flooding at Taft Avenue, Manila (near DLSU). Small vehicles are advised to take an alternate route.', 0, 4, 1, 'Pending', NULL, NULL, NULL, '2026-07-23 04:07:00'),
(6, 6, 1, 8, 3, 'Flash flood advisory area at Taft Avenue, Manila (near DLSU)', 'Heavy downpour in the last hour has led to flash flooding around Taft Avenue, Manila (near DLSU). Motorcycles are having a hard time passing through.', 5, 2, 0, 'Verified', 'Confirmed by field officer.', 7, '2026-07-22 22:05:00', '2026-07-22 20:39:00'),
(7, 10, 1, 8, 3, 'Drainage overflow near Taft Avenue, Manila (near DLSU)', 'Clogged drainage near Taft Avenue, Manila (near DLSU) has caused water to pool on the road, submerging part of the sidewalk as well.', 1, 3, 8, 'Resolved', 'Issue has been addressed and area is now clear.', 7, '2026-07-21 23:01:00', '2026-07-21 20:29:00'),
(8, 6, 2, 131, 2, 'Slow-moving traffic along Alabang, Muntinlupa (SLEX)', 'Vehicles are moving at a crawl along Alabang, Muntinlupa (SLEX). No visible incident yet, might just be volume from the holiday rush.', 4, 3, 8, 'Resolved', 'Issue has been addressed and area is now clear.', 8, '2026-07-09 00:39:00', '2026-07-08 20:43:00'),
(9, 12, 2, 131, 2, 'Backed-up intersection at Alabang, Muntinlupa (SLEX)', 'The intersection at Alabang, Muntinlupa (SLEX) is backed up in all directions, enforcers are on scene trying to manually direct flow.', 9, 4, 5, 'Rejected', 'Insufficient evidence / duplicate report.', 8, '2026-07-10 01:39:00', '2026-07-09 22:41:00'),
(10, 9, 2, 131, 2, 'Backed-up intersection at Alabang, Muntinlupa (SLEX)', 'The intersection at Alabang, Muntinlupa (SLEX) is backed up in all directions, enforcers are on scene trying to manually direct flow.', 1, 4, 1, 'Verified', 'Confirmed by field officer.', 8, '2026-07-11 18:41:00', '2026-07-11 17:30:00'),
(11, 12, 2, 131, 2, 'Slow-moving traffic along Alabang, Muntinlupa (SLEX)', 'Vehicles are moving at a crawl along Alabang, Muntinlupa (SLEX). No visible incident yet, might just be volume from the holiday rush.', 4, 4, 8, 'Resolved', 'Issue has been addressed and area is now clear.', 8, '2026-07-11 13:39:00', '2026-07-11 13:22:00'),
(12, 9, 3, 80, 1, 'Motorcycle down near Ortigas Avenue, Pasig', 'A rider lost control and skidded near Ortigas Avenue, Pasig. Bystanders helped move the motorcycle to the shoulder; traffic is moving slowly past the scene.', 9, 4, 4, 'Verified', 'Confirmed by field officer.', 8, '2026-07-21 05:53:00', '2026-07-21 04:57:00'),
(13, 5, 3, 80, 1, 'Rear-end collision along Ortigas Avenue, Pasig', 'Sudden braking caused a rear-end collision along Ortigas Avenue, Pasig during rush hour. Minor damage but drivers are blocking the outer lane while exchanging information.', 3, 4, 11, 'Resolved', 'Issue has been addressed and area is now clear.', 8, '2026-07-23 10:10:00', '2026-07-23 08:07:00'),
(14, 6, 3, 80, 1, 'Motorcycle down near Ortigas Avenue, Pasig', 'A rider lost control and skidded near Ortigas Avenue, Pasig. Bystanders helped move the motorcycle to the shoulder; traffic is moving slowly past the scene.', 8, 2, 5, 'Pending', NULL, NULL, NULL, '2026-07-20 09:03:00'),
(15, 6, 3, 80, 1, 'Delivery van collides with barrier near Ortigas Avenue, Pasig', 'A delivery van clipped a concrete barrier near Ortigas Avenue, Pasig, spilling some cargo onto the road. Cleanup crew has been called.', 6, 4, 0, 'Pending', NULL, NULL, NULL, '2026-07-22 15:39:00'),
(16, 12, 4, 47, 4, 'Debris blocking road at Katipunan Avenue, Quezon City', 'Construction debris has spilled onto the road at Katipunan Avenue, Quezon City, forcing vehicles to merge into a single lane.', 8, 2, 3, 'Pending', NULL, NULL, NULL, '2026-07-23 08:11:00'),
(17, 11, 4, 47, 4, 'Lane closed due to fallen tree near Katipunan Avenue, Quezon City', 'A fallen tree branch is blocking the outer lane near Katipunan Avenue, Quezon City after strong winds. Barangay crew has been notified.', 3, 3, 2, 'Verified', 'Confirmed by field officer.', 2, '2026-07-21 22:57:00', '2026-07-21 21:03:00'),
(18, 3, 4, 47, 4, 'Road partially blocked near Katipunan Avenue, Quezon City', 'Utility crews have partially blocked the road near Katipunan Avenue, Quezon City for emergency repairs, causing a slow single-file merge.', 1, 4, 3, 'Pending', NULL, NULL, NULL, '2026-07-21 06:23:00'),
(19, 6, 5, 115, 5, 'Ongoing construction narrows road at Marikina City', 'Construction work near Marikina City has narrowed the road to one lane. Expect delays especially during peak hours.', 4, 4, 5, 'Pending', NULL, NULL, NULL, '2026-07-25 20:53:00'),
(20, 12, 5, 115, 5, 'Ongoing construction narrows road at Marikina City', 'Construction work near Marikina City has narrowed the road to one lane. Expect delays especially during peak hours.', 2, 2, 2, 'Verified', 'Confirmed by field officer.', 2, '2026-07-22 20:44:00', '2026-07-22 18:20:00'),
(21, 9, 5, 115, 5, 'Ongoing construction narrows road at Marikina City', 'Construction work near Marikina City has narrowed the road to one lane. Expect delays especially during peak hours.', 4, 2, 8, 'Resolved', 'Issue has been addressed and area is now clear.', 2, '2026-07-26 05:02:00', '2026-07-26 03:31:00'),
(22, 9, 5, 115, 5, 'Utility trenching along Marikina City', 'Utility trenching work is ongoing along Marikina City, reducing the road to a single passable lane.', 5, 2, 0, 'Rejected', 'Insufficient evidence / duplicate report.', 2, '2026-07-23 22:28:00', '2026-07-23 18:37:00'),
(23, 11, 6, 46, 6, 'Stalled bus blocking lane at Quiapo, Manila', 'A public bus has stalled at Quiapo, Manila, blocking the rightmost lane. Passengers have already disembarked.', 2, 2, 5, 'Pending', NULL, NULL, NULL, '2026-07-24 07:56:00'),
(24, 3, 6, 46, 6, 'Broken-down truck at Quiapo, Manila', 'A cargo truck broke down at Quiapo, Manila and is waiting for a tow. Traffic enforcers are directing vehicles around it.', 2, 3, 6, 'Resolved', 'Issue has been addressed and area is now clear.', 1, '2026-07-25 22:05:00', '2026-07-25 18:27:00'),
(25, 5, 6, 46, 6, 'Vehicle out of gas along Quiapo, Manila', 'A car ran out of fuel along Quiapo, Manila, blocking part of the lane until it can be pushed to the shoulder.', 3, 3, 2, 'Pending', NULL, NULL, NULL, '2026-07-23 20:03:00'),
(26, 6, 6, 46, 6, 'Overheated car stuck near Quiapo, Manila', 'A private vehicle overheated and stopped near Quiapo, Manila. Hazard lights are on but it''s still causing a slowdown.', 0, 3, 15, 'Resolved', 'Issue has been addressed and area is now clear.', 1, '2026-07-23 21:50:00', '2026-07-23 19:53:00'),
(27, 9, 7, 8, 8, 'Bus bunching observed at Taft Avenue, Manila (near DLSU)', 'Several buses are bunching up near Taft Avenue, Manila (near DLSU), likely due to earlier congestion further down the route.', 9, 2, 0, 'Rejected', 'Insufficient evidence / duplicate report.', 7, '2026-07-27 15:43:00', '2026-07-27 14:21:00'),
(28, 6, 7, 8, 8, 'Train service slowdown near Taft Avenue, Manila (near DLSU)', 'Rail service near Taft Avenue, Manila (near DLSU) has slowed down due to a signal check, expect longer waiting times at the platform.', 6, 3, 5, 'Pending', NULL, NULL, NULL, '2026-07-26 17:58:00'),
(29, 11, 7, 8, 8, 'Bus bunching observed at Taft Avenue, Manila (near DLSU)', 'Several buses are bunching up near Taft Avenue, Manila (near DLSU), likely due to earlier congestion further down the route.', 5, 2, 4, 'Verified', 'Confirmed by field officer.', 7, '2026-07-27 21:05:00', '2026-07-27 19:07:00'),
(30, 5, 7, 8, 8, 'Jeepney line delayed near Taft Avenue, Manila (near DLSU)', 'Jeepney queues near Taft Avenue, Manila (near DLSU) are longer than usual due to a shortage of units on this route.', 0, 3, 4, 'Verified', 'Confirmed by field officer.', 7, '2026-07-26 00:46:00', '2026-07-25 21:49:00'),
(31, 12, 8, 80, 7, 'Traffic light malfunctioning at Ortigas Avenue, Pasig', 'The traffic signal at Ortigas Avenue, Pasig is stuck on red in all directions, causing confusion among drivers.', 0, 2, 4, 'Verified', 'Confirmed by field officer.', 8, '2026-07-28 10:36:00', '2026-07-28 07:40:00'),
(32, 4, 8, 80, 7, 'Signal timing issue at Ortigas Avenue, Pasig', 'The traffic light cycle at Ortigas Avenue, Pasig seems off, giving very short green light windows and backing up traffic.', 5, 4, 5, 'Pending', NULL, NULL, NULL, '2026-07-26 23:22:00'),
(33, 12, 8, 80, 7, 'Blinking traffic light at Ortigas Avenue, Pasig', 'The signal light at Ortigas Avenue, Pasig is stuck on blinking yellow, so treat it as a four-way stop for now.', 5, 4, 1, 'Verified', 'Confirmed by field officer.', 8, '2026-07-28 13:45:00', '2026-07-28 10:18:00'),
(34, 3, 8, 80, 7, 'Traffic light malfunctioning at Ortigas Avenue, Pasig', 'The traffic signal at Ortigas Avenue, Pasig is stuck on red in all directions, causing confusion among drivers.', 4, 2, 0, 'Rejected', 'Insufficient evidence / duplicate report.', 8, '2026-07-28 14:22:00', '2026-07-28 12:11:00');

/*
5. MEDIA_ATTACHMENTS
Only report 5 (Taft Avenue) has attachments for now; more can be added per-report as they're supplied.
*/
INSERT INTO media_attachments (report_id, file_url, file_type) VALUES
(5, 'assets/report_media/media1-1.jfif', 'photo'),
(5, 'assets/report_media/media2-1.jpg', 'photo'),
(5, 'assets/report_media/media3-1.jfif', 'photo'),
(5, 'assets/report_media/media4-1.png', 'photo');

/*
6. COMMENTS (100 total, 91 newly generated)
*/
INSERT INTO comments (comment_id, user_id, report_id, comment_text, created_at) VALUES
(10, 2, 5, 'Local barangay should really look into this permanently.', '2026-07-23 04:56:00'),
(11, 10, 5, 'Grabbing an alternate route, thanks for posting this.', '2026-07-23 04:28:00'),
(12, 1, 5, 'Any update on this? Still stuck here.', '2026-07-23 06:03:00'),
(13, 4, 5, 'Seeing a lot of comments about this on other apps too.', '2026-07-23 09:20:00'),
(14, 12, 6, 'Grabbing an alternate route, thanks for posting this.', '2026-07-22 23:38:00'),
(15, 5, 6, 'Better to take the alternate route if you can.', '2026-07-22 22:34:00'),
(16, 2, 7, 'Local barangay should really look into this permanently.', '2026-07-21 23:04:00'),
(17, 11, 7, 'Appreciate the community reports, way better than waze sometimes.', '2026-07-22 04:07:00'),
(18, 6, 7, 'Update: seems to be improving little by little.', '2026-07-21 22:12:00'),
(19, 5, 8, 'Hope everyone involved is okay.', '2026-07-09 02:13:00'),
(20, 6, 8, 'Same here, barely moving for the last 15 minutes.', '2026-07-08 23:57:00'),
(21, 6, 8, 'Is this still ongoing as of this posting?', '2026-07-09 02:31:00'),
(22, 4, 9, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-10 05:57:00'),
(23, 1, 9, 'Appreciate the report, saved me a headache today.', '2026-07-10 05:46:00'),
(24, 1, 9, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-10 02:11:00'),
(25, 5, 9, 'Any update on this? Still stuck here.', '2026-07-10 00:34:00'),
(26, 8, 10, 'Any update on this? Still stuck here.', '2026-07-12 00:01:00'),
(27, 1, 10, 'Enforcers just arrived on scene, might clear up soon.', '2026-07-11 18:53:00'),
(28, 11, 10, 'Same here, barely moving for the last 15 minutes.', '2026-07-12 00:20:00'),
(29, 11, 10, 'Grabbing an alternate route, thanks for posting this.', '2026-07-11 22:40:00'),
(30, 9, 11, 'Looks like it''s slowly clearing up now.', '2026-07-11 20:00:00'),
(31, 11, 11, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-11 14:24:00'),
(32, 5, 11, 'Grabbing an alternate route, thanks for posting this.', '2026-07-11 14:47:00'),
(33, 8, 11, 'Can confirm, passed by a few minutes ago and it''s still bad.', '2026-07-11 21:35:00'),
(34, 1, 12, 'Appreciate the community reports, way better than waze sometimes.', '2026-07-21 07:47:00'),
(35, 8, 12, 'Can confirm, passed by a few minutes ago and it''s still bad.', '2026-07-21 05:59:00'),
(36, 6, 12, 'Public transport is your best bet right now honestly.', '2026-07-21 07:04:00'),
(37, 1, 12, 'Appreciate the report, saved me a headache today.', '2026-07-21 12:31:00'),
(38, 12, 13, 'Is this still ongoing as of this posting?', '2026-07-23 14:17:00'),
(39, 5, 13, 'Confirmed still blocked as of a few minutes ago.', '2026-07-23 13:55:00'),
(40, 11, 13, 'Just avoid this stretch entirely if you''re in a rush.', '2026-07-23 11:56:00'),
(41, 9, 13, 'Hope everyone involved is okay.', '2026-07-23 09:13:00'),
(42, 4, 14, 'Any update on this? Still stuck here.', '2026-07-20 16:51:00'),
(43, 1, 14, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-20 09:44:00'),
(44, 7, 15, 'Just avoid this stretch entirely if you''re in a rush.', '2026-07-22 19:20:00'),
(45, 7, 15, 'Hope everyone involved is okay.', '2026-07-22 23:06:00'),
(46, 12, 15, 'Thanks for the heads up, rerouting now.', '2026-07-22 21:28:00'),
(47, 11, 15, 'Enforcers just arrived on scene, might clear up soon.', '2026-07-22 16:15:00'),
(48, 2, 16, 'Hope everyone involved is okay.', '2026-07-23 15:09:00'),
(49, 9, 16, 'Enforcers just arrived on scene, might clear up soon.', '2026-07-23 08:41:00'),
(50, 5, 17, 'Grabbing an alternate route, thanks for posting this.', '2026-07-22 03:04:00'),
(51, 12, 17, 'Local barangay should really look into this permanently.', '2026-07-22 02:46:00'),
(52, 12, 17, 'This is why I leave the house an hour early nowadays.', '2026-07-21 22:27:00'),
(53, 9, 18, 'Same here, barely moving for the last 15 minutes.', '2026-07-21 06:57:00'),
(54, 9, 18, 'Any update on this? Still stuck here.', '2026-07-21 13:43:00'),
(55, 3, 18, 'Any update on this? Still stuck here.', '2026-07-21 11:32:00'),
(56, 2, 18, 'Appreciate the report, saved me a headache today.', '2026-07-21 09:54:00'),
(57, 10, 19, 'Seeing a lot of comments about this on other apps too.', '2026-07-25 23:39:00'),
(58, 5, 19, 'Is this still ongoing as of this posting?', '2026-07-26 02:40:00'),
(59, 12, 19, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-25 23:00:00'),
(60, 5, 19, 'Confirmed still blocked as of a few minutes ago.', '2026-07-25 22:05:00'),
(61, 6, 20, 'Any update on this? Still stuck here.', '2026-07-23 01:55:00'),
(62, 4, 20, 'Just avoid this stretch entirely if you''re in a rush.', '2026-07-22 20:50:00'),
(63, 5, 21, 'Enforcers just arrived on scene, might clear up soon.', '2026-07-26 11:11:00'),
(64, 2, 21, 'Local barangay should really look into this permanently.', '2026-07-26 04:55:00'),
(65, 11, 22, 'Grabbing an alternate route, thanks for posting this.', '2026-07-24 01:46:00'),
(66, 5, 22, 'Thanks for the heads up, rerouting now.', '2026-07-23 18:43:00'),
(67, 9, 23, 'Thanks for the heads up, rerouting now.', '2026-07-24 15:08:00'),
(68, 6, 23, 'Update: seems to be improving little by little.', '2026-07-24 12:43:00'),
(69, 4, 24, 'Appreciate the report, saved me a headache today.', '2026-07-26 00:13:00'),
(70, 2, 24, 'Just avoid this stretch entirely if you''re in a rush.', '2026-07-26 01:11:00'),
(71, 9, 24, 'Grabbing an alternate route, thanks for posting this.', '2026-07-25 23:49:00'),
(72, 11, 25, 'Appreciate the report, saved me a headache today.', '2026-07-23 22:24:00'),
(73, 3, 25, 'Enforcers just arrived on scene, might clear up soon.', '2026-07-23 23:23:00'),
(74, 1, 25, 'This is why I leave the house an hour early nowadays.', '2026-07-23 22:01:00'),
(75, 2, 26, 'Looks like it''s slowly clearing up now.', '2026-07-23 22:57:00'),
(76, 11, 26, 'Seeing a lot of comments about this on other apps too.', '2026-07-23 23:22:00'),
(77, 11, 26, 'Local barangay should really look into this permanently.', '2026-07-23 22:47:00'),
(78, 10, 27, 'Grabbing an alternate route, thanks for posting this.', '2026-07-27 17:22:00'),
(79, 12, 27, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-27 18:09:00'),
(80, 10, 28, 'This has been an issue for a while now, hope it gets fixed permanently.', '2026-07-26 23:42:00'),
(81, 2, 28, 'Public transport is your best bet right now honestly.', '2026-07-26 22:22:00'),
(82, 5, 28, 'Grabbing an alternate route, thanks for posting this.', '2026-07-26 20:50:00'),
(83, 5, 29, 'Public transport is your best bet right now honestly.', '2026-07-27 20:59:00'),
(84, 7, 29, 'Update: seems to be improving little by little.', '2026-07-28 00:22:00'),
(85, 2, 30, 'Appreciate the report, saved me a headache today.', '2026-07-26 03:38:00'),
(86, 5, 30, 'Appreciate the report, saved me a headache today.', '2026-07-26 04:46:00'),
(87, 4, 30, 'Better to take the alternate route if you can.', '2026-07-25 22:06:00'),
(88, 12, 31, 'Confirmed still blocked as of a few minutes ago.', '2026-07-28 11:58:00'),
(89, 7, 31, 'Appreciate the report, saved me a headache today.', '2026-07-28 09:00:00'),
(90, 8, 32, 'Thanks for the heads up, rerouting now.', '2026-07-27 04:12:00'),
(91, 4, 32, 'Enforcers just arrived on scene, might clear up soon.', '2026-07-27 03:20:00'),
(92, 3, 32, 'Hope everyone involved is okay.', '2026-07-27 05:08:00'),
(93, 9, 32, 'Local barangay should really look into this permanently.', '2026-07-27 04:31:00'),
(94, 5, 33, 'Seeing a lot of comments about this on other apps too.', '2026-07-28 14:31:00'),
(95, 11, 33, 'Appreciate the report, saved me a headache today.', '2026-07-28 12:43:00'),
(96, 8, 33, 'Any update on this? Still stuck here.', '2026-07-28 16:28:00'),
(97, 5, 33, 'Appreciate the report, saved me a headache today.', '2026-07-28 12:42:00'),
(98, 6, 34, 'Local barangay should really look into this permanently.', '2026-07-29 21:59:00'),
(99, 7, 34, 'Thanks for the heads up, rerouting now.', '2026-07-29 19:46:00'),
(100, 7, 34, 'Confirmed still blocked as of a few minutes ago.', '2026-07-30 01:44:00');

/*
7. UPVOTES (123 newly generated rows)
*/
INSERT INTO upvotes (user_id, report_id) VALUES
(2, 6),
(12, 6),
(8, 6),
(11, 6),
(3, 6),
(1, 7),
(12, 8),
(2, 8),
(3, 8),
(4, 8),
(6, 9),
(4, 9),
(8, 9),
(7, 9),
(11, 9),
(10, 9),
(2, 9),
(3, 9),
(1, 9),
(7, 10),
(9, 11),
(3, 11),
(11, 11),
(2, 11),
(2, 12),
(12, 12),
(8, 12),
(11, 12),
(7, 12),
(5, 12),
(10, 12),
(6, 12),
(3, 12),
(4, 13),
(2, 13),
(7, 13),
(4, 14),
(5, 14),
(9, 14),
(12, 14),
(11, 14),
(2, 14),
(8, 14),
(10, 14),
(7, 15),
(2, 15),
(4, 15),
(10, 15),
(11, 15),
(5, 15),
(1, 16),
(2, 16),
(4, 16),
(3, 16),
(9, 16),
(7, 16),
(6, 16),
(10, 16),
(5, 17),
(4, 17),
(1, 17),
(11, 18),
(9, 19),
(7, 19),
(2, 19),
(1, 19),
(8, 20),
(9, 20),
(5, 21),
(11, 21),
(4, 21),
(6, 21),
(3, 22),
(5, 22),
(12, 22),
(8, 22),
(11, 22),
(7, 23),
(3, 23),
(5, 24),
(4, 24),
(9, 25),
(7, 25),
(6, 25),
(10, 27),
(2, 27),
(7, 27),
(4, 27),
(3, 27),
(1, 27),
(8, 27),
(6, 27),
(12, 27),
(5, 28),
(10, 28),
(3, 28),
(4, 28),
(9, 28),
(7, 28),
(8, 29),
(12, 29),
(10, 29),
(4, 29),
(5, 29),
(9, 32),
(11, 32),
(10, 32),
(8, 32),
(12, 32),
(6, 33),
(9, 33),
(2, 33),
(3, 33),
(10, 33),
(1, 34),
(10, 34),
(7, 34),
(8, 34),
(12, 34),
(3, 34),
(6, 34),
(4, 34),
(2, 34);

/*
8. RESOLVED_MARKS (subset of upvoters, for reports with resolved_count >= 4)
*/
INSERT INTO resolved_marks (user_id, report_id) VALUES
(1, 7),
(12, 8), (2, 8),
(6, 9), (4, 9), (8, 9),
(9, 11), (3, 11),
(4, 13), (2, 13), (7, 13),
(4, 14), (5, 14), (9, 14),
(9, 19), (7, 19),
(5, 21), (11, 21), (4, 21),
(7, 23), (3, 23),
(5, 24), (4, 24),
(2, 26), (11, 26),
(5, 28), (10, 28), (3, 28),
(9, 32), (11, 32), (10, 32);

/*
9. AUDIT LOGS (23 newly generated rows)
*/
INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES
(7, 'Verify Report', 'report', 6, 'Auto-generated seed action for report #6.', '10.0.0.218', '2026-07-22 22:05:00'),
(7, 'Resolve Report', 'report', 7, 'Auto-generated seed action for report #7.', '10.0.0.171', '2026-07-21 23:01:00'),
(8, 'Resolve Report', 'report', 8, 'Auto-generated seed action for report #8.', '10.0.0.43', '2026-07-09 00:39:00'),
(8, 'Reject Report', 'report', 9, 'Auto-generated seed action for report #9.', '10.0.0.65', '2026-07-10 01:39:00'),
(8, 'Verify Report', 'report', 10, 'Auto-generated seed action for report #10.', '10.0.0.99', '2026-07-11 18:41:00'),
(8, 'Resolve Report', 'report', 11, 'Auto-generated seed action for report #11.', '10.0.0.224', '2026-07-11 13:39:00'),
(8, 'Verify Report', 'report', 12, 'Auto-generated seed action for report #12.', '10.0.0.123', '2026-07-21 05:53:00'),
(8, 'Resolve Report', 'report', 13, 'Auto-generated seed action for report #13.', '10.0.0.7', '2026-07-23 10:10:00'),
(2, 'Verify Report', 'report', 17, 'Auto-generated seed action for report #17.', '10.0.0.150', '2026-07-21 22:57:00'),
(2, 'Verify Report', 'report', 20, 'Auto-generated seed action for report #20.', '10.0.0.182', '2026-07-22 20:44:00'),
(2, 'Resolve Report', 'report', 21, 'Auto-generated seed action for report #21.', '10.0.0.54', '2026-07-26 05:02:00'),
(2, 'Reject Report', 'report', 22, 'Auto-generated seed action for report #22.', '10.0.0.182', '2026-07-23 22:28:00'),
(1, 'Resolve Report', 'report', 24, 'Auto-generated seed action for report #24.', '10.0.0.251', '2026-07-25 22:05:00'),
(1, 'Resolve Report', 'report', 26, 'Auto-generated seed action for report #26.', '10.0.0.31', '2026-07-23 21:50:00'),
(7, 'Reject Report', 'report', 27, 'Auto-generated seed action for report #27.', '10.0.0.238', '2026-07-27 15:43:00'),
(7, 'Verify Report', 'report', 29, 'Auto-generated seed action for report #29.', '10.0.0.123', '2026-07-27 21:05:00'),
(7, 'Verify Report', 'report', 30, 'Auto-generated seed action for report #30.', '10.0.0.64', '2026-07-26 00:46:00'),
(8, 'Verify Report', 'report', 31, 'Auto-generated seed action for report #31.', '10.0.0.230', '2026-07-28 10:36:00'),
(8, 'Verify Report', 'report', 33, 'Auto-generated seed action for report #33.', '10.0.0.61', '2026-07-28 13:45:00'),
(2, 'Create Thread', 'thread', 5, 'Created thread: Marikina Riverbanks Road Construction Delays.', '10.0.0.139', '2026-07-22 08:00:00'),
(1, 'Create Thread', 'thread', 6, 'Created thread: Quiapo Stalled Delivery Truck Blocking Lane.', '10.0.0.193', '2026-07-23 16:20:00'),
(7, 'Create Thread', 'thread', 7, 'Created thread: LRT-1 Service Disruption Near Taft Station.', '10.0.0.190', '2026-07-24 06:45:00'),
(8, 'Create Thread', 'thread', 8, 'Created thread: Broken Traffic Light at Ortigas-Meralco Junction.', '10.0.0.141', '2026-07-25 17:10:00');