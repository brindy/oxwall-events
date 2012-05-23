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
class EVENT_CMP_UpcomingEvents extends BASE_CLASS_Widget
{

    /**
     * @return Constructor.
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramsObj )
    {
        parent::__construct();

        $params = $paramsObj->customParamList;

        $eventService = EVENT_BOL_EventService::getInstance();
        $events = $eventService->findPublicEvents(null, $params['events_count']);
        $count = $eventService->findPublicEventsCount();

        if ( ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'add_event') ) && $count == 0 )
        {
            $this->setVisible(false);
            return;
        }

        $this->assign('events', $eventService->getListingDataWithToolbar($events));
        $this->assign('no_content_message', OW::getLanguage()->text('event', 'no_index_events_label', array('url' => OW::getRouter()->urlForRoute('event.add'))));

        if ( $eventService->findPublicEventsCount() > $params['events_count'] )
        {
            $toolbarArray = array(array('href' => OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'latest')), 'label' => OW::getLanguage()->text('event', 'view_all_label')));
            $this->assign('toolbar', $toolbarArray);
        }
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
            self::SETTING_TITLE => OW::getLanguage()->text('event', 'up_events_widget_block_cap_label'),
            self::SETTING_WRAP_IN_BOX => false,
            self::SETTING_ICON => self::ICON_CALENDAR
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}