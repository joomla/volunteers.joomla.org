-- Give the members of the Board of Directors (department 58) the roles of the board department instead of
-- the old roles without a department or team. Run this after 2026-10-24-copy-live-data.sql.
-- 33 President => 286, 36 Vice President => 287, 35 Secretary => 288, 34 Treasurer => 289
UPDATE `#__volunteers_members`
   SET `role` = CASE `role` WHEN 33 THEN 286 WHEN 36 THEN 287 WHEN 35 THEN 288 WHEN 34 THEN 289 END
 WHERE `department` = 58 AND `role` IN (33, 34, 35, 36);
