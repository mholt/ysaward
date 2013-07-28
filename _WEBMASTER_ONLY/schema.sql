# Use this file to set up all the MySQL database tables for a YSA site.
# Replace DB_NAME_HERE with the name of your database (2 instances).
# Note: MyISAM is typically a better table engine for reads/selects,
# and InnoDB is generally better for frequent updates, inserts, and deletes.

delimiter ;

CREATE DATABASE IF NOT EXISTS `DB_NAME_HERE`;

USE `DB_NAME_HERE`;


CREATE TABLE IF NOT EXISTS `Callings` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(120) NOT NULL,
  `Preset` tinyint(1) DEFAULT '0',
  `WardID` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Credentials` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Salt` varchar(16) NOT NULL,
  `MemberID` bigint(20) unsigned DEFAULT NULL,
  `StakeLeaderID` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`),
  UNIQUE KEY `Email_UNIQUE` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `EmailJobs` (
  `ID` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `Started` timestamp NULL DEFAULT NULL,
  `Ended` timestamp NULL DEFAULT NULL,
  `MemberID` bigint(11) unsigned DEFAULT NULL,
  `StakeLeaderID` bigint(11) unsigned DEFAULT NULL,
  `SenderName` varchar(45) NOT NULL,
  `SenderEmail` varchar(255) NOT NULL,
  `Recipients` mediumtext NOT NULL,
  `Subject` varchar(255) NOT NULL,
  `Message` mediumtext NOT NULL,
  `IsHTML` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `FailedRecipients` mediumtext,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `FheGroups` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `WardID` bigint(20) unsigned NOT NULL,
  `GroupName` varchar(45) NOT NULL DEFAULT '',
  `Leader1` bigint(20) unsigned DEFAULT NULL,
  `Leader2` bigint(20) unsigned DEFAULT NULL,
  `Leader3` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `GrantedPrivileges` (
  `ID` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `PrivilegeID` bigint(10) NOT NULL,
  `MemberID` bigint(10) DEFAULT NULL,
  `CallingID` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `Members` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `CredentialsID` bigint(20) unsigned DEFAULT NULL,
  `WardID` bigint(20) unsigned NOT NULL,
  `FirstName` varchar(45) NOT NULL,
  `MiddleName` varchar(45) DEFAULT NULL,
  `LastName` varchar(45) NOT NULL,
  `Gender` tinyint(1) unsigned DEFAULT '0',
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `ResidenceID` bigint(20) unsigned DEFAULT NULL,
  `Apartment` varchar(45) DEFAULT NULL,
  `Birthday` date DEFAULT NULL,
  `PictureFile` varchar(255) DEFAULT NULL,
  `LastUpdated` timestamp NULL DEFAULT NULL,
  `LastActivity` timestamp NULL DEFAULT NULL,
  `RegistrationDate` timestamp NULL DEFAULT NULL,
  `HidePhone` tinyint(1) unsigned DEFAULT '0',
  `HideEmail` tinyint(1) unsigned DEFAULT '0',
  `HideBirthday` tinyint(1) unsigned DEFAULT '0',
  `FheGroup` bigint(20) unsigned DEFAULT '0',
  `ReceiveEmails` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `ReceiveTexts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`),
  UNIQUE KEY `CredentialsID_UNIQUE` (`CredentialsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `MembersCallings` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `MemberID` bigint(20) unsigned NOT NULL,
  `CallingID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Permissions` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `QuestionID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ObjectID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ObjectType` enum('Calling','Member') NOT NULL DEFAULT 'Calling',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;





DROP TABLE IF EXISTS `Privileges`;
CREATE TABLE `Privileges` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Privilege` varchar(100) NOT NULL,
  `HelpText` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`),
  UNIQUE KEY `Privilege_UNIQUE` (`Privilege`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `Privileges` VALUES 
	(1,'Mass-email all ward members','May send mass-emails to all ward members from the website'),
	(2,'Mass-email all brothers in the ward','May send mass-emails to the men in the ward from the website'),
	(3,'Mass-email all sisters in the ward','May send mass-emails to the women in the ward from the website'),
	(4,'Export file has all (including hidden) email addresses','Is able to get all email addresses, even if members opted to hide theirs, with the Export feature'),
	(5,'Export file has all (including hidden) phone numbers','Is able to get all phone numbers, even if members opted to hide theirs, with the Export feature'),
	(6,'Export file has all (including hidden) full birth dates','Is able to get the birthdays of every member including year, exposing the age of the members, even if they opted to hide it, with the Export feature'),
	(7,'Manage: FHE groups','May organize members into FHE groups on the website'),
	(8, 'Manage: survey questions', 'Create, change, or delete ward survey questions'),
	(9, 'Manage: survey permissions', 'Grant permissions for certain members or callings to see answers to certain survey questions'),
	(10, 'Manage: site privileges', 'Grant privileges to members or callings to have extra access to this page, where they can assign privileges for extra things on the site'),
	(11, 'Manage: callings', 'Manage callings; members can be assigned to callings and callings can be deleted. Through callings, members have access to various survey questions'),
	(12, 'Manage: profile pictures', 'Change or remove profile pictures of any ward member'),
	(13, 'Manage: delete accounts', 'Delete member accounts or prune accounts of those who are no longer in the ward'),
	(14, 'Send texts to all ward members', 'May send mass or individual texts to all members of the ward from the website'),
	(15, 'Send texts to members of FHE group', 'May send texts to the member\'s FHE group from the website');





CREATE TABLE IF NOT EXISTS `PwdResetTokens` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `CredentialsID` bigint(20) unsigned DEFAULT NULL,
  `Token` varchar(45) DEFAULT NULL,
  `Timestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Residences` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `WardID` bigint(20) unsigned NOT NULL,
  `Name` varchar(64) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `City` varchar(64) DEFAULT NULL,
  `State` varchar(45) DEFAULT NULL,
  `PostalCode` varchar(45) DEFAULT NULL,
  `Custom` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;





CREATE TABLE IF NOT EXISTS `StakeLeaders` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `CredentialsID` bigint(20) unsigned NOT NULL,
  `StakeID` bigint(20) unsigned NOT NULL,
  `Gender` tinyint(1) unsigned NOT NULL,
  `Calling` varchar(45) NOT NULL,
  `Title` varchar(45) NOT NULL,
  `FirstName` varchar(45) NOT NULL,
  `MiddleName` varchar(45) NOT NULL,
  `LastName` varchar(45) NOT NULL,
  `ViewGender` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `LastActivity` timestamp NULL DEFAULT NULL,
  `RegistrationDate` timestamp NULL DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`),
  UNIQUE KEY `Email_UNIQUE` (`CredentialsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Stakes` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(65) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `SurveyAnswerOptions` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `QuestionID` int(10) unsigned NOT NULL,
  `AnswerValue` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `SurveyAnswers` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `QuestionID` int(10) unsigned NOT NULL,
  `MemberID` int(10) unsigned NOT NULL,
  `AnswerValue` text,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `SurveyQuestions` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Question` varchar(255) NOT NULL,
  `QuestionType` varchar(30) NOT NULL,
  `Required` tinyint(1) DEFAULT '0',
  `Visible` tinyint(1) DEFAULT '1',
  `WardID` bigint(20) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `SMSJobs` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `WardID` bigint(20) unsigned DEFAULT NULL,
  `StakeID` bigint(20) unsigned DEFAULT NULL,
  `NumbersUsed` text NOT NULL,
  `SenderID` bigint(20) unsigned NOT NULL,
  `SenderName` varchar(90) NOT NULL,
  `SenderPhone` varchar(45) DEFAULT NULL,
  `Message` text NOT NULL,
  `SegmentCount` int(11) DEFAULT NULL,
  `Cost` decimal(4,4) NOT NULL,
  `Recipients` mediumtext,
  `Started` timestamp NULL DEFAULT NULL,
  `Finished` timestamp NULL DEFAULT NULL,
  `FailedRecipients` mediumtext,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




CREATE TABLE IF NOT EXISTS `Wards` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `Name` varchar(65) NOT NULL,
  `StakeID` bigint(20) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Salt` varchar(32) DEFAULT NULL,
  `Balance` decimal(10,4) NOT NULL,
  `LastReimbursement` date DEFAULT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID_UNIQUE` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;









CREATE VIEW `AllPermissions` AS

SELECT P.ID, P.ObjectID, CONCAT(M.FirstName, ' ', M.LastName) AS Name, M.WardID, Q.Question, 'Member' AS `Type`
  FROM Members M
 INNER JOIN Permissions P
    ON M.ID = P.ObjectID
   AND P.ObjectType = 'Member'
 INNER JOIN SurveyQuestions Q
    ON Q.ID = P.QuestionID

 UNION ALL

SELECT P.ID, P.ObjectID, C.Name, C.WardID, Q.Question, 'Calling' AS `Type`
  FROM Callings C
 INNER JOIN Permissions P
    ON C.ID = P.ObjectID
   AND P.ObjectType = 'Calling'
 INNER JOIN SurveyQuestions Q
    ON Q.ID = P.QuestionID;