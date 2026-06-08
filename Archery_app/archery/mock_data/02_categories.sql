-- categories (12 rows)
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `categories`;
SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('1', 'compound', 'male', '50+');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('2', 'recurve', 'male', 'open');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('3', 'recurve barebow', 'male', 'open');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('4', 'recurve barebow', 'female', '50+');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('5', 'recurve', 'female', 'open');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('6', 'recurve barebow', 'male', '50+');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('7', 'compound', 'male', 'open');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('8', 'recurve barebow', 'female', 'open');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('9', 'compound', 'female', '50+');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('10', 'recurve', 'female', '50+');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('11', 'recurve', 'male', '50+');
INSERT IGNORE INTO `categories` (`category_id`, `bow_type`, `gender`, `age`) VALUES ('12', 'compound', 'female', 'open');