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
 * Data Transfer Object for `event_item` table.
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.bol
 * @since 1.0
 */
class EVENT_BOL_Event extends OW_Entity
{
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $location;
    /**
     * @var string
     */
    public $description;
    /**
     * @var integer
     */
    public $createTimeStamp;
    /**
     * @var integer
     */
    public $startTimeStamp;
    /**
     * @var integer
     */
    public $endTimeStamp;
    /**
     * @var integer
     */
    public $userId;
    /**
     * @var integer
     */
    public $whoCanView;
    /**
     * @var integer
     */
    public $whoCanInvite;
    /**
     * @var integer
     */
    public $status = 1;
    /**
     * @var string
     */
    public $image = null;
    /**
     * @var boolean
     */
    public $endDateFlag = false;
    /**
     * @var boolean
     */
    public $startTimeDisabled = false;
    /**
     * @var boolean
     */
    public $endTimeDisabled = false;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle( $title )
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription( $description )
    {
        $this->description = $description;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation( $location )
    {
        $this->location = $location;
    }

    public function getCreateTimeStamp()
    {
        return $this->createTimeStamp;
    }

    public function setCreateTimeStamp( $createTimeStamp )
    {
        $this->createTimeStamp = $createTimeStamp;
    }

    public function getStartTimeStamp()
    {
        return $this->startTimeStamp;
    }

    public function setStartTimeStamp( $startTimeStamp )
    {
        $this->startTimeStamp = $startTimeStamp;
    }

    public function getEndTimeStamp()
    {
        return $this->endTimeStamp;
    }

    public function setEndTimeStamp( $endTimeStamp )
    {
        $this->endTimeStamp = $endTimeStamp;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId( $userId )
    {
        $this->userId = $userId;
    }

    public function getWhoCanView()
    {
        return $this->whoCanView;
    }

    public function setWhoCanView( $whoCanView )
    {
        $this->whoCanView = $whoCanView;
    }

    public function getWhoCanInvite()
    {
        return $this->whoCanInvite;
    }

    public function setWhoCanInvite( $whoCanInvite )
    {
        $this->whoCanInvite = $whoCanInvite;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus( $status )
    {
        $this->status = $status;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage( $image )
    {
        $this->image = $image;
    }

    public function getEndDateFlag()
    {
        return $this->endDateFlag;
    }

    public function setEndDateFlag( $flag )
    {
        $this->endDateFlag = (boolean)$flag;
    }

    public function getStartTimeDisable()
    {
        return $this->startTimeDisabled;
    }

    public function setStartTimeDisable( $flag )
    {
        $this->startTimeDisabled = (boolean)$flag;
    }
    
    public function getEndTimeDisable()
    {
        return $this->endTimeDisabled;
    }

    public function setEndTimeDisable( $flag )
    {
        $this->endTimeDisabled = (boolean)$flag;
    }

}

