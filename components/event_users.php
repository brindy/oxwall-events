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
 * User console component class.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow.ow_system_plugins.base.components
 * @since 1.0
 */
class EVENT_CMP_EventUsers extends OW_Component
{
    private $eventService;

    /**
     * @return Constructor.
     */
    public function __construct( $eventId )
    {
        parent::__construct();

        $this->eventService = EVENT_BOL_EventService::getInstance();

        $event = $this->eventService->findEvent($eventId);

        if ( $event === null )
        {
            $this->setVisible(false);
        }

        // event users info
        $this->addUserList($event, EVENT_BOL_EventService::USER_STATUS_YES);
        $this->addUserList($event, EVENT_BOL_EventService::USER_STATUS_MAYBE);
        $this->addUserList($event, EVENT_BOL_EventService::USER_STATUS_NO);
        $this->assign('userLists', $this->userLists);
        $this->addComponent('userListMenu', new BASE_CMP_WidgetMenu($this->userListMenu));
    }
    private $userLists;
    private $userListMenu;

    private function addUserList( EVENT_BOL_Event $event, $status )
    {
        $configs = $this->eventService->getConfigs();

        $language = OW::getLanguage();
        $listTypes = $this->eventService->getUserListsArray();
        $serviceConfigs = $this->eventService->getConfigs();
        $userList = $this->eventService->findEventUsers($event->getId(), $status, null, $configs[EVENT_BOL_EventService::CONF_EVENT_USERS_COUNT]);
        $usersCount = $this->eventService->findEventUsersCount($event->getId(), $status);

        $idList = array();

        /* @var $eventUser EVENT_BOL_EventUser */
        foreach ( $userList as $eventUser )
        {
            $idList[] = $eventUser->getUserId();
        }

        $usersCmp = new BASE_CMP_AvatarUserList($idList);

        $linkId = UTIL_HtmlTag::generateAutoId('link');
        $contId = UTIL_HtmlTag::generateAutoId('cont');

        $this->userLists[] = array(
            'contId' => $contId,
            'cmp' => $usersCmp->render(),
            'bottomLinkEnable' => ($usersCount > $serviceConfigs[EVENT_BOL_EventService::CONF_EVENT_USERS_COUNT]),
            'toolbarArray' => array(
                array(
                    'label' => $language->text('event', 'avatar_user_list_bottom_link_label', array('count' => $usersCount)),
                    'href' => OW::getRouter()->urlForRoute('event.user_list', array('eventId' => $event->getId(), 'list' => $listTypes[(int) $status]))
                )
            )
        );

        $this->userListMenu[] = array(
            'label' => $language->text('event', 'avatar_user_list_link_label_' . $status),
            'id' => $linkId,
            'contId' => $contId,
            'active' => ( sizeof($this->userListMenu) < 1 ? true : false )
        );
    }
}