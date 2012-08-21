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
 * Data Access Object for `event_invite` table.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.bol
 * @since 1.0
 */
class EVENT_BOL_EventInviteDao extends OW_BaseDao
{
    const USER_ID = 'userId';
    const INVITER_ID = 'inviterId';
    const TIME_STAMP = 'timeStamp';
    const EVENT_ID = 'eventId';

    /**
     * Singleton instance.
     *
     * @var EVENT_BOL_EventInviteDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return EVENT_BOL_EventInviteDao
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
        return 'EVENT_BOL_EventInvite';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'event_invite';
    }

    /**
     * @param integer $eventId
     * @param integer $userId
     * @return EVENT_BOL_EventInvite
     */
    public function findObjectByUserIdAndEventId( $eventId, $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int) $eventId);
        $example->andFieldEqual(self::USER_ID, (int) $userId);

        return $this->findObjectByExample($example);
    }

    /**
     * @param integer $eventId
     * @param integer $userId
     */
    public function hideInvitationByUserId( $userId )
    {
        $query = "UPDATE `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` SET `displayInvitation` = false 
            WHERE `" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId AND `displayInvitation` = true ";

        return $this->dbo->update($query, array('userId' => (int) $userId));
    }

    /**
     * @param integer $eventId
     */
    public function deleteByEventId( $eventId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int) $eventId);

        $this->deleteByExample($example);
    }

    /**
     * @param integer $eventId
     * @param integer $userId
     */
    public function deleteByUserIdAndEventId( $eventId, $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int)$eventId);
        $example->andFieldEqual(self::USER_ID, (int)$userId);

        $this->deleteByExample($example);
    }

    /**
     * @param integer $eventId
     */
    public function findInviteListByEventId( $eventId)
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int)$eventId);

        return $this->findListByExample($example);
    }

    /**
     * @param integer $eventId
     */
    public function findUserListForInvite( $eventId, $first, $count, $friendList = array() )
    {

        $userDao = BOL_UserDao::getInstance();
        $eventDao = EVENT_BOL_EventDao::getInstance();
        $eventUserDao = EVENT_BOL_EventUserDao::getInstance();

        $where = "";
        if ( !empty($friendList) )
        {
            $where = " AND `u`.id IN ( " . $this->dbo->mergeInClause($friendList) . " ) ";
        }

        $query = "SELECT `u`.`id`
    		FROM `{$userDao->getTableName()}` as `u`
            LEFT JOIN `" . $eventDao->getTableName() . "` as `e`
    			ON( `u`.`id` = `e`.`userId` AND e.id = :event )
            LEFT JOIN `" . $this->getTableName() . "` as `ei`
    			ON( `u`.`id` = `ei`.`userId` AND `ei`.eventId = :event )

            LEFT JOIN `" . $eventUserDao->getTableName() . "` as `eu`
    			ON( `u`.`id` = `eu`.`userId` AND `eu`.eventId = :event )

    		LEFT JOIN `" . BOL_UserSuspendDao::getInstance()->getTableName() . "` as `s`
    			ON( `u`.`id` = `s`.`userId` )

    		LEFT JOIN `" . BOL_UserApproveDao::getInstance()->getTableName() . "` as `d`
    			ON( `u`.`id` = `d`.`userId` )

    		WHERE `e`.`id` IS NULL AND `ei`.`id` IS NULL AND `s`.`id` IS NULL AND `d`.`id` IS NULL AND `eu`.`id` IS NULL ". $where ."
    		ORDER BY `u`.`activityStamp` DESC
    		LIMIT :first, :count ";

        return $this->dbo->queryForColumnList($query, array('event' => $eventId, 'first' => $first, 'count' => $count));
    }
}
