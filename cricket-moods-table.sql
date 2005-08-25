-- 
-- Table structure for table `cm_moods`
-- 

CREATE TABLE `cm_moods` (
  `mood_id` smallint(6) NOT NULL auto_increment,
  `mood_name` varchar(64) NOT NULL default '',
  `mood_image` varchar(64) default NULL,
  PRIMARY KEY  (`mood_id`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `cm_moods`
-- 

INSERT INTO `cm_moods` VALUES (1, 'Esctatic', 'icon_biggrin.gif');
INSERT INTO `cm_moods` VALUES (2, 'Confused', 'icon_confused.gif');
INSERT INTO `cm_moods` VALUES (3, 'Cool', 'icon_cool.gif');
INSERT INTO `cm_moods` VALUES (4, 'Confused', 'eusa_think.gif');
INSERT INTO `cm_moods` VALUES (5, 'Sad', 'icon_cry.gif');
INSERT INTO `cm_moods` VALUES (6, 'Alarmed', 'icon_eek.gif');
INSERT INTO `cm_moods` VALUES (7, 'Angry', 'icon_evil.gif');
INSERT INTO `cm_moods` VALUES (8, 'Bored', 'icon_neutral.gif');
INSERT INTO `cm_moods` VALUES (9, 'Playful', 'icon_razz.gif');
INSERT INTO `cm_moods` VALUES (10, 'Sickly', 'icon_sad.gif');
INSERT INTO `cm_moods` VALUES (11, 'Happy', 'icon_smile.gif');
INSERT INTO `cm_moods` VALUES (12, 'Surprised', 'icon_surprised.gif');
INSERT INTO `cm_moods` VALUES (13, 'Mischievous', 'icon_twisted.gif');
INSERT INTO `cm_moods` VALUES (14, 'Flirtatious', 'icon_wink.gif');
        