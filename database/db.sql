-- phpMyAdmin SQL Dump
-- version 4.8.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 23, 2019 at 03:36 PM
-- Server version: 5.7.28-0ubuntu0.18.04.4
-- PHP Version: 7.2.24-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `taslibsql`
--

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `documentid` bigint(20) UNSIGNED NOT NULL,
  `documentcaption` varchar(255) DEFAULT NULL,
  `filepath` varchar(255) DEFAULT NULL,
  `linkerid` bigint(20) NOT NULL,
  `linkertype` varchar(100) NOT NULL,
  `isdefault` int(11) DEFAULT '0',
  `status` int(11) DEFAULT '0',
  `adddate` datetime DEFAULT NULL,
  `updatedate` datetime DEFAULT NULL,
  `size` bigint(20) NOT NULL DEFAULT '0',
  `originalname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `emailcms`
--

CREATE TABLE `emailcms` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text,
  `status` int(1) DEFAULT '0',
  `allowedvariable` text CHARACTER SET utf8 COLLATE utf8_bin,
  `usetemplate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `imageid` bigint(20) UNSIGNED NOT NULL,
  `imagecaption` varchar(255) DEFAULT NULL,
  `imagefile` varchar(255) NOT NULL,
  `thumbnailfile` varchar(255) DEFAULT NULL,
  `linkerid` bigint(20) NOT NULL,
  `linkertype` varchar(75) NOT NULL,
  `isdefault` int(11) DEFAULT '0',
  `status` int(11) DEFAULT '0',
  `adddate` datetime DEFAULT NULL,
  `updatedate` datetime DEFAULT NULL,
  `tag` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `logid` bigint(20) UNSIGNED NOT NULL,
  `eventdate` datetime NOT NULL,
  `eventlevel` varchar(30) NOT NULL,
  `message` varchar(100) NOT NULL,
  `details` text,
  `debugtrace` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`documentid`);

--
-- Indexes for table `emailcms`
--
ALTER TABLE `emailcms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`imageid`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`logid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `documentid` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emailcms`
--
ALTER TABLE `emailcms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `imageid` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `logid` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
