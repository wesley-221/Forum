-- phpMyAdmin SQL Dump
-- version 4.4.12
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Gegenereerd op: 29 jun 2016 om 14:01
-- Serverversie: 5.6.25
-- PHP-versie: 5.6.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `forum`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum`
--

CREATE TABLE IF NOT EXISTS `forum` (
  `forumid` int(11) NOT NULL,
  `forumname` varchar(50) NOT NULL,
  `permission_required` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Gegevens worden geëxporteerd voor tabel `forum`
--

INSERT INTO `forum` (`forumid`, `forumname`, `permission_required`) VALUES
(1, 'General', 0),
(2, 'Help', 0),
(3, 'Other', 0),
(4, 'Non English', 0),
(5, 'Admin board', 3);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `friends`
--

CREATE TABLE IF NOT EXISTS `friends` (
  `userid` int(11) NOT NULL,
  `friend_userid` int(11) NOT NULL,
  `denied` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `messageid` int(11) NOT NULL,
  `senderid` int(11) NOT NULL,
  `receiverid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `message_read` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `permissions`
--

CREATE TABLE IF NOT EXISTS `permissions` (
  `permission` int(5) NOT NULL DEFAULT '0',
  `permission_name` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Gegevens worden geëxporteerd voor tabel `permissions`
--

INSERT INTO `permissions` (`permission`, `permission_name`) VALUES
(0, 'User'),
(1, 'Board Moderator'),
(2, 'Moderator'),
(3, 'Administrator');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `postid` int(11) NOT NULL,
  `threadid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `opening_post` int(11) NOT NULL DEFAULT '0',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `title` varchar(50) NOT NULL,
  `body` text NOT NULL,
  `lastedit_userid` int(11) NOT NULL DEFAULT '0',
  `lastedit_date` date NOT NULL DEFAULT '0000-00-00',
  `lastedit_time` time NOT NULL DEFAULT '00:00:00',
  `lastedit_message` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `reputation`
--

CREATE TABLE IF NOT EXISTS `reputation` (
  `postid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `message` varchar(100) NOT NULL,
  `anonymous` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `subforum`
--

CREATE TABLE IF NOT EXISTS `subforum` (
  `forumid` int(11) NOT NULL,
  `subforumid` int(11) NOT NULL,
  `subforumname` varchar(50) NOT NULL,
  `subforumdescription` varchar(200) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `threads`
--

CREATE TABLE IF NOT EXISTS `threads` (
  `threadid` int(11) NOT NULL,
  `subforumid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `lastpost` datetime NOT NULL,
  `sticky` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  `likes` int(11) NOT NULL,
  `dislikes` int(11) NOT NULL,
  `locked` int(1) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `temp_id` varchar(20) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `profilename` varchar(20) NOT NULL,
  `last_profilename` varchar(20) NOT NULL,
  `userdescription` varchar(50) NOT NULL,
  `permission` int(5) NOT NULL DEFAULT '0',
  `password` varchar(129) NOT NULL,
  `salt` varchar(50) NOT NULL,
  `register_date` date NOT NULL,
  `register_datetime` time NOT NULL,
  `born` date NOT NULL,
  `email` varchar(50) NOT NULL,
  `last_activity` date NOT NULL,
  `reputation` int(11) NOT NULL,
  `signature` varchar(500) NOT NULL,
  `cookie` varchar(128) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `forum`
--
ALTER TABLE `forum`
  ADD PRIMARY KEY (`forumid`);

--
-- Indexen voor tabel `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`userid`,`friend_userid`);

--
-- Indexen voor tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`messageid`,`senderid`,`receiverid`);

--
-- Indexen voor tabel `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission`);

--
-- Indexen voor tabel `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`postid`,`threadid`,`userid`);

--
-- Indexen voor tabel `reputation`
--
ALTER TABLE `reputation`
  ADD PRIMARY KEY (`postid`,`userid`);

--
-- Indexen voor tabel `subforum`
--
ALTER TABLE `subforum`
  ADD PRIMARY KEY (`subforumid`);

--
-- Indexen voor tabel `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`threadid`,`subforumid`,`userid`);

--
-- Indexen voor tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `forum`
--
ALTER TABLE `forum`
  MODIFY `forumid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT voor een tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `messageid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `posts`
--
ALTER TABLE `posts`
  MODIFY `postid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT voor een tabel `subforum`
--
ALTER TABLE `subforum`
  MODIFY `subforumid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT voor een tabel `threads`
--
ALTER TABLE `threads`
  MODIFY `threadid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT voor een tabel `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
