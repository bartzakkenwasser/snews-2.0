-- Run this once against your live snews18 database to raise the homepage
-- article limit from 3 to 10.
-- After running it, reload the homepage — up to 10 articles will appear.

USE `snews20`;

UPDATE `settings`
SET    `value` = '10'
WHERE  `name`  = 'article_limit';

