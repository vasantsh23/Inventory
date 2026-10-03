-- ------------------------------------------------------------
-- Diamond Data Upload: server-side source file
--
-- Adds the `path` row Diamond Data Upload looks up by
-- description = 'upload' (only if it isn't there already).
--   path blank   -> the admin is prompted to choose the CSV file
--   path filled  -> that file is imported directly from the server;
--                   give the FULL path including the file name, e.g.
--                   /home/youraccount/feeds/diamonds.csv
-- ------------------------------------------------------------
INSERT INTO path (description, path)
SELECT 'upload', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM path WHERE description = 'upload');
