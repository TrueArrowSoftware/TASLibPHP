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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `displayorder` int(11) NOT NULL DEFAULT '0',
  `tag` text,
  `settings` text,
  `status` int(11) DEFAULT '0',
  `adddate` datetime DEFAULT NULL,
  `updatedate` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
COMMIT;
--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `logid` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

