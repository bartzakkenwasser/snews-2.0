-- -----------------------------------------------------------------------------
--  sNews 2.0 - security update for an EXISTING database (run it in phpMyAdmin -> SQL)
--  Does NOT drop or re-create anything. Review each statement before running it.
-- -----------------------------------------------------------------------------
USE `snews18`;

-- 1. Server-side script types must not be uploadable. (The updated PHP code also blocks them,
--    this removes them from the settings as well. Adjust if you added your own file types.)
UPDATE `settings` SET `value` = 'txt,htm,html'          WHERE `name` = 'file_extensions';
UPDATE `settings` SET `value` = 'htm,html,txt,css,js'   WHERE `name` = 'allowed_files';

-- 2. Replace the install default login (admin / admin).
--    Easiest: log in, open Settings -> "Change Username and Password" (new password: at least 10 characters).
--    Or by SQL: first make a hash on your computer:
--        php -r "echo password_hash('A-long-password-of-your-own', PASSWORD_DEFAULT);"
--    then run (paste the hash between the quotes):
-- UPDATE `settings` SET `value` = 'PASTE_HASH_HERE' WHERE `name` = 'password';
-- UPDATE `settings` SET `value` = 'yourusername'    WHERE `name` = 'username';   -- 4-13 letters/digits

-- 3. A database account for the website that can only do what sNews needs (not "root").
--    Choose your own long random password, then put the user name and password in config.php.
-- CREATE USER 'snews_app'@'localhost' IDENTIFIED BY 'PUT-A-LONG-RANDOM-PASSWORD-HERE';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `snews18`.* TO 'snews_app'@'localhost';
-- FLUSH PRIVILEGES;
