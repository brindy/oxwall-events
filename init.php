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
$plugin = OW::getPluginManager()->getPlugin('event');
$router = OW::getRouter();
$router->addRoute(new OW_Route('event.add', 'event/add', 'EVENT_CTRL_Base', 'add'));
$router->addRoute(new OW_Route('event.edit', 'event/edit/:eventId', 'EVENT_CTRL_Base', 'edit'));
$router->addRoute(new OW_Route('event.delete', 'event/delete/:eventId', 'EVENT_CTRL_Base', 'delete'));
$router->addRoute(new OW_Route('event.view', 'event/:eventId', 'EVENT_CTRL_Base', 'view'));
$router->addRoute(new OW_Route('event.main_menu_route', 'events', 'EVENT_CTRL_Base', 'eventsList', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
$router->addRoute(new OW_Route('event.view_event_list', 'events/:list', 'EVENT_CTRL_Base', 'eventsList'));
$router->addRoute(new OW_Route('event.main_user_list', 'event/:eventId/users', 'EVENT_CTRL_Base', 'eventUserLists', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'yes'))));
$router->addRoute(new OW_Route('event.user_list', 'event/:eventId/users/:list', 'EVENT_CTRL_Base', 'eventUserLists'));
$router->addRoute(new OW_Route('event.private_event', 'event/:eventId/private', 'EVENT_CTRL_Base', 'privateEvent'));
$router->addRoute(new OW_Route('event.invite_accept', 'event/:eventId/:list/invite_accept', 'EVENT_CTRL_Base', 'inviteListAccept'));
$router->addRoute(new OW_Route('event.invite_decline', 'event/:eventId/:list/invite_decline', 'EVENT_CTRL_Base', 'inviteListDecline'));
$router->addRoute(new OW_Route('event.clone', 'event/:eventId/clone', 'EVENT_CTRL_Base', 'cloneEvent'));
$router->addRoute(new OW_Route('event.view_calendar', 'events/calendar', 'EVENT_CTRL_Base', 'calendarView'));

/**inviteListAcept
 * Add notification actions to the collection
 *
 * @param BASE_CLASS_EventCollector $e
 */
function event_on_notify_actions( BASE_CLASS_EventCollector $e )
{
    $e->add(array(
        'section' => 'event',
        'action' => 'event-invitation',
        'sectionIcon' => 'ow_ic_calendar',
        'sectionLabel' => OW::getLanguage()->text('event', 'notifications_section_label'),
        'description' => OW::getLanguage()->text('event', 'notifications_new_message'),
        'selected' => true
    ));

    $e->add(array(
        'section' => 'event',
        'sectionIcon' => 'ow_ic_files',
        'sectionLabel' => OW::getLanguage()->text('event', 'notifications_section_label'),
        'action' => 'event-add_comment',
        'description' => OW::getLanguage()->text('event', 'email_notification_comment_setting'),
        'selected' => true
    ));
}
OW::getEventManager()->bind('base.notify_actions', 'event_on_notify_actions');

/**
 * Trigger notification on user invite action
 *
 * @param OW_Event $e
 */
function event_on_user_invite( OW_Event $e )
{
    $params = $e->getParams();

    $event = new OW_Event('base.notify', array(
            'plugin' => 'event',
            'action' => 'event-invitation',
            'userId' => $params['userId'],
            'string' => OW::getLanguage()->text('event', 'email_notification_invite', array(
                'inviterUrl' => BOL_UserService::getInstance()->getUserUrl($params['inviterId']),
                'inviterName' => BOL_UserService::getInstance()->getDisplayName($params['inviterId']),
                'eventUrl' => OW::getRouter()->urlForRoute('event.view', array('eventId' => $params['eventId'])),
                'eventName' => $params['eventTitle']
            )),
            'content' => strip_tags($params['eventDesc']),
            'time' => time(),
            'url' => OW::getRouter()->urlForRoute('event.view', array('eventId' => $params['eventId'])),
            'avatar' => empty($params['imageId']) ? EVENT_BOL_EventService::getInstance()->generateImageUrl($params['imageId'], true) : null
        ));

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind('event.invite_user', 'event_on_user_invite');

/**
 * Add event entity to the newsfeed
 *
 * @param OW_Event $e
 */
function event_feed_entity_add( OW_Event $e )
{
    $params = $e->getParams();
    $data = $e->getData();

    if ( $params['entityType'] != 'event' )
    {
        return;
    }

    $eventService = EVENT_BOL_EventService::getInstance();
    $event = $eventService->findEvent($params['entityId']);

//    if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY )
//    {
//        return;
//    }

    $url = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId()));
    $thumb = $eventService->generateImageUrl($event->image, true);

    $title = UTIL_String::truncate(strip_tags($event->getTitle()), 100, "...");
    

    $data = array(
        'time' => $event->getCreateTimeStamp(),
        'ownerId' => $event->getUserId(),
        'string' => OW::getLanguage()->text('event', 'feed_add_item_label'),
        'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
            <a href="' . $url . '"><img src="' . ( $event->getImage() ? $eventService->generateImageUrl($event->getImage(), true) : $eventService->generateDefaultImageUrl() ) . '" /></a>
            </div><div class="ow_newsfeed_item_content">
            <a class="ow_newsfeed_item_title" href="' . $url . '">' . $title . '</a><div class="ow_remark ow_smallmargin">' . UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...') . '</div><div class="ow_newsfeed_action_activity event_newsfeed_activity">[ph:activity]</div></div></div>',
        'view' => array(
            'iconClass' => 'ow_ic_calendar'
        )
    );

    if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY )
    {
        $data['params']['visibility'] = 4;
    }

    $e->setData($data);
}
OW::getEventManager()->bind('feed.on_entity_add', 'event_feed_entity_add');

function event_after_event_edit( OW_Event $event )
{
    $params = $event->getParams();
    $evemtId = (int) $params['eventId'];

    $eventService = EVENT_BOL_EventService::getInstance();
    $event = $eventService->findEvent($evemtId);

    $url = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId()));
    $thumb = $eventService->generateImageUrl($event->image, true);

    $data = array(
        'time' => $event->getCreateTimeStamp(),
        'ownerId' => $event->getUserId(),
        'string' => OW::getLanguage()->text('event', 'feed_add_item_label'),
        'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
            <a href="' . $url . '"><img src="' . $thumb . '" /></a>
            </div><div class="ow_newsfeed_item_content">
            <a class="ow_newsfeed_item_title" href="' . $url . '">' . $event->getTitle() . '</a><div class="ow_remark ow_smallmargin">' . UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...') . '</div><div class="ow_newsfeed_action_activity event_newsfeed_activity">[ph:activity]</div></div></div>',
        'view' => array(
            'iconClass' => 'ow_ic_calendar'
        )
    );

    if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY )
    {
        $data['params']['visibility'] = 4;
    }

    $event = new OW_Event('feed.action', array(
        'entityType' => 'event',
        'entityId' => $evemtId,
        'pluginKey' => 'event'
    ), $data);

    OW::getEventManager()->trigger($event);
}
OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_AFTER_EVENT_EDIT, 'event_after_event_edit');

function event_elst_add_new_content_item( BASE_CLASS_EventCollector $event )
{
    if ( !OW::getUser()->isAuthorized('event', 'add_event') )
    {
        return;
    }

    $resultArray = array(
        BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_calendar',
        BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('event.add'),
        BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('event', 'add_new_link_label')
    );

    $event->add($resultArray);
}
OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, 'event_elst_add_new_content_item');

function event_ads_enabled( BASE_EventCollector $event )
{
    $event->add('event');
}
OW::getEventManager()->bind('ads.enabled_plugins', 'event_ads_enabled');

function event_plugin_is_active()
{
    return true;
}
OW::getEventManager()->bind('plugin.events', 'event_plugin_is_active');


$credits = new EVENT_CLASS_Credits();
OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

function event_add_auth_labels( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(
        array(
            'event' => array(
                'label' => $language->text('event', 'auth_group_label'),
                'actions' => array(
                    'add_event' => $language->text('event', 'auth_action_label_add_event'),
                    'view_event' => $language->text('event', 'auth_action_label_view_event'),
                    'add_comment' => $language->text('event', 'auth_action_label_add_comment')
                )
            )
        )
    );
}
OW::getEventManager()->bind('admin.add_auth_labels', 'event_add_auth_labels');

function event_add_console_item( BASE_EventCollector $e )
{
    if ( !OW::getUser()->isAuthenticated() )
    {
        return;
    }

    // fix to run the script update
    if( OW::getPluginManager()->getPlugin('event')->getDto()->build < 4894 )
    {
        return;
    }

    $count = EVENT_BOL_EventService::getInstance()->findDispaledUserInvitationCount(OW::getUser()->getId());

    if ( $count > 0 )
    {
        $ntfBlockId = UTIL_HtmlTag::generateAutoId('event_notification_block');

        $e->add(
            array(
                BASE_CMP_Console::DATA_KEY_URL => OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'invited')),
                BASE_CMP_Console::DATA_KEY_ICON_CLASS => 'new_mail ow_ic_calendar',
                BASE_CMP_Console::DATA_KEY_ITEMS_LABEL => $count,
                BASE_CMP_Console::DATA_KEY_BLOCK => true,
                BASE_CMP_Console::DATA_KEY_BLOCK_CLASS => 'ow_mild_green',
                BASE_CMP_Console::DATA_KEY_TITLE => OW::getLanguage()->text('event', 'console_notification_label'),
                BASE_CMP_Console::DATA_KEY_BLOCK_ID => $ntfBlockId
            )
        );

        OW::getDocument()->addOnloadScript("OW.bind('event_notifications_update',
            function(data){
                var blockNode = $('#{$ntfBlockId}');

                if( data.count <= 0 ){
                    blockNode.remove();
                }
                else{
                    $('span.ow_txt_value', blockNode).empty().append(data.count);
                }
            }
        );");
    }
}
OW::getEventManager()->bind(BASE_CMP_Console::EVENT_NAME, 'event_add_console_item');

function event_on_user_delete( OW_Event $event )
{
    $params = $event->getParams();

    if ( empty($params['deleteContent']) )
    {
        return;
    }

    $userId = $params['userId'];

    EVENT_BOL_EventService::getInstance()->deleteUserEvents($userId);
}
OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, 'event_on_user_delete');

function event_privacy_add_action( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();

    $action = array(
        'key' => 'event_view_attend_events',
        'pluginKey' => 'event',
        'label' => $language->text('event', 'privacy_action_view_attend_events'),
        'description' => '',
        'defaultValue' => 'everybody'
    );

    $event->add($action);
}
OW::getEventManager()->bind('plugin.privacy.get_action_list', 'event_privacy_add_action');

function event_feed_on_item_render_activity( OW_Event $event )
{
    $params = $event->getParams();
    $data = $event->getData();

    if ( $params['action']['entityType'] != 'event' )
    {
        return;
    }

    $eventId = $params['action']['entityId'];
    $usersCount = EVENT_BOL_EventService::getInstance()->findEventUsersCount($eventId, EVENT_BOL_EventService::USER_STATUS_YES);

    if ( $usersCount == 1 )
    {
        return;
    }

    $users = EVENT_BOL_EventService::getInstance()->findEventUsers($eventId, EVENT_BOL_EventService::USER_STATUS_YES, null, 6);

    $userIds = array();

    foreach ( $users as $user )
    {
        $userIds[] = $user->getUserId();
    }

    $activityUserIds = array();

    foreach ( $params['activity'] as $activity )
    {
        if ( $activity['activityType'] == 'event-join' )
        {
            $activityUserIds[] = $activity['data']['userId'];
        }
    }

    $lastUserId = reset($activityUserIds);
    $follows = array_intersect($activityUserIds, $userIds);
    $notFollows = array_diff($userIds, $activityUserIds);
    $idlist = array_merge($follows, $notFollows);

    $avatarList = new BASE_CMP_MiniAvatarUserList(array_slice($idlist, 0, 5));
    $avatarList->setEmptyListNoRender(true);

    if ( count($idlist) > 5 )
    {
        $avatarList->setViewMoreUrl(OW::getRouter()->urlForRoute('event.main_user_list', array('eventId' => $eventId)));
    }

    $language = OW::getLanguage();

    $avatarList = new BASE_CMP_MiniAvatarUserList($idlist);
    $content = $avatarList->render();

    if ( $lastUserId )
    {
        $userName = BOL_UserService::getInstance()->getDisplayName($lastUserId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($lastUserId);
        $content .= $language->text('event', 'feed_activity_joined', array('user' => '<a href="' . $userUrl . '">' . $userName . '</a>'));
    }

    $data['assign']['activity'] = array('template' => 'activity', 'vars' => array(
        'title' => $language->text('event', 'feed_activity_users', array('usersCount' => $usersCount)),
        'content' => $content
    ));

    $event->setData($data);
}
OW::getEventManager()->bind('feed.on_item_render', 'event_feed_on_item_render_activity');

function event_feed_collect_privacy( BASE_CLASS_EventCollector $event )
{
    $event->add(array('event-join', 'event_view_attend_events'));
}
OW::getEventManager()->bind('feed.collect_privacy', 'event_feed_collect_privacy');

function event_feed_collect_configurable_activity( BASE_CLASS_EventCollector $event )
{
    $language = OW::getLanguage();
    $event->add(array(
        'label' => $language->text('event', 'feed_content_label'),
        'activity' => '*:event'
    ));
}
OW::getEventManager()->bind('feed.collect_configurable_activity', 'event_feed_collect_configurable_activity');

function event_add_comment( OW_Event $e )
{
    $params = $e->getParams();

    if ( empty($params['entityType']) || $params['entityType'] != 'event' )
    {
        return;
    }

    $entityId = $params['entityId'];
    $userId = $params['userId'];
    $commentId = $params['commentId'];
    $event = EVENT_BOL_EventService::getInstance()->findEvent($entityId);

    $comment = BOL_CommentService::getInstance()->findComment($commentId);
    $eventUrl = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->id));

    $eventImage = EVENT_BOL_EventService::getInstance()->generateImageUrl($event->image, true);

    $string = OW::getLanguage()->text('event', 'email_notification_comment', array(
            'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
            'userUrl' => BOL_UserService::getInstance()->getUserUrl($userId),
            'url' => $eventUrl,
            'title' => strip_tags($event->title)
        ));

    $params = array(
        'plugin' => 'event',
        'action' => 'event-add_comment',
        'string' => $string,
        'avatar' => $eventImage,
        'content' => $comment->getMessage(),
        'url' => $eventUrl,
        'time' => time(),
        'userId' => $event->userId
    );

    $event = new OW_Event('base.notify', $params);
    OW::getEventManager()->trigger($event);
}

OW::getEventManager()->bind('base_add_comment', 'event_add_comment');
