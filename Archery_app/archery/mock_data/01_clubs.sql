-- clubs (10 rows)
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `clubs`;
SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('1', 'Tigers');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('2', 'Eagles');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('3', 'Hawks');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('4', 'Falcons');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('5', 'Chimpanzees');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('6', 'Parrots');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('7', 'Quails');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('8', 'Apes');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('9', 'Cats');
INSERT IGNORE INTO `clubs` (`club_id`, `club_name`) VALUES ('10', 'Capybaras');