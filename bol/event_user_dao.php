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
class EVENT_BOL_EventUserDao extends OW_BaseDao
{
    const EVENT_ID = 'eventId';
    const USER_ID = 'userId';
    const TIME_STAMP = 'timeStamp';
    const STATUS = 'status';

    const VALUE_STATUS_YES = 1;
    const VALUE_STATUS_MAYBE = 2;
    const VALUE_STATUS_NO = 3;

    /**
     * Singleton instance.
     *
     * @var EVENT_BOL_EventUserDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return EVENT_BOL_EventUserDao
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
        return 'EVENT_BOL_EventUser';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'event_user';
    }

    public function deleteByEventId( $id )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int) $id);

        $this->deleteByExample($example);
    }

    public function findListByEventIdAndStatus( $eventId, $status, $first, $count )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int) $eventId);
        $example->andFieldEqual(self::STATUS, (int) $status);
        $example->setLimitClause($first, $count);

        return $this->findListByExample($example);
    }

    public function findCountByEventIdAndStatus( $eventId, $status )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int) $eventId);
        $example->andFieldEqual(self::STATUS, (int) $status);

        return $this->countByExample($example);
    }

    /**
     * @param integer $eventId
     * @param integer $userId
     * @return EVENT_BOL_EventUser
     */
    public function findObjectByEventIdAndUserId( $eventId, $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::EVENT_ID, (int) $eventId);
        $example->andFieldEqual(self::USER_ID, (int) $userId);

        return $this->findObjectByExample($example);
    }

    /**
     * @param integer $userId
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findByUserId( $userId, $first, $count )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, (int) $userId);
        $example->setLimitClause($first, $count);

        return $this->findListByExample($example);
    }
}
