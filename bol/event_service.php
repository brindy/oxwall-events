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
 *  Events Service.
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.bol
 * @since 1.0
 */
final class EVENT_BOL_EventService
{
    const USER_STATUS_YES = EVENT_BOL_EventUserDao::VALUE_STATUS_YES;
    const USER_STATUS_MAYBE = EVENT_BOL_EventUserDao::VALUE_STATUS_MAYBE;
    const USER_STATUS_NO = EVENT_BOL_EventUserDao::VALUE_STATUS_NO;

    const CAN_INVITE_PARTICIPANT = EVENT_BOL_EventDao::VALUE_WHO_CAN_INVITE_PARTICIPANT;
    const CAN_INVITE_CREATOR = EVENT_BOL_EventDao::VALUE_WHO_CAN_INVITE_CREATOR;

    const CAN_VIEW_ANYBODY = EVENT_BOL_EventDao::VALUE_WHO_CAN_VIEW_ANYBODY;
    const CAN_VIEW_INVITATION_ONLY = EVENT_BOL_EventDao::VALUE_WHO_CAN_VIEW_INVITATION_ONLY;

    const CONF_EVENT_USERS_COUNT = 'event_users_count';
    const CONF_EVENT_USERS_COUNT_ON_PAGE = 'event_users_count_on_page';
    const CONF_EVENTS_COUNT_ON_PAGE = 'events_count_on_page';
    const CONF_WIDGET_EVENTS_COUNT = 'events_widget_count';
    const CONF_WIDGET_EVENTS_COUNT_OPTION_LIST = 'events_widget_count_select_set';
    const CONF_DASH_WIDGET_EVENTS_COUNT = 'events_dash_widget_count';

    const EVENT_AFTER_EVENT_EDIT = 'event_after_event_edit';

    /**
     * @var array
     */
    private $configs = array();
    /**
     * @var EVENT_BOL_EventDao
     */
    private $eventDao;
    /**
     * @var EVENT_BOL_EventUserDao
     */
    private $eventUserDao;
    /**
     * @var EVENT_BOL_EventInviteDao
     */
    private $eventInviteDao;
    /**
     * Singleton instance.
     *
     * @var EVENT_BOL_EventService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return EVENT_BOL_EventService
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
    private function __construct()
    {
        $this->eventDao = EVENT_BOL_EventDao::getInstance();
        $this->eventUserDao = EVENT_BOL_EventUserDao::getInstance();
        $this->eventInviteDao = EVENT_BOL_EventInviteDao::getInstance();

        $this->configs[self::CONF_EVENT_USERS_COUNT] = 10;
        $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE] = 15;
        $this->configs[self::CONF_DASH_WIDGET_EVENTS_COUNT] = 3;
        $this->configs[self::CONF_WIDGET_EVENTS_COUNT] = 3;
        $this->configs[self::CONF_EVENT_USERS_COUNT_ON_PAGE] = 30;
        $this->configs[self::CONF_WIDGET_EVENTS_COUNT_OPTION_LIST] = array(3 => 3, 5 => 5, 10 => 10, 15 => 15, 20 => 20);
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * Saves event dto.
     *
     * @param EVENT_BOL_Event $event
     */
    public function saveEvent( EVENT_BOL_Event $event )
    {
        $this->eventDao->save($event);
    }

    /**
     * Makes and saves event standard image and icon.
     *
     * @param string $imagePath
     * @param integer $imageId
     */
    public function saveEventImage( $imagePath, $imageId )
    {
        $storage = OW::getStorage();
        
        if ( $storage->fileExists($this->generateImagePath($imageId)) )
        {
            $storage->removeFile($this->generateImagePath($imageId));
            $storage->removeFile($this->generateImagePath($imageId, false));
        }

        $pluginfilesDir = Ow::getPluginManager()->getPlugin('event')->getPluginFilesDir();

        $tmpImgPath = $pluginfilesDir . 'img_' .uniqid() . '.jpg';
        $tmpIconPath = $pluginfilesDir . 'icon_' . uniqid() . '.jpg';

        $image = new UTIL_Image($imagePath);
        $image->resizeImage(295, null)->saveImage($tmpImgPath)
            ->resizeImage(100, 100, true)->saveImage($tmpIconPath);

        unlink($imagePath);

        $storage->copyFile($tmpIconPath, $this->generateImagePath($imageId));
        $storage->copyFile($tmpImgPath,$this->generateImagePath($imageId, false));

        unlink($tmpImgPath);
        unlink($tmpIconPath);
    }

    /**
     * Deletes event.
     *
     * @param integer $eventId
     */
    public function deleteEvent( $eventId )
    {
        $event = $this->eventDao->findById((int) $eventId);

        if ( $event === null )
        {
            return;
        }

        $storage = OW::getStorage();
        $storage->removeFile($this->generateImagePath($event->image));
        $storage->removeFile($this->generateImagePath($event->image, false));

        $this->eventUserDao->deleteByEventId($event->getId());
        $this->eventDao->deleteById($event->getId());
        $this->eventInviteDao->deleteByEventId($event->getId());
    }

    /**
     * Returns event image and icon path.
     *
     * @param integer $imageId
     * @param boolean $icon
     * @return string
     */
    public function generateImagePath( $imageId, $icon = true )
    {
        $imagesDir = OW::getPluginManager()->getPlugin('event')->getUserFilesDir();
        return $imagesDir . ( $icon ? 'event_icon_' : 'event_image_' ) . $imageId . '.jpg';
    }

    /**
     * Returns event image and icon url.
     * 
     * @param integer $imageId
     * @param boolean $icon
     * @return string
     */
    public function generateImageUrl( $imageId, $icon = true )
    {
        return OW::getStorage()->getFileUrl($this->generateImagePath($imageId, $icon));
    }

    /**
     * Returns default event image url.
     */
    public function generateDefaultImageUrl()
    {
        return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'no-picture.png';
    }

    /**
     * Finds event by id.
     *
     * @param integer $id
     * @return EVENT_BOL_Event
     */
    public function findEvent( $id )
    {
        return $this->eventDao->findById((int) $id);
    }

    public function findEventsByDate( $date )
    {
	return $this->eventDao->findByDate($date);
    }

    /**
     * Returns event users with provided status.
     *
     * @param integer $eventId
     * @param integer $status
     * @return array<EVENT_BOL_EventUser>
     */
    public function findEventUsers( $eventId, $status, $page, $usersCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $usersCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENT_USERS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventUserDao->findListByEventIdAndStatus($eventId, $status, $first, $count);
    }

    /**
     * Returns users count for provided event and status.
     *
     * @param integer $eventId
     * @param integer $status
     * @return integer
     */
    public function findEventUsersCount( $eventId, $status )
    {
        return (int) $this->eventUserDao->findCountByEventIdAndStatus($eventId, $status);
    }

    /**
     * Saves event user objects.
     *
     * @param EVENT_BOL_EventUser $eventUser
     */
    public function saveEventUser( EVENT_BOL_EventUser $eventUser )
    {
        $this->eventUserDao->save($eventUser);
    }

    /**
     * Finds event-user object.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return EVENT_BOL_EventUser
     */
    public function findEventUser( $eventId, $userId )
    {
        return $this->eventUserDao->findObjectByEventIdAndUserId($eventId, $userId);
    }

    /**
     * Checks if user can view and join event.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return boolean
     */
    public function canUserView( $eventId, $userId )
    {
        $event = $this->eventDao->findById($eventId);
        /* @var $event EVENT_BOL_Event */
        if ( $event === null )
        {
            return false;
        }

        $userEvent = $this->eventUserDao->findObjectByEventIdAndUserId($eventId, $userId);

        if ( $event->getWhoCanView() === self::CAN_VIEW_INVITATION_ONLY && $userEvent === null )
        {
            return false;
        }

        return true;
    }

    /**
     * Checks if user can invite to event.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return boolean
     */
    public function canUserInvite( $eventId, $userId )
    {
        $event = $this->eventDao->findById($eventId);
        /* @var $event EVENT_BOL_Event */
        if ( $event === null || ( $event->getWhoCanInvite() == self::CAN_INVITE_CREATOR && $userId != $event->getUserId() ) )
        {
            return false;
        }

        $userEvent = $this->eventUserDao->findObjectByEventIdAndUserId($eventId, $userId);

        if ( $userEvent === null || $userEvent->getStatus() != self::USER_STATUS_YES )
        {
            return false;
        }

        return true;
    }

    /**
     * Returns latest events list.
     *
     * @param integer $page
     * @return array<EVENT_BOL_Event>
     */
    public function findPublicEvents( $page, $eventsCount = null, $past = false )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findPublicEvents($first, $count, $past);
    }

    /**
     * Returns latest events count.
     *
     * @return integer
     */
    public function findPublicEventsCount( $past = false )
    {
        return $this->eventDao->findPublicEventsCount($past);
    }

    /**
     * Invites user to event.
     *
     * @param integer $eventId
     * @param integer $userId
     * @param integer $inviterId
     *
     * @return EVENT_BOL_EventInvite
     */
    public function inviteUser( $eventId, $userId, $inviterId )
    {
        $event = $this->findEvent($eventId);

        if ( $event === null )
        {
            return false;
        }

        $eventInvite = new EVENT_BOL_EventInvite();
        $eventInvite->setEventId($eventId);
        $eventInvite->setInviterId($inviterId);
        $eventInvite->setUserId($userId);
        $eventInvite->setTimeStamp(time());
        $eventInvite->setDisplayInvitation(true);

        $this->eventInviteDao->save($eventInvite);

        return $eventInvite;
    }

    /**
     * Returns event invitation for user.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return EVENT_BOL_EventInvite
     */
    public function findEventInvite( $eventId, $userId )
    {
        return $this->eventInviteDao->findObjectByUserIdAndEventId($eventId, $userId);
    }

    /**
     * Finds events for user
     *
     * @param integer $userId
     * @return array
     */
    public function findUserEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserCreatedEvents($userId, $first, $count);
    }

    /**
     * Returns user created events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUsersEventsCount( $userId )
    {
        return $this->eventDao->findUserCretedEventsCount($userId);
    }

    /**
     * Returns list of user participating events.
     *
     * @param integer $userId
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function findUserParticipatedEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserEventsWithStatus($userId, self::USER_STATUS_YES, $first, $count);
    }

    /**
     * Returns user participated events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUserParticipatedEventsCount( $userId )
    {
        return $this->eventDao->findUserEventsCountWithStatus($userId, self::USER_STATUS_YES);
    }

    /**
     * Returns list of user participating public events.
     *
     * @param integer $userId
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function findUserParticipatedPublicEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findPublicUserEventsWithStatus($userId, self::USER_STATUS_YES, $first, $count);
    }

    /**
     * Returns user participated public events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUserParticipatedPublicEventsCount( $userId )
    {
        return $this->eventDao->findPublicUserEventsCountWithStatus($userId, self::USER_STATUS_YES);
    }

    /**
     * Returns user participated public events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function hideInvitationByUserId( $userId )
    {
        return $this->eventInviteDao->hideInvitationByUserId($userId);
    }

    /**
     * Prepares data for ipc listing.
     *
     * @param array<EVENT_BOL_Event> $events
     * @return array
     */
    public function getListingData( array $events )
    {
        $resultArray = array();

        /* @var $eventItem EVENT_BOL_Event */
        foreach ( $events as $eventItem )
        {
            $title = UTIL_String::truncate(strip_tags($eventItem->getTitle()), 80, "...") ;
            $content = UTIL_String::truncate(strip_tags($eventItem->getDescription()), 100, "...");

            $resultArray[$eventItem->getId()] = array(
                'content' => $content,
                'title' => $title,
                'eventUrl' => OW::getRouter()->urlForRoute('event.view', array('eventId' => $eventItem->getId())),
                'imageSrc' => ( $eventItem->getImage() ? $this->generateImageUrl($eventItem->getImage(), true) : $this->generateDefaultImageUrl() ),
                'imageTitle' => $title
            );
        }

        return $resultArray;
    }

    /**
     * Prepares data for ipc listing with toolbar.
     *
     * @param array<EVENT_BOL_Event> $events
     * @return array
     */
    public function getListingDataWithToolbar( array $events, $toolbarList = array() )
    {
        $resultArray = $this->getListingData($events);
        $userService = BOL_UserService::getInstance();

        $idArray = array();

        /* @var $event EVENT_BOL_Event */
        foreach ( $events as $event )
        {
            $idArray[] = $event->getUserId();
        }

        $usernames = $userService->getDisplayNamesForList($idArray);
        $urls = $userService->getUserUrlsForList($idArray);

        $language = OW::getLanguage();
        /* @var $eventItem EVENT_BOL_Event */
        foreach ( $events as $eventItem )
        {
            $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => $usernames[$eventItem->getUserId()], 'href' => $urls[$eventItem->getUserId()], 'class' => 'ow_icon_control ow_ic_user');
            $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => UTIL_DateTime::formatSimpleDate($eventItem->getStartTimeStamp(),$eventItem->getStartTimeDisable()));

            if ( !empty($toolbarList[$eventItem->getId()]) )
            {
                $resultArray[$eventItem->getId()]['toolbar'] = array_merge($resultArray[$eventItem->getId()]['toolbar'], $toolbarList[$eventItem->getId()]);
            }
            
            /* if( !empty($isInviteList) )
            {
                $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => $language->text('event', 'ignore_request'),'href' => 'event.invite_acept');
                $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => $language->text('event', 'accept_request'),'href' => 'event.invite_decline');
            }*/
        }
        //printVar($resultArray);
        return $resultArray;
    }

    public function getUserListsArray()
    {
        return array(
            self::USER_STATUS_YES => 'yes',
            self::USER_STATUS_MAYBE => 'maybe',
            self::USER_STATUS_NO => 'no'
        );
    }

    /**
     * Returns user invited events.
     *
     * @param integer $userId
     * @param integer $page
     * @param integer $eventsCount
     * @return array<EVENT_BOL_Event>
     */
    public function findUserInvitedEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserInvitedEvents($userId, $first, $count);
    }

    /**
     * Returns user invited events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUserInvitedEventsCount( $userId )
    {
        return $this->eventDao->findUserInvitedEventsCount($userId);
    }

    /**
     * Returns dispalyed user invited events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findDispaledUserInvitationCount( $userId )
    {
        return $this->eventDao->findDispaledUserInvitationCount($userId);
    }

    /**
     * Deletes all event invites for provided user.
     *
     * @param integer $eventId
     * @param integer $userId
     */
    public function deleteUserEventInvites( $eventId, $userId )
    {
        $this->eventInviteDao->deleteByUserIdAndEventId($eventId, $userId);
    }

    /**
     * Deletes all user events.
     *
     * @param integer $userId
     */
    public function deleteUserEvents( $userId )
    {
        $events = $this->eventDao->findAllUserEvents($userId);

        /* @var $event EVENT_BOL_Event */
        foreach ( $events as $event )
        {
            $this->deleteEvent($event->getId());
        }
    }

    /**
     * returns invited userId list.
     *
     * @param integer $eventId
     */
    public function findInviteUserListByEventId( $eventId )
    {
        $inviteList = $this->eventInviteDao->findInviteListByEventId($eventId);

        $userList = array();

        foreach ( $inviteList as $invite )
        {
            $userList[] = $invite->userId;
        }

        return $userList;
    }

    public function findUserListForInvite( $eventId, $first, $count, $friendList = array() )
    {
         return $this->eventInviteDao->findUserListForInvite($eventId, $first, $count, $friendList );
    }
}
