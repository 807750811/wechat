/*
Navicat MySQL Data Transfer

Source Server         : 10.68.17.106
Source Server Version : 50719
Source Host           : 10.68.17.106:3306
Source Database       : wechat

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2017-08-18 15:09:22
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for friend_tag
-- ----------------------------
DROP TABLE IF EXISTS `friend_tag`;
CREATE TABLE `friend_tag` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) NOT NULL,
  `belongs_uid` int(10) unsigned NOT NULL,
  `list` varchar(5000) DEFAULT NULL,
  `non_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of friend_tag
-- ----------------------------
INSERT INTO `friend_tag` VALUES ('1', '前端码屌', '1', '[2,3,4,5,6]', '0');
INSERT INTO `friend_tag` VALUES ('2', '网红', '1', '[7,8,9,10,11]', '0');
INSERT INTO `friend_tag` VALUES ('3', '我心中的女神', '1', '[12,13]', '0');
INSERT INTO `friend_tag` VALUES ('4', '我的好友', '1', '[1]', '1');
INSERT INTO `friend_tag` VALUES ('5', '我的好友', '13', '[13,1,12,6,8]', '1');
INSERT INTO `friend_tag` VALUES ('6', '我的好友', '12', '[12,1,13,2,3]', '1');
INSERT INTO `friend_tag` VALUES ('7', '我的好友', '2', '[2,1,12]', '1');
INSERT INTO `friend_tag` VALUES ('8', '我的好友', '3', '[3,1,12]', '1');
INSERT INTO `friend_tag` VALUES ('9', '我的好友', '4', '[4,1]', '1');
INSERT INTO `friend_tag` VALUES ('10', '我的好友', '5', '[5,1]', '1');
INSERT INTO `friend_tag` VALUES ('11', '我的好友', '6', '[6,1,13]', '1');
INSERT INTO `friend_tag` VALUES ('12', '我的好友', '7', '[7,1]', '1');
INSERT INTO `friend_tag` VALUES ('13', '我的好友', '8', '[8,1,13]', '1');
INSERT INTO `friend_tag` VALUES ('14', '我的好友', '9', '[9,1]', '1');
INSERT INTO `friend_tag` VALUES ('15', '我的好友', '10', '[10,1]', '1');
INSERT INTO `friend_tag` VALUES ('16', '我的好友', '11', '[11,1]', '1');

-- ----------------------------
-- Table structure for game_result
-- ----------------------------
DROP TABLE IF EXISTS `game_result`;
CREATE TABLE `game_result` (
  `rec_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `win_times` int(11) DEFAULT '0',
  `lose_times` int(11) DEFAULT '0',
  `escape_times` int(11) DEFAULT '0',
  PRIMARY KEY (`rec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of game_result
-- ----------------------------

-- ----------------------------
-- Table structure for groups
-- ----------------------------
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL,
  `group_avatar` varchar(255) NOT NULL,
  `member_list` varchar(5000) DEFAULT NULL,
  `creator` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of groups
-- ----------------------------
INSERT INTO `groups` VALUES ('1', '前端群', 'http://tva1.sinaimg.cn/crop.0.0.200.200.50/006q8Q6bjw8f20zsdem2mj305k05kdfw.jpg', '[1,12,13]', '12');
INSERT INTO `groups` VALUES ('2', 'Fly社区官方群', 'http://tva2.sinaimg.cn/crop.0.0.199.199.180/005Zseqhjw1eplix1brxxj305k05kjrf.jpg', '[1,12,13]', '12');

-- ----------------------------
-- Table structure for msg_box
-- ----------------------------
DROP TABLE IF EXISTS `msg_box`;
CREATE TABLE `msg_box` (
  `msg_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` tinyint(1) unsigned NOT NULL COMMENT '消息类型:1-好友申请2-拒绝添加3-同意添加',
  `uid` int(10) unsigned NOT NULL COMMENT '本用户uid',
  `from_uid` int(10) unsigned NOT NULL COMMENT '申请人uid',
  `from_group` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请人移入的群组id',
  `remark` varchar(255) DEFAULT NULL COMMENT '申请备注',
  `addtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '申请时间',
  `my_is_read` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '我是否已读',
  `from_is_read` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '对方是否已读',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请加入群组ID',
  `add_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1-添加用户 2-加入群组',
  PRIMARY KEY (`msg_id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of msg_box
-- ----------------------------
INSERT INTO `msg_box` VALUES ('63', '3', '12', '13', '5', 'O(∩_∩)O哈哈~', '2017-03-23 15:46:21', '1', '1', '0', '1');
INSERT INTO `msg_box` VALUES ('64', '3', '2', '12', '6', '', '2017-03-23 15:48:38', '1', '1', '0', '1');
INSERT INTO `msg_box` VALUES ('65', '3', '3', '12', '6', '', '2017-03-23 16:02:55', '1', '1', '0', '1');
INSERT INTO `msg_box` VALUES ('66', '3', '6', '13', '5', '', '2017-03-23 16:05:48', '1', '1', '0', '1');
INSERT INTO `msg_box` VALUES ('67', '3', '13', '8', '13', '\\(^o^)/YES!', '2017-03-23 16:31:08', '1', '1', '0', '1');

-- ----------------------------
-- Table structure for user_info
-- ----------------------------
DROP TABLE IF EXISTS `user_info`;
CREATE TABLE `user_info` (
  `rec_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `mobile` varchar(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `education` tinyint(2) DEFAULT NULL,
  `shengxiao` tinyint(2) DEFAULT NULL,
  `sex` tinyint(1) DEFAULT NULL,
  `birthday` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`rec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of user_info
-- ----------------------------
INSERT INTO `user_info` VALUES ('2', '13', '15813688548', '999669@qq.com', '7', '4', '0', '2000-03-23 00:00:00');
INSERT INTO `user_info` VALUES ('3', '12', '15896521458', '88985@qq.com', '4', '5', '0', '1999-03-25 00:00:00');
INSERT INTO `user_info` VALUES ('4', '3', null, null, null, null, null, null);
INSERT INTO `user_info` VALUES ('5', '6', null, null, null, null, null, null);
INSERT INTO `user_info` VALUES ('6', '8', '15896521456', '88565@qq.com', '3', '4', '0', '1999-03-26 00:00:00');
INSERT INTO `user_info` VALUES ('7', '10', null, null, null, null, null, null);
INSERT INTO `user_info` VALUES ('8', '11', null, null, null, null, null, null);
INSERT INTO `user_info` VALUES ('9', '1', null, null, null, null, null, null);
INSERT INTO `user_info` VALUES ('10', '4', null, null, null, null, null, null);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) DEFAULT NULL COMMENT '登录用户名',
  `password` varchar(100) DEFAULT NULL COMMENT '登录密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '个人昵称',
  `signature` varchar(255) DEFAULT NULL COMMENT '个人签名',
  `avatar` varchar(255) DEFAULT NULL COMMENT '个人头像',
  `groups_list` varchar(5000) DEFAULT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', '123456', '7c4a8d09ca3762af61e59520943dc26494f8941b', '让我们荡起双桨', '哥没做大哥好多年了', 'http://cdn.firstlinkapp.com/upload/2016_6/1465575923433_33812.jpg', '[1,2]');
INSERT INTO `users` VALUES ('2', 'test1000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '贤心', '这些都是测试数据，实际使用请严格按照该格式返回', 'http://tp1.sinaimg.cn/1571889140/180/40030060651/1', '');
INSERT INTO `users` VALUES ('3', 'test2000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Z_子晴', '微电商达人', 'http://tva3.sinaimg.cn/crop.0.0.512.512.180/8693225ajw8f2rt20ptykj20e80e8weu.jpg', '');
INSERT INTO `users` VALUES ('4', 'test3', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Lemon_CC', null, 'http://tp2.sinaimg.cn/1833062053/180/5643591594/0', '');
INSERT INTO `users` VALUES ('5', 'test4000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '马小云', '让天下没有难写的代码', 'http://tp4.sinaimg.cn/2145291155/180/5601307179/1', '');
INSERT INTO `users` VALUES ('6', 'test5000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '徐小峥', '代码在囧途，也要写到底', 'http://tp2.sinaimg.cn/1783286485/180/5677568891/1', '');
INSERT INTO `users` VALUES ('7', 'test6000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '罗玉凤', '在自己实力不济的时候，不要去相信什么媒体和记者。他们不是善良的人，有时候候他们的采访对当事人而言就是陷阱', 'http://tp1.sinaimg.cn/1241679004/180/5743814375/0', '');
INSERT INTO `users` VALUES ('8', 'test7000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '长泽梓Azusa', '我是日本女艺人长泽あずさ', '/Uploads/avatar/2017/03/24/atkS3p.jpg', null);
INSERT INTO `users` VALUES ('9', 'test8000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '大鱼_MsYuyu', '我瘋了！這也太準了吧  超級笑點低', 'http://tp1.sinaimg.cn/5286730964/50/5745125631/0', '');
INSERT INTO `users` VALUES ('10', 'test9000001', '7c4a8d09ca3762af61e59520943dc26494f8941b', '谢楠', '\\呵呵 哈哈\\/ 尝试 \\/ \\`', 'http://tp4.sinaimg.cn/1665074831/180/5617130952/0', '');
INSERT INTO `users` VALUES ('11', 'guest1', '7c4a8d09ca3762af61e59520943dc26494f8941b', '柏雪近在它香', '千里冰封，万里雪飘', 'http://tp2.sinaimg.cn/2518326245/180/5636099025/0', '');
INSERT INTO `users` VALUES ('12', 'zengxijie', '7c4a8d09ca3762af61e59520943dc26494f8941b', '林心如', '\\(^o^)/YES!', 'http://tp3.sinaimg.cn/1223762662/180/5741707953/0', '[1,2]');
INSERT INTO `users` VALUES ('13', 'zxj', '7c4a8d09ca3762af61e59520943dc26494f8941b', '佟丽娅', 'O(∩_∩)O哈哈~', 'http://tva3.sinaimg.cn/crop.0.0.750.750.180/5033b6dbjw8etqysyifpkj20ku0kuwfw.jpg', '[1,2]');
