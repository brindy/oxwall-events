<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

OW::getDbo()->query("DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "event_invite` ");
OW::getDbo()->query("DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "event_item` ");
OW::getDbo()->query("DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "event_user` ");

OW::getDbo()->query("
  CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "event_invite` (
  `id` int(11) NOT NULL auto_increment,
  `eventId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `inviterId` int(11) NOT NULL,
  `displayInvitation` BOOL NOT NULL DEFAULT '1',
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `inviteUnique` (`userId`,`inviterId`,`eventId`),
  KEY `userId` (`userId`),
  KEY `inviterId` (`inviterId`),
  KEY `eventId` (`eventId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

OW::getDbo()->query("
   CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "event_item` (
  `id` int(11) NOT NULL auto_increment,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `location` text NOT NULL,
  `createTimeStamp` int(11) NOT NULL,
  `startTimeStamp` int(11) NOT NULL,
  `endTimeStamp` int(11) default NULL,
  `userId` int(11) NOT NULL,
  `whoCanView` tinyint(4) NOT NULL,
  `whoCanInvite` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `image` VARCHAR(32) default NULL,
  `endDateFlag` BOOL NOT NULL DEFAULT '0',
  `startTimeDisabled` BOOL NOT NULL DEFAULT '0',
  `endTimeDisabled` BOOL NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

OW::getDbo()->query("
   CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "event_user` (
  `id` int(11) NOT NULL auto_increment,
  `eventId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `eventUser` (`eventId`,`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

$authorization = OW::getAuthorization();
$groupName = 'event';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'add_event');
$authorization->addAction($groupName, 'view_event', true);
$authorization->addAction($groupName, 'add_comment');

OW::getLanguage()->importPluginLangs(OW::getPluginManager()->getPlugin('event')->getRootDir() . 'langs.zip', 'event');