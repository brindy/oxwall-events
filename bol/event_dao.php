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

/**
 * Data Access Object for `event_item` table.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.bol
 * @since 1.0
 */
class EVENT_BOL_EventDao extends OW_BaseDao
{
    const TITLE = 'title';
    const LOCATION = 'location';
    const CREATE_TIME_STAMP = 'createTimeStamp';
    const START_TIME_STAMP = 'startTimeStamp';
    const END_TIME_STAMP = 'endTimeStamp';
    const USER_ID = 'userId';
    const WHO_CAN_VIEW = 'whoCanView';
    const WHO_CAN_INVITE = 'whoCanInvite';
    const STATUS = 'status';

    const VALUE_WHO_CAN_INVITE_CREATOR = 1;
    const VALUE_WHO_CAN_INVITE_PARTICIPANT = 2;
    const VALUE_WHO_CAN_VIEW_ANYBODY = 1;
    const VALUE_WHO_CAN_VIEW_INVITATION_ONLY = 2;

    /**
     * Singleton instance.
     *
     * @var EVENT_BOL_EventDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return EVENT_BOL_EventDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'EVENT_BOL_Event';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'event_item';
    }

    /**
     * Returns latest public events.
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findPublicEvents( $first, $count, $past = false )
    {
        $where = " `" . self::WHO_CAN_VIEW . "` = :wcv ";
        $params = array('wcv' => self::VALUE_WHO_CAN_VIEW_ANYBODY, 'startTime' => time(), 'endTime' => time(), 'first' => (int) $first, 'count' => (int) $count);

        if ( OW::getUser()->isAuthorized('event') )
        {
            $params = array('startTime' => time(), 'endTime' => time(), 'first' => (int) $first, 'count' => (int) $count);
            $where = " 1 ";
        }

        if ( $past )
        {
            $query = "SELECT * FROM `" . $this->getTableName() . "` WHERE " . $where . "
                AND " . $this->getTimeClause(true) . " ORDER BY `startTimeStamp` DESC LIMIT :first, :count";
        }
        else
        {
            $query = "SELECT * FROM `" . $this->getTableName() . "` WHERE " . $where . "
                AND " . $this->getTimeClause() . " ORDER BY `startTimeStamp` LIMIT :first, :count";
        }

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(),
            $params);
    }

    /**
     * @return integer
     */
    public function findPublicEventsCount( $past = false )
    {
        if ( $past )
        {
            $query = "SELECT COUNT(*) FROM `" . $this->getTableName() . "` WHERE `" . self::WHO_CAN_VIEW . "` = :wcv AND " . $this->getTimeClause(true);
        }
        else
        {
            $query = "SELECT COUNT(*) FROM `" . $this->getTableName() . "` WHERE `" . self::WHO_CAN_VIEW . "` = :wcv AND " . $this->getTimeClause();
        }

        return $this->dbo->queryForColumn($query, array('wcv' => self::VALUE_WHO_CAN_VIEW_ANYBODY, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * Returns events with user status.
     *
     * @param integer $userId
     * @param integer $userStatus
     * @param integer $first
     * @param inetger $count
     * @return array
     */
    public function findUserEventsWithStatus( $userId, $userStatus, $first, $count )
    {
        $query = "SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e') . "
            ORDER BY `" . self::START_TIME_STAMP . "` LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => $userId, 'status' => $userStatus, 'first' => $first, 'count' => $count, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * @param integer $userId
     * @param integer $status
     * @return integer
     */
    public function findUserEventsCountWithStatus( $userId, $status )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e');

        return (int) $this->dbo->queryForColumn($query, array('userId' => $userId, 'status' => $status, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * Returns events with user status.
     *
     * @param integer $userId
     * @param integer $userStatus
     * @param integer $first
     * @param inetger $count
     * @return array
     */
    public function findPublicUserEventsWithStatus( $userId, $userStatus, $first, $count )
    {
        $query = "SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e') . " AND `e`.`" . self::WHO_CAN_VIEW . "` = " . self::VALUE_WHO_CAN_VIEW_ANYBODY . "
            ORDER BY `" . self::START_TIME_STAMP . "` LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => $userId, 'status' => $userStatus, 'first' => $first, 'count' => $count, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * @param integer $userId
     * @param integer $status
     * @return integer
     */
    public function findPublicUserEventsCountWithStatus( $userId, $status )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e') . " AND `e`.`" . self::WHO_CAN_VIEW . "` = " . self::VALUE_WHO_CAN_VIEW_ANYBODY . "";

        return (int) $this->dbo->queryForColumn($query, array('userId' => $userId, 'status' => $status, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * Returns user created events.
     *
     * @param integer $userId
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findUserCreatedEvents( $userId, $first, $count )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, $userId);
        $example->setOrder(self::START_TIME_STAMP );
        $example->andFieldGreaterThan(self::START_TIME_STAMP, time());
        $example->setLimitClause($first, $count);

        return $this->findListByExample($example);
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findUserCretedEventsCount( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, $userId);
        $example->andFieldGreaterThan(self::START_TIME_STAMP, time());

        return $this->countByExample($example);
    }

    /**
     * @param integer $userId
     * @return array<EVENT_BOL_Event>
     */
    public function findUserInvitedEvents( $userId, $first, $count )
    {
        $query = "SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` AS `ei` ON ( `e`.`id` = `ei`.`" . EVENT_BOL_EventInviteDao::EVENT_ID . "` )
            WHERE `ei`.`" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId AND " . $this->getTimeClause(false, 'e') . "
            GROUP BY `e`.`id` LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => (int) $userId, 'first' => (int) $first, 'count' => (int) $count, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findUserInvitedEventsCount( $userId )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` AS `ei` ON ( `e`.`id` = `ei`.`" . EVENT_BOL_EventInviteDao::EVENT_ID . "` )
            WHERE `ei`.`" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId AND " . $this->getTimeClause(false, 'e') . " GROUP BY `e`.`id`";

        return $this->dbo->queryForColumn($query, array('userId' => (int) $userId, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findDispaledUserInvitationCount( $userId )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` AS `ei` ON ( `e`.`id` = `ei`.`" . EVENT_BOL_EventInviteDao::EVENT_ID . "` )
            WHERE `ei`.`" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId AND `ei`.`displayInvitation` = true AND " . $this->getTimeClause(false, 'e') . " GROUP BY `e`.`id`";

        return $this->dbo->queryForColumn($query, array('userId' => (int) $userId, 'startTime' => time(), 'endTime' => time()));
    }


    /**
     * @param integer $userId
     * @return array<EVENT_BOL_Event>
     */
    public function findAllUserEvents( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, (int) $userId);

        return $this->findListByExample($example);
    }

    private function getTimeClause( $past = false, $alias = null )
    {
        if ( $past )
        {
            return "( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::START_TIME_STAMP . "` <= :startTime AND ( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` IS NULL OR " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` <= :endTime ) )";
        }

        return "( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::START_TIME_STAMP . "` > :startTime OR ( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` IS NOT NULL AND " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` > :endTime ) )";
    }

    
}
