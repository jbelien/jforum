-- Adminer 4.2.4 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';

CREATE TABLE `jforum_board` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `desc` mediumtext NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `id_parent` int(11) DEFAULT NULL,
  `id_category` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_id_parent` (`id_parent`),
  KEY `FK_id_category` (`id_category`),
  CONSTRAINT `FK_board_1` FOREIGN KEY (`id_parent`) REFERENCES `jforum_board` (`id`),
  CONSTRAINT `FK_board_2` FOREIGN KEY (`id_category`) REFERENCES `jforum_category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `jforum_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `jforum_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `color` varchar(45) DEFAULT NULL,
  `nbmsg` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `jforum_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_topic` int(11) NOT NULL DEFAULT '0',
  `title` varchar(45) DEFAULT NULL,
  `content` text NOT NULL,
  `date` datetime NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_id_user` (`id_user`),
  KEY `FK_id_topic` (`id_topic`),
  CONSTRAINT `FK_message_1` FOREIGN KEY (`id_topic`) REFERENCES `jforum_topic` (`id`),
  CONSTRAINT `FK_message_2` FOREIGN KEY (`id_user`) REFERENCES `jforum_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `jforum_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `id_board` int(11) NOT NULL DEFAULT '0',
  `id_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_id_board` (`id_board`),
  KEY `FK_id_user` (`id_user`),
  CONSTRAINT `FK_topic_1` FOREIGN KEY (`id_board`) REFERENCES `jforum_board` (`id`),
  CONSTRAINT `FK_topic_2` FOREIGN KEY (`id_user`) REFERENCES `jforum_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `jforum_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `email` varchar(45) NOT NULL,
  `password` varchar(45) NOT NULL COMMENT 'MD5()',
  `id_group` varchar(45) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2018-02-18 10:34:15
