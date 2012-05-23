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
 * @package ow.ow_plugins.event.components
 * @since 1.0
 */
class EVENT_CMP_ProfilePageWidget extends BASE_CLASS_Widget
{

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramsObj )
    {
        parent::__construct();

        $params = $paramsObj->customParamList;
        $addParams = $paramsObj->additionalParamList;

        if ( empty($addParams['entityId']) )
        {
            $this->setVisible(false);
            return;
        }
        else
        {
            $userId = $addParams['entityId'];
        }

        $eventParams =  array(
                'action' => 'event_view_attend_events',
                'ownerId' => $userId,
                'viewerId' => OW::getUser()->getId()
            );
        
        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch( RedirectException $e )
        {
            $this->setVisible(false);
            return;
        }
        
        $language = OW::getLanguage();
        $eventService = EVENT_BOL_EventService::getInstance();

        $userEvents = $eventService->findUserParticipatedPublicEvents($userId, null, $params['events_count']);
        
        
        if ( empty($userEvents) )
        {
            $this->setVisible(false);
            return;
        }

        $this->assign('my_events', $eventService->getListingDataWithToolbar($userEvents));

        $toolbarArray = array();
        
        if ( $eventService->findUserParticipatedPublicEventsCount($userId) > $params['events_count'] )
        {
            $url = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'user-participated-events')), array('userId' => $userId));
            $toolbarArray = array(array('href' => $url, 'label' => $language->text('event', 'view_all_label')));
        }

        $this->assign('toolbars', $toolbarArray);
    }

    public static function getSettingList()
    {
        $eventConfigs = EVENT_BOL_EventService::getInstance()->getConfigs();
        $settingList = array();
        $settingList['events_count'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('event', 'cmp_widget_events_count'),
            'optionList' => $eventConfigs[EVENT_BOL_EventService::CONF_WIDGET_EVENTS_COUNT_OPTION_LIST],
            'value' => $eventConfigs[EVENT_BOL_EventService::CONF_WIDGET_EVENTS_COUNT]
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_TITLE => OW::getLanguage()->text('event', 'profile_events_widget_block_cap_label'),
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_ICON => self::ICON_CALENDAR
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}