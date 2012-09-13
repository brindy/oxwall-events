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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.controllers
 * @since 1.0
 */
class EVENT_CTRL_Base extends OW_ActionController
{
    /**
     * @var EVENT_BOL_EventService
     */
    private $eventService;

    public function __construct()
    {
        parent::__construct();
        $this->eventService = EVENT_BOL_EventService::getInstance();
    }

    public function calendarView( $params ) 
    {
	if ( !empty($params['month']) )
	{
	    $parts = explode("-", $params['month']); 
            $timeToUse = mktime(0, 0, 0, $parts[0], 1, $parts[1]);
	}
	else
	{
	    $timeToUse = time();
	} 

        $date = getdate($timeToUse);
        $month = $date['mon'];
        $year = $date['year'];

	$actualDate = getdate();
	$isThisMonth = $actualDate['mon'] == $date['mon'] && $actualDate['year'] == $date['year'];

        $configs = $this->eventService->getConfigs();

        $language = OW::getLanguage();

   	$contentMenu = $this->getContentMenu();
        $contentMenu->getElement('calendar')->setActive(true);
        $this->addComponent('contentMenu', $contentMenu);
        $this->setPageHeading($language->text('event', 'calendar_events_page_heading'));
        $this->setPageTitle($language->text('event', 'calendar_events_page_title'));
        $this->setPageHeadingIconClass('ow_ic_calendar');
        OW::getDocument()->setDescription($language->text('event', 'calendar_events_page_desc'));

	$thisMonthCalendar = array();
	
	// what day of the week is the first?
	$day = 1;
	do 
	{
		$time = mktime(0, 0, 0, $month, $day--, $year);
		$date = getdate($time);
	} 
	while (1 != $date['wday']);

	// create an array of weeks => days => events
	$targetMonth = ($year * 12) + $month;
	$weekMonth = $date['mon'];
	$checkMonth = ($date['year'] * 12) + $date['mon'];
	while ($checkMonth <= $targetMonth)
	{
		$week = array();
		for ($i = 0; $i < 7; $i++) 
		{
			$events = $this->eventService->findEventsByDate($time);
			$eventListings = $this->eventService->getListingData($events);

			$firstEvent = count($events) > 0 ? $eventListings[$events[0]->getId()] : null;

			array_push($week, array(
				'day' => $date['mday'],
				'month' => $date['mon'],
				'event' => $firstEvent,
				'eventCount' => (count($events) - 1)
			));

			$time = mktime(0, 0, 0, $weekMonth, $date['mday'] + 1, $date['year']);
			$date = getdate($time);
			$weekMonth = $date['mon'];
			$checkMonth = ($date['year'] * 12) + $date['mon'];
		}
		array_push($thisMonthCalendar, $week);
	}

        if ( !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            $this->assign('noButton', true);
        }

	$todayInfo = getdate($timeToUse);
	$nextMonth = sprintf("%d-%d", $todayInfo['mon'] + 1, $todayInfo['year']);
	// error_log("next month: $nextMonth", 3, "/tmp/event.log");

	$prevMonth = sprintf("%d-%d", $todayInfo['mon'] - 1, $todayInfo['year']); 
	$this->assign('nextMonth', OW::getRouter()->urlForRoute("event.view_calendar_month", array( 'month' => $nextMonth )));
	$this->assign('prevMonth', OW::getRouter()->urlForRoute("event.view_calendar_month", array( 'month' => $prevMonth )));

        if ($isThisMonth) {
	    $this->assign('today', date("j"));
        }
	$this->assign('thisMonth', $month);
        $this->assign('monthWeeks', $thisMonthCalendar);
	$this->assign('monthName', date("F, Y", $timeToUse));
        $this->assign('add_new_url', OW::getRouter()->urlForRoute('event.add'));
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'event', 'main_menu_item');
    }

    public function cloneEvent( $params ) 
    {
        $language = OW::getLanguage();
        $event = $this->getEventForParams($params);
		$event->setId(null);
		$this->eventService->saveEvent( $event );

		if ( $event->getImage() ) 
		{
			$imagePath = $this->eventService->generateImagePath( $event->getImage(), false );
			$event->setImage( uniqid() );

			$pathInfo = pathinfo( $imagePath );
			$ext = $pathInfo['extension'];	
			$tmpImage = "/tmp/" . uniqid() . "." . $ext;
			if ( !copy( $imagePath , $tmpImage )) 
			{
				$this->eventService->deleteEvent( $event );
				OW::getFeedback()->error($language->text('event', 'clone_event_image_error'));
	            return;
			}

	        $this->eventService->saveEventImage( $tmpImage , $event->getImage() );
			$this->eventService->saveEvent( $event );
		}
		

        $eventUser = new EVENT_BOL_EventUser();
        $eventUser->setEventId($event->getId());
        $eventUser->setUserId(OW::getUser()->getId());
        $eventUser->setTimeStamp(time());
        $eventUser->setStatus(EVENT_BOL_EventService::USER_STATUS_YES);
        $this->eventService->saveEventUser($eventUser);
		
        $this->redirect(OW::getRouter()->urlForRoute('event.edit', array('eventId' => $event->getId())));
    }

    /**
     * Add new event controller
     */
    public function add()
    {
        $language = OW::getLanguage();
        $this->setPageTitle($language->text('event', 'add_page_title'));
        $this->setPageHeading($language->text('event', 'add_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_add');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'event', 'main_menu_item');

        // check permissions for this page
        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            $this->assign('err_msg', OW::getLanguage()->text('base', 'authorization_failed_feedback'));
            return;
        }

        $eventParams = array('pluginKey' => 'event', 'action' => 'add_event');
        $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);

        if ( $credits === false )
        {
            $this->assign('err_msg', OW::getEventManager()->call('usercredits.error_message', $eventParams));
            return;
        }

        $form = new EventAddForm('event_add');

        if ( date('n', time()) == 12 && date('j', time()) == 31 )
        {
            $defaultDate = (date('Y', time()) + 1) . '/1/1';
        }
        else if ( ( date('j', time()) + 1 ) > date('t') )
        {
            $defaultDate = date('Y', time()) . '/' . ( date('n', time()) + 1 ) . '/1';
        }
        else
        {
            $defaultDate = date('Y', time()) . '/' . date('n', time()) . '/' . ( date('j', time()) + 1 );
        }

        $form->getElement('start_date')->setValue($defaultDate);
        $form->getElement('end_date')->setValue($defaultDate);
        $form->getElement('start_time')->setValue('all_day');
        $form->getElement('end_time')->setValue('all_day');

        $form->getElement('open_date')->setValue($defaultDate);
        $form->getElement('close_date')->setValue($defaultDate);
        $form->getElement('open_time')->setValue('00:00');
        $form->getElement('close_time')->setValue('00:00');
        
       $checkboxId = UTIL_HtmlTag::generateAutoId('chk');
        $tdId = UTIL_HtmlTag::generateAutoId('td');
        $this->assign('tdId', $tdId);
        $this->assign('chId', $checkboxId);

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin("event")->getStaticJsUrl() . 'event.js');
        OW::getDocument()->addOnloadScript("new eventAddForm(". json_encode(array('checkbox_id' => $checkboxId, 'end_date_id' => $form->getElement('end_date')->getId(), 'tdId' => $tdId)) .")");

        if ( OW::getRequest()->isPost() )
        {
            if ( !empty($_POST['endDateFlag']) )
            {
                $this->assign('endDateFlag', true);
            }

            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                $dateArray = explode('/', $data['open_date']);
                $openStamp = mktime($data['open_time']['hour'], $data['open_time']['minute'], 0, $dateArray[1], $dateArray[2], $dateArray[0]);

                $dateArray = explode('/', $data['close_date']);
                $closeStamp = mktime($data['close_time']['hour'], $data['close_time']['minute'], 0, $dateArray[1], $dateArray[2], $dateArray[0]);


                $dateArray = explode('/', $data['start_date']);
                $startStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);

                if ( $data['start_time'] != 'all_day' )
                {
                    $startStamp = mktime($data['start_time']['hour'], $data['start_time']['minute'], 0, $dateArray[1], $dateArray[2], $dateArray[0]);
                }

                if ( !empty($_POST['endDateFlag']) && !empty($data['end_date']) )
                {
                    $dateArray = explode('/', $data['end_date']);
                    $endStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);

                    $endStamp = strtotime("+1 day", $endStamp);

                    if ( $data['end_time'] != 'all_day' )
                    {
                        $hour = 0;
                        $min = 0;

                        if( $data['end_time'] != 'all_day' )
                        {
                            $hour = $data['end_time']['hour'];
                            $min = $data['end_time']['minute'];
                        }

                        $dateArray = explode('/', $data['end_date']);
                        $endStamp = mktime($hour, $min, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
                    }
                }
                
                $imageValid = true;
                $datesAreValid = true;
                $imagePosted = false;

                if ( !empty($_FILES['image']['name']) )
                {
                    if ( (int) $_FILES['image']['error'] !== 0 || !is_uploaded_file($_FILES['image']['tmp_name']) || !UTIL_File::validateImage($_FILES['image']['name']) )
                    {
                        $imageValid = false;
                        OW::getFeedback()->error($language->text('base', 'not_valid_image'));
                    }
                    else
                    {
                        $imagePosted = true;
                    }
                }

                if ( empty($endStamp) )
                {
                    $endStamp = strtotime("+1 day", $startStamp);
                    $endStamp = mktime(0, 0, 0, date('n',$endStamp), date('j',$endStamp), date('Y',$endStamp));
                }

                if ( !empty($endStamp) && $endStamp < $startStamp )
                {
                    $datesAreValid = false;
                    OW::getFeedback()->error($language->text('event', 'add_form_invalid_end_date_error_message'));
                }

                if ( $openStamp > $endStamp )
                {
                    $datesAreValid = false;
                    OW::getFeedback()->error($language->text('event', 'add_form_invalid_open_date_error_message'));
                }

                if ( $closeStamp < $openStamp )
                {
                    $datesAreValid = false;
                    OW::getFeedback()->error($language->text('event', 'add_form_invalid_close_date_error_message'));
                }

                if ( $imageValid && $datesAreValid )
                {
                    $event = new EVENT_BOL_Event();
                    $event->setStartTimeStamp($startStamp);
                    $event->setEndTimeStamp($endStamp);
                    $event->setCreateTimeStamp(time());
                    $event->setTitle(htmlspecialchars($data['title']));
                    $event->setLocation(UTIL_HtmlTag::autoLink(strip_tags($data['location'])));
                    $event->setWhoCanView((int) $data['who_can_view']);
                    $event->setWhoCanInvite((int) $data['who_can_invite']);
                    $event->setDescription($data['desc']);
                    $event->setUserId(OW::getUser()->getId());
                    $event->setEndDateFlag( !empty($_POST['endDateFlag']) );
                    $event->setStartTimeDisable( $data['start_time'] == 'all_day' );
                    $event->setEndTimeDisable( $data['end_time'] == 'all_day' );
		    $event->setAttendeeLimit( $data['limit'] );
                    $event->setOpenTimeStamp( $openStamp );
                    $event->setCloseTimeStamp( $closeStamp );

                    if ( $imagePosted )
                    {
                        $event->setImage(uniqid());
                    }

                    $this->eventService->saveEvent($event);

                    if ( $imagePosted )
                    {
                        $this->eventService->saveEventImage($_FILES['image']['tmp_name'], $event->getImage());
                    }

                    $eventUser = new EVENT_BOL_EventUser();
                    $eventUser->setEventId($event->getId());
                    $eventUser->setUserId(OW::getUser()->getId());
                    $eventUser->setTimeStamp(time());
                    $eventUser->setStatus(EVENT_BOL_EventService::USER_STATUS_YES);
                    $this->eventService->saveEventUser($eventUser);

                    OW::getFeedback()->info($language->text('event', 'add_form_success_message'));

                    if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_ANYBODY )
                    {
                        $eventObj = new OW_Event('feed.action', array(
                                'pluginKey' => 'event',
                                'entityType' => 'event',
                                'entityId' => $event->getId(),
                                'userId' => $event->getUserId()
                            ));
                        OW::getEventManager()->trigger($eventObj);
                    }

                    if ( $credits === true )
                    {
                        OW::getEventManager()->call('usercredits.track_action', $eventParams);
                    }

                    $this->redirect(OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId())));
                }
            }
        }

        if( empty($_POST['endDateFlag']) )
        {
            //$form->getElement('start_time')->addAttribute('disabled', 'disabled');
            //$form->getElement('start_time')->addAttribute('style', 'display:none;');

            $form->getElement('end_date')->addAttribute('disabled', 'disabled');
            $form->getElement('end_date')->addAttribute('style', 'display:none;');

            $form->getElement('end_time')->addAttribute('disabled', 'disabled');
            $form->getElement('end_time')->addAttribute('style', 'display:none;');
        }

        $this->addForm($form);
    }

    /**
     * Get event by params(eventId)
     * 
     * @param array $params
     * @return EVENT_BOL_Event 
     */
    private function getEventForParams( $params )
    {
        if ( empty($params['eventId']) )
        {
            throw new Redirect404Exception();
        }

        $event = $this->eventService->findEvent($params['eventId']);

        if ( $event === null )
        {
            throw new Redirect404Exception();
        }

        return $event;
    }

    /**
     * Update event controller
     * 
     * @param array $params 
     */
    public function edit( $params )
    {
        $event = $this->getEventForParams($params);
        $language = OW::getLanguage();
        $form = new EventAddForm('event_edit');

        $form->getElement('title')->setValue($event->getTitle());
        $form->getElement('desc')->setValue($event->getDescription());
        $form->getElement('location')->setValue($event->getLocation());
        $form->getElement('who_can_view')->setValue($event->getWhoCanView());
        $form->getElement('who_can_invite')->setValue($event->getWhoCanInvite());

        $limit = $event->getAttendeeLimit();
        $form->getElement('limit')->setValue($limit > 0 ? $limit : '');

        $startTimeArray = array('hour' => date('G', $event->getStartTimeStamp()), 'minute' => date('i', $event->getStartTimeStamp()));
        $form->getElement('start_time')->setValue($startTimeArray);

        $startDate = date('Y', $event->getStartTimeStamp()) . '/' . date('n', $event->getStartTimeStamp()) . '/' . date('j', $event->getStartTimeStamp());
        $form->getElement('start_date')->setValue($startDate);

        $openTimeArray = array('hour' => date('G', $event->getOpenTimeStamp()), 'minute' => date('i', $event->getOpenTimeStamp()));
        $form->getElement('open_time')->setValue($openTimeArray);
        $openDate = date('Y', $event->getOpenTimeStamp()) . '/' . date('n', $event->getOpenTimeStamp()) . '/' . date('j', $event->getOpenTimeStamp());
        $form->getElement('open_date')->setValue($openDate);

        $closeTimeArray = array('hour' => date('G', $event->getCloseTimeStamp()), 'minute' => date('i', $event->getCloseTimeStamp()));
        $form->getElement('close_time')->setValue($closeTimeArray);
        $closeDate = date('Y', $event->getCloseTimeStamp()) . '/' . date('n', $event->getCloseTimeStamp()) . '/' . date('j', $event->getCloseTimeStamp());
        $form->getElement('close_date')->setValue($closeDate);

        if ( $event->getEndTimeStamp() !== null )
        {
            $endTimeArray = array('hour' => date('G', $event->getEndTimeStamp()), 'minute' => date('i', $event->getEndTimeStamp()));
            $form->getElement('end_time')->setValue($endTimeArray);


            $endTimeStamp = $event->getEndTimeStamp();
            if ( $event->getEndTimeDisable() )
            {
                $endTimeStamp = strtotime("-1 day", $endTimeStamp);
            }

            $endDate = date('Y', $endTimeStamp) . '/' . date('n', $endTimeStamp) . '/' . date('j', $endTimeStamp);
            $form->getElement('end_date')->setValue($endDate);
        }

        if ( $event->getStartTimeDisable() )
        {
            $form->getElement('start_time')->setValue('all_day');
        }

        if ( $event->getEndTimeDisable() )
        {
            $form->getElement('end_time')->setValue('all_day');
        }

        $form->getSubmitElement('submit')->setValue(OW::getLanguage()->text('event', 'edit_form_submit_label'));

        $checkboxId = UTIL_HtmlTag::generateAutoId('chk');
        $tdId = UTIL_HtmlTag::generateAutoId('td');
        $this->assign('tdId', $tdId);
        $this->assign('chId', $checkboxId);
        
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin("event")->getStaticJsUrl() . 'event.js');
        OW::getDocument()->addOnloadScript("new eventAddForm(". json_encode(array('checkbox_id' => $checkboxId, 'end_date_id' => $form->getElement('end_date')->getId(), 'tdId' => $tdId)) .")");

        if ( $event->getImage() )
        {
            $this->assign('imgsrc', $this->eventService->generateImageUrl($event->getImage(), true));
        }

        $endDateFlag = $event->getEndDateFlag();

        if ( OW::getRequest()->isPost() )
        {
            $endDateFlag = !empty($_POST['endDateFlag']);

            //$this->assign('endDateFlag', !empty($_POST['endDateFlag']));

            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();

                $dateArray = explode('/', $data['open_date']);
                $openStamp = mktime($data['open_time']['hour'], $data['open_time']['minute'], 0, $dateArray[1], $dateArray[2], $dateArray[0]);
                $event->setOpenTimeStamp($openStamp);

	        $dateArray = explode('/', $data['close_date']);
                $closeStamp = mktime($data['close_time']['hour'], $data['close_time']['minute'], 0, $dateArray[1], $dateArray[2], $dateArray[0]);	
                $event->setCloseTimeStamp($closeStamp);

                $dateArray = explode('/', $data['start_date']);

                $startStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);

                if ( $data['start_time'] != 'all_day' )
                {
                    $startStamp = mktime($data['start_time']['hour'], $data['start_time']['minute'], 0, $dateArray[1], $dateArray[2], $dateArray[0]);
                }

                if ( !empty($_POST['endDateFlag']) && !empty($data['end_date']) )
                {
					$dateArray = explode('/', $data['end_date']);
                    $endStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
                    $endStamp = strtotime("+1 day", $endStamp);

					if ( $data['end_time'] != 'all_day' )
                    {
						$hour = 0;
						$min = 0;

						if( $data['end_time'] != 'all_day' )
						{
							$hour = $data['end_time']['hour'];
							$min = $data['end_time']['minute'];
						}

                        $dateArray = explode('/', $data['end_date']);
						$endStamp = mktime($hour, $min, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
					}
                }

                $event->setStartTimeStamp($startStamp);
                
                if ( empty($endStamp) )
                {
                    $endStamp = strtotime("+1 day", $startStamp);
                    $endStamp = mktime(0, 0, 0, date('n',$endStamp), date('j',$endStamp), date('Y',$endStamp));
                }
                
                $ok = true;
                if ( $startStamp > $endStamp )
                {
                    OW::getFeedback()->error($language->text('event', 'add_form_invalid_end_date_error_message'));
                    $ok = false;
                }

                if ( $openStamp > $endStamp ) 
                {
                    OW::getFeedback()->error($language->text('event', 'add_form_invalid_open_date_error_message'));
                    $ok = false;
                }

                if ( $closeStamp < $openStamp ) 
                {
                    OW::getFeedback()->error($language->text('event', 'add_form_invalid_close_date_error_message'));
                    $ok = false;
                }

                if ($ok)
                {
                    $event->setEndTimeStamp($endStamp);

                    if ( !empty($_FILES['image']['name']) )
                    {
                        if ( (int) $_FILES['image']['error'] !== 0 || !is_uploaded_file($_FILES['image']['tmp_name']) || !UTIL_File::validateImage($_FILES['image']['name']) )
                        {
                            OW::getFeedback()->error($language->text('base', 'not_valid_image'));
                            $this->redirect();
                        }
                        else
                        {
                            $event->setImage(uniqid());
                            $this->eventService->saveEventImage($_FILES['image']['tmp_name'], $event->getImage());

                        }
                    }
                                        
                    $event->setTitle(htmlspecialchars($data['title']));
                    $event->setLocation(UTIL_HtmlTag::autoLink(strip_tags($data['location'])));
                    $event->setWhoCanView((int) $data['who_can_view']);
                    $event->setWhoCanInvite((int) $data['who_can_invite']);
                    $event->setDescription($data['desc']);
                    $event->setEndDateFlag(!empty($_POST['endDateFlag']));
                    $event->setStartTimeDisable( $data['start_time'] == 'all_day' );
                    $event->setEndTimeDisable( $data['end_time'] == 'all_day' );
		    $event->setAttendeeLimit( $data['limit'] );

                    $this->eventService->saveEvent($event);
                    
                    $e = new OW_Event(EVENT_BOL_EventService::EVENT_AFTER_EVENT_EDIT, array('eventId' => $event->id));
                    OW::getEventManager()->trigger($e);

                    OW::getFeedback()->info($language->text('event', 'edit_form_success_message'));
                    $this->redirect(OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId())));
                }
            }
        }

        if( !$endDateFlag )
        {
           // $form->getElement('start_time')->addAttribute('disabled', 'disabled');
           // $form->getElement('start_time')->addAttribute('style', 'display:none;');

            $form->getElement('end_date')->addAttribute('disabled', 'disabled');
            $form->getElement('end_date')->addAttribute('style', 'display:none;');

            $form->getElement('end_time')->addAttribute('disabled', 'disabled');
            $form->getElement('end_time')->addAttribute('style', 'display:none;');
        }

        $this->assign('endDateFlag', $endDateFlag);

        $this->setPageHeading($language->text('event', 'edit_page_heading'));
        $this->setPageTitle($language->text('event', 'edit_page_title'));
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'event', 'main_menu_item');
        $this->addForm($form);
    }

    /**
     * Delete event controller
     * 
     * @param array $params 
     */
    public function delete( $params )
    {
        $event = $this->getEventForParams($params);

        if ( !OW::getUser()->isAuthenticated() || ( OW::getUser()->getId() != $event->getUserId() && !OW::getUser()->isAuthorized('event') ) )
        {
            throw new Redirect403Exception();
        }

        $this->eventService->deleteEvent($event->getId());
        OW::getFeedback()->info(OW::getLanguage()->text('event', 'delete_success_message'));
        $this->redirect(OW::getRouter()->urlForRoute('event.main_menu_route'));
    }

    
    /**
     * View event controller
     * 
     * @param array $params
     */
    public function view( $params )
    {
        $event = $this->getEventForParams($params);

        $cmpId = UTIL_HtmlTag::generateAutoId('cmp');

        $this->assign('contId', $cmpId);

        if ( !OW::getUser()->isAuthorized('event', 'view_event') && $event->getUserId() != OW::getUser()->getId() )
        {
            $this->assign('authErrorText', OW::getLanguage()->text('event', 'event_view_permission_error_message'));
            return;
        }

        // guest gan't view private events
        if ( (int) $event->getWhoCanView() === EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY && !OW::getUser()->isAuthenticated() )
        {
            $this->redirect(OW::getRouter()->urlForRoute('event.private_event', array('eventId' => $event->getId())));
        }

        $eventInvite = $this->eventService->findEventInvite($event->getId(), OW::getUser()->getId());
        $eventUser = $this->eventService->findEventUser($event->getId(), OW::getUser()->getId());

        // check if user can view event
        if ( (int) $event->getWhoCanView() === EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY && $eventUser === null && $eventInvite === null && !OW::getUser()->isAuthorized('event') )
        {
            $this->redirect(OW::getRouter()->urlForRoute('event.private_event', array('eventId' => $event->getId())));
        }

        if ( OW::getUser()->isAuthorized('event') || OW::getUser()->getId() == $event->getUserId() )
        {
            $this->assign('editArray', array(
                'edit' => array('url' => OW::getRouter()->urlForRoute('event.edit', array('eventId' => $event->getId())), 'label' => OW::getLanguage()->text('event', 'edit_button_label')),
                'delete' =>
                array(
                    'url' => OW::getRouter()->urlForRoute('event.delete', array('eventId' => $event->getId())),
                    'label' => OW::getLanguage()->text('event', 'delete_button_label'),
                    'confirmMessage' => OW::getLanguage()->text('event', 'delete_confirm_message')
                ),
				'clone' => array(
					'url' => OW::getRouter()->urlForRoute('event.clone', array('eventId' => $event->getId())), 
					'label' => OW::getLanguage()->text('event', 'clone_button_label'),
					'confirmMessage' => OW::getLanguage()->text('event', 'clone_confirm_message')
				),
			));
        }
        
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'event', 'main_menu_item');
        $this->setPageHeading($event->getTitle());
        $this->setPageTitle(OW::getLanguage()->text('event', 'event_view_page_heading', array('event_title' => $event->getTitle())));
        $this->setPageHeadingIconClass('ow_ic_calendar_view');
        OW::getDocument()->setDescription(UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...'));

	$startTimeStamp = $event->getStartTimeStamp();
	$endTimeStamp = $event->getEndTimeStamp();

        $googleDate = date("Ymd\THi00\ZA", $startTimeStamp) . "/" . date("Ymd\THi00\Z", $endTimeStamp);

        if ($this->isClosed($event)) 
        {
            $this->assign('event_closed', true);

            // if user is already a no, don't show the form
            if ($eventUser->getStatus() == EVENT_BOL_EventService::USER_STATUS_NO) 
            {
                $this->assign('no_attend_form', true);
            }
        }

        $infoArray = array(
            'id' => $event->getId(),
            'image' => ( $event->getImage() ? $this->eventService->generateImageUrl($event->getImage(), false) : null ),
            'date' => UTIL_DateTime::formatSimpleDate($event->getStartTimeStamp(), $event->getStartTimeDisable()),
            'endDate' => $event->getEndTimeStamp() === null || !$event->getEndDateFlag() ? null : UTIL_DateTime::formatSimpleDate($event->getEndTimeDisable() ? strtotime("-1 day", $event->getEndTimeStamp()) : $event->getEndTimeStamp(),$event->getEndTimeDisable()),
            'location' => $event->getLocation(),
            'desc' => UTIL_HtmlTag::autoLink($event->getDescription()),
            'title' => $event->getTitle(),
            'creatorName' => BOL_UserService::getInstance()->getDisplayName($event->getUserId()),
            'creatorLink' => BOL_UserService::getInstance()->getUserUrl($event->getUserId()),
	    'attendeeLimit' => $event->getAttendeeLimit(),
            'attendeeCount' => $this->eventService->findEventUsersCount( $event->id, EVENT_BOL_EventService::USER_STATUS_YES ),
	    'googleDate' => $googleDate,
	    'googleTitle' => urlencode(substr(html_entity_decode($event->getTitle()), 0, 50)),
	    'googleDesc' => urlencode(substr(strip_tags(html_entity_decode($event->getDescription())), 0, 100)),
	    'googleLocation' => urlencode(substr(html_entity_decode($event->getLocation()), 0, 50))
        );

        $this->assign('info', $infoArray);

	if ( isset( $nfoArray['attendeeLimit'] )  && $infoArray['attendeeCount'] >= $infoArray['attendeeLimit'] )
        {
       	    $this->assign('event_full', true);
	    $eventFull = true;
        }

        if ($event->getOpenTimeStamp() > time()) 
        {
           $this->assign('event_open', UTIL_DateTime::formatSimpleDate($event->getOpenTimeStamp()));
        }

        // event attend form
        if ( OW::getUser()->isAuthenticated() && $event->getEndTimeStamp() > time() )
        {
            if ( $eventUser !== null )
            {
                $this->assign('currentStatus', OW::getLanguage()->text('event', 'user_status_label_' . $eventUser->getStatus()));
            }
            
	    if ( $eventUser !== null || !isset( $attendeeLimit ) || $attendeeCount < $attendeeLimit ) 
    	    {

	    $this->addForm(new AttendForm($event->getId(), $cmpId));

            $onloadJs = "
                    var \$context = $('#" . $cmpId . "');
                    $('#event_attend_yes_btn').click(
                        function(){
                            $('input[name=attend_status]', \$context).val(" . EVENT_BOL_EventService::USER_STATUS_YES . ");
                        }
                    );
                    $('#event_attend_maybe_btn').click(
                        function(){
                            $('input[name=attend_status]', \$context).val(" . EVENT_BOL_EventService::USER_STATUS_MAYBE . ");
                        }
                    );
                    $('#event_attend_no_btn').click(
                        function(){
                            $('input[name=attend_status]', \$context).val(" . EVENT_BOL_EventService::USER_STATUS_NO . ");
                        }
                    );

                    $('.current_status a', \$context).click(
                        function(){
                            $('.attend_buttons .buttons', \$context).fadeIn(500);
                    	}
                    );
	    ";

            OW::getDocument()->addOnloadScript($onloadJs);

            }
	    else
	    {
		$this->assign('no_attend_form', true);
            }
        }
        else
        {
            $this->assign('no_attend_form', true);
        }

        $friends = EVENT_BOL_EventService::getInstance()->findUserListForInvite($event->getId(), 0, 100);
        
        if ( OW::getEventManager()->call('plugin.friends') && OW::getUser()->isAuthenticated() )
        {
            $friendList = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => OW::getUser()->getId()));
            
            $friends = array_intersect($friends, $friendList);
        }
        
        if ( $event->getEndTimeStamp() > time() && ((int) $event->getUserId() === OW::getUser()->getId() || ( (int) $event->getWhoCanInvite() === EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT && $eventUser !== null) ) )
        {
            $params = array(
                $friends
            );

            $this->assign('inviteLink', true);
            OW::getDocument()->addOnloadScript("
                var eventFloatBox;
                $('#inviteLink', $('#" . $cmpId . "')).click(
                    function(){
                        eventFloatBox = OW.ajaxFloatBox('BASE_CMP_AvatarUserListSelect', " . json_encode($params) . ", {width:600, height:400, iconClass: 'ow_ic_user', title: '" . OW::getLanguage()->text('event', 'friends_invite_button_label') . "'});
                    }
                );
                OW.bind('base.avatar_user_list_select',
                    function(list){
                        eventFloatBox.close();
                        $.ajax({
                            type: 'POST',
                            url: " . json_encode(OW::getRouter()->urlFor('EVENT_CTRL_Base', 'inviteResponder')) . ",
                            data: 'eventId=" . json_encode($event->getId()) . "&userIdList='+JSON.stringify(list),
                            dataType: 'json',
                            success : function(data){
                                if( data.messageType == 'error' ){
                                    OW.error(data.message);
                                }
                                else{
                                    OW.info(data.message);
                                }
                            },
                            error : function( XMLHttpRequest, textStatus, errorThrown ){
                                OW.error(textStatus);
                            }
                        });
                    }
                );
            ");
        }

        $cmntParams = new BASE_CommentsParams('event', 'event');
        $cmntParams->setEntityId($event->getId());
        $cmntParams->setOwnerId($event->getUserId());
        $this->addComponent('comments', new BASE_CMP_Comments($cmntParams));
        $this->addComponent('userListCmp', new EVENT_CMP_EventUsers($event->getId()));
    }

    /**
     * Events list controller
     * 
     * @param array $params 
     */
    public function eventsList( $params )
    {
        if ( empty($params['list']) )
        {
            throw new Redirect404Exception();
        }

        $configs = $this->eventService->getConfigs();
        $page = ( empty($_GET['page']) || (int) $_GET['page'] < 0 ) ? 1 : (int) $_GET['page'];

        $language = OW::getLanguage();

        $toolbarList = array();

        switch ( trim($params['list']) )
        {
            case 'created':
                if ( !OW::getUser()->isAuthenticated() )
                {
                    throw new Redirect403Exception();
                }

                $this->setPageHeading($language->text('event', 'event_created_by_me_page_heading'));
                $this->setPageTitle($language->text('event', 'event_created_by_me_page_title'));
                $this->setPageHeadingIconClass('ow_ic_calendar');
                $events = $this->eventService->findUserEvents(OW::getUser()->getId(), $page);
                $eventsCount = $this->eventService->findLatestEventsCount();
                break;

            case 'joined':
                if ( !OW::getUser()->isAuthenticated() )
                {
                    throw new Redirect403Exception();
                }
                $contentMenu = $this->getContentMenu();
                $this->addComponent('contentMenu', $contentMenu);
                $this->setPageHeading($language->text('event', 'event_joined_by_me_page_heading'));
                $this->setPageTitle($language->text('event', 'event_joined_by_me_page_title'));
                $this->setPageHeadingIconClass('ow_ic_calendar');

                $events = $this->eventService->findUserParticipatedEvents(OW::getUser()->getId(), $page);
                $eventsCount = $this->eventService->findUserParticipatedEventsCount(OW::getUser()->getId());
                break;

            case 'latest':
                $contentMenu = $this->getContentMenu();
                $contentMenu->getElement('latest')->setActive(true);
                $this->addComponent('contentMenu', $contentMenu);
                $this->setPageHeading($language->text('event', 'latest_events_page_heading'));
                $this->setPageTitle($language->text('event', 'latest_events_page_title'));
                $this->setPageHeadingIconClass('ow_ic_calendar');
                OW::getDocument()->setDescription($language->text('event', 'latest_events_page_desc'));
                $events = $this->eventService->findPublicEvents($page);
                $eventsCount = $this->eventService->findPublicEventsCount();
                break;

            case 'user-participated-events':

                if ( empty($_GET['userId']) )
                {
                    throw new Redirect404Exception();
                }

                $user = BOL_UserService::getInstance()->findUserById($_GET['userId']);

                if ( $user === null )
                {
                    throw new Redirect404Exception();
                }

                $eventParams = array(
                    'action' => 'event_view_attend_events',
                    'ownerId' => $user->getId(),
                    'viewerId' => OW::getUser()->getId()
                );

                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);

                $displayName = BOL_UserService::getInstance()->getDisplayName($user->getId());

                $this->setPageHeading($language->text('event', 'user_participated_events_page_heading', array('display_name' => $displayName)));
                $this->setPageTitle($language->text('event', 'user_participated_events_page_title', array('display_name' => $displayName)));
                OW::getDocument()->setDescription($language->text('event', 'user_participated_events_page_desc', array('display_name' => $displayName)));
                $this->setPageHeadingIconClass('ow_ic_calendar');
                $events = $this->eventService->findUserParticipatedPublicEvents($user->getId(), $page);
                $eventsCount = $this->eventService->findUserParticipatedPublicEventsCount($user->getId());
                break;

            case 'past':
                $contentMenu = $this->getContentMenu();
                $this->addComponent('contentMenu', $contentMenu);
                $this->setPageHeading($language->text('event', 'past_events_page_heading'));
                $this->setPageTitle($language->text('event', 'past_events_page_title'));
                $this->setPageHeadingIconClass('ow_ic_calendar');
                OW::getDocument()->setDescription($language->text('event', 'past_events_page_desc'));
                $events = $this->eventService->findPublicEvents($page, null, true);
                $eventsCount = $this->eventService->findPublicEventsCount(true);
                break;

            case 'invited':
                if ( !OW::getUser()->isAuthenticated() )
                {
                    throw new Redirect403Exception();
                }

                 $this->eventService->hideInvitationByUserId(OW::getUser()->getId());

                $contentMenu = $this->getContentMenu();
                $this->addComponent('contentMenu', $contentMenu);
                $this->setPageHeading($language->text('event', 'invited_events_page_heading'));
                $this->setPageTitle($language->text('event', 'invited_events_page_title'));
                $this->setPageHeadingIconClass('ow_ic_calendar');
                $events = $this->eventService->findUserInvitedEvents(OW::getUser()->getId(), $page);
                $eventsCount = $this->eventService->findUserInvitedEventsCount(OW::getUser()->getId());
                
                foreach( $events as $event )
                {
                    $toolbarList[$event->getId()] = array();

                    $paramsList = array( 'eventId' => $event->getId(), 'page' => $page, 'list' => trim($params['list']) );

                    $acceptUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('event.invite_accept', $paramsList), array('page' => $page));
                    $ignoreUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('event.invite_decline', $paramsList), array('page' => $page));

                    $toolbarList[$event->getId()][] = array('label' => $language->text('event', 'accept_request'),'href' => $acceptUrl);
                    $toolbarList[$event->getId()][] = array('label' => $language->text('event', 'ignore_request'),'href' => $ignoreUrl);
                    
                }

                break;

            default:
                throw new Redirect404Exception();
        }

        $this->addComponent('paging', new BASE_CMP_Paging($page, ceil($eventsCount / $configs[EVENT_BOL_EventService::CONF_EVENTS_COUNT_ON_PAGE]), 5));

        if ( !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            $this->assign('noButton', true);
        }

        if ( empty($events) )
        {
            $this->assign('no_events', true);
        }
        
        $this->assign('listType', trim($params['list']));
        $this->assign('page', $page);
        $this->assign('events', $this->eventService->getListingDataWithToolbar($events, $toolbarList));
        $this->assign('toolbarList', $toolbarList);
        $this->assign('add_new_url', OW::getRouter()->urlForRoute('event.add'));
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'event', 'main_menu_item');
    }

    public function inviteListAccept( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new Redirect404Exception();
        }

        $userId = OW::getUser()->getId();
        $feedback = array('messageType' => 'error');
        $exit = false;
        $attendedStatus = 1;

        if ( !empty($attendedStatus) && !empty($params['eventId']) && $this->eventService->canUserView($params['eventId'], $userId) )
        {
            $event = $this->eventService->findEvent($params['eventId']);

            if ( $event->getEndTimeStamp() < time() )
            {
                throw new Redirect404Exception();
            }

            $eventUser = $this->eventService->findEventUser($params['eventId'], $userId);

            if ( $eventUser !== null && (int) $eventUser->getStatus() === (int) $attendedStatus )
            {
                $feedback['message'] = OW::getLanguage()->text('event', 'user_status_not_changed_error');
                //exit(json_encode($feedback));
            }

            if ( $event->getUserId() == OW::getUser()->getId() && (int) $attendedStatus == EVENT_BOL_EventService::USER_STATUS_NO )
            {
                $feedback['message'] = OW::getLanguage()->text('event', 'user_status_author_cant_leave_error');
                //exit(json_encode($feedback));
            }

            if ( !$exit )
            {
                if ( $eventUser === null )
                {
                    $eventUser = new EVENT_BOL_EventUser();
                    $eventUser->setUserId($userId);
                    $eventUser->setEventId((int) $params['eventId']);
                }

                $eventUser->setStatus((int) $attendedStatus);
                $eventUser->setTimeStamp(time());
                $this->eventService->saveEventUser($eventUser);
                $this->eventService->deleteUserEventInvites((int)$params['eventId'], OW::getUser()->getId());

                $feedback['message'] = OW::getLanguage()->text('event', 'user_status_updated');
                $feedback['messageType'] = 'info';

                if ( $eventUser->getStatus() == EVENT_BOL_EventService::USER_STATUS_YES && $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_ANYBODY )
                {
                    $userName = BOL_UserService::getInstance()->getDisplayName($event->getUserId());
                    $userUrl = BOL_UserService::getInstance()->getUserUrl($event->getUserId());
                    $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

                    OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
                            'activityType' => 'event-join',
                            'activityId' => $eventUser->getId(),
                            'entityId' => $event->getId(),
                            'entityType' => 'event',
                            'userId' => $eventUser->getUserId(),
                            'pluginKey' => 'event'
                            ), array(
                            'eventId' => $event->getId(),
                            'userId' => $eventUser->getUserId(),
                            'eventUserId' => $eventUser->getId(),
                            'string' =>  OW::getLanguage()->text('event', 'feed_actiovity_attend_string' ,  array( 'user' => $userEmbed )),
                            'feature' => array()
                        )));
                }
            }
        }
        else
        {
            $feedback['message'] = OW::getLanguage()->text('event', 'user_status_update_error');
        }

        if ( !empty($feedback['message']) )
        {
            switch( $feedback['messageType'] )
            {
                case 'info':
                    OW::getFeedback()->info($feedback['message']);
                    break;
                case 'warning':
                    OW::getFeedback()->warning($feedback['message']);
                    break;
                case 'error':
                    OW::getFeedback()->error($feedback['message']);
                    break;
            }
        }

        $paramsList = array();

        if ( !empty($params['page']) )
        {
            $paramsList['page'] = $params['page'];
        }

        if ( !empty($params['list']) )
        {
            $paramsList['list'] = $params['list'];
        }

        $this->redirect(OW::getRouter()->urlForRoute('event.view_event_list', $paramsList));
    }

    public function inviteListDecline( $params )
    {
        if ( !empty($params['eventId']) )
        {
            $this->eventService->deleteUserEventInvites((int)$params['eventId'], OW::getUser()->getId());
            OW::getLanguage()->text('event', 'user_status_updated');
        }
        else
        {
            OW::getLanguage()->text('event', 'user_status_update_error');
        }

        if ( !empty($params['page']) )
        {
            $paramsList['page'] = $params['page'];
        }

        if ( !empty($params['list']) )
        {
            $paramsList['list'] = $params['list'];
        }

        $this->redirect(OW::getRouter()->urlForRoute('event.view_event_list', $paramsList));
    }

    /**
     * User's events list controller
     * 
     * @param array $params 
     */
    public function eventUserLists( $params )
    {
        if ( empty($params['eventId']) || empty($params['list']) )
        {
            throw new Redirect404Exception();
        }

        $event = $this->eventService->findEvent((int) $params['eventId']);

        if ( $event === null )
        {
            throw new Redirect404Exception();
        }

        $listArray = array_flip($this->eventService->getUserListsArray());

        if ( !array_key_exists($params['list'], $listArray) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthorized('event', 'view_event') && $event->getUserId() != OW::getUser()->getId() && !OW::getUser()->isAuthorized('event') )
        {
            $this->assign('authErrorText', OW::getLanguage()->text('event', 'event_view_permission_error_message'));
            return;
        }

        // guest gan't view private events
        if ( (int) $event->getWhoCanView() === EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY && !OW::getUser()->isAuthenticated() )
        {
            $this->redirect(OW::getRouter()->urlForRoute('event.private_event', array('eventId' => $event->getId())));
        }

        $eventInvite = $this->eventService->findEventInvite($event->getId(), OW::getUser()->getId());
        $eventUser = $this->eventService->findEventUser($event->getId(), OW::getUser()->getId());

        // check if user can view event
        if ( (int) $event->getWhoCanView() === EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY && $eventUser === null && $eventInvite === null && !OW::getUser()->isAuthorized('event') )
        {
            $this->redirect(OW::getRouter()->urlForRoute('event.private_event', array('eventId' => $event->getId())));
        }

        $language = OW::getLanguage();
        $configs = $this->eventService->getConfigs();
        $page = ( empty($_GET['page']) || (int) $_GET['page'] < 0 ) ? 1 : (int) $_GET['page'];
        $status = $listArray[$params['list']];
        $eventUsers = $this->eventService->findEventUsers($event->getId(), $status, $page);
        $eventUsersCount = $this->eventService->findEventUsersCount($event->getId(), $status);

        $userIdList = array();

        /* @var $eventUser EVENT_BOL_EventUser */
        foreach ( $eventUsers as $eventUser )
        {
            $userIdList[] = $eventUser->getUserId();
        }

        $userDtoList = BOL_UserService::getInstance()->findUserListByIdList($userIdList);

        $this->addComponent('users', new EVENT_CMP_EventUsersList($userDtoList, $eventUsersCount, $configs[EVENT_BOL_EventService::CONF_EVENT_USERS_COUNT_ON_PAGE], true));

        $this->setPageHeading($language->text('event', 'user_list_page_heading_' . $status, array('eventTitle' => $event->getTitle())));
        $this->setPageTitle($language->text('event', 'user_list_page_heading_' . $status, array('eventTitle' => $event->getTitle())));
        OW::getDocument()->setDescription($language->text('event', 'user_list_page_desc_' . $status, array('eventTitle' => $event->getTitle())));

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'event', 'main_menu_item');
    }

    public function privateEvent( $params )
    {
        $language = OW::getLanguage();

        $this->setPageTitle($language->text('event', 'private_page_title'));
        $this->setPageHeading($language->text('event', 'private_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_lock');

        $eventId = $params['eventId'];
        $event = $this->eventService->findEvent((int) $eventId);

        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($event->userId));
        $displayName = BOL_UserService::getInstance()->getDisplayName($event->userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($event->userId);

        $this->assign('event', $event);
        $this->assign('avatar', $avatarList[$event->userId]);
        $this->assign('displayName', $displayName);
        $this->assign('userUrl', $userUrl);
        $this->assign('creator', $language->text('event', 'creator'));
    }
    /**
     * Get top menu for events list
     * 
     * @return BASE_CMP_ContentMenu
     */
    private function getContentMenu()
    {
        $menuItems = array();

        if ( OW::getUser()->isAuthenticated() )
        {
            $listNames = array(
                'invited' => array('iconClass' => 'ow_ic_bookmark'),
                'joined' => array('iconClass' => 'ow_ic_friends'),
                'past' => array('iconClass' => 'ow_ic_reply'),
		'calendar' => array('iconClass' => 'ow_ic_calendar'),
                'latest' => array('iconClass' => 'ow_ic_calendar_view')
            );
        }
        else
        {
            $listNames = array(
                'past' => array('iconClass' => 'ow_ic_reply'),
		'calendar' => array('iconClass' => 'ow_ic_calendar'),
                'latest' => array('iconClass' => 'ow_ic_calendar_view')
            );
        }

        foreach ( $listNames as $listKey => $listArr )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($listKey);
            $menuItem->setUrl(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => $listKey)));
            $menuItem->setLabel(OW::getLanguage()->text('event', 'common_list_type_' . $listKey . '_label'));
            $menuItem->setIconClass($listArr['iconClass']);
            $menuItems[] = $menuItem;
        }

        return new BASE_CMP_ContentMenu($menuItems);
    }

    private function isClosed($event) 
    {
        return $event->getOpenTimeStamp() > time() || $event->getCloseTimeStamp() < time();
    }

    /**
     * Responder for event attend form
     */
    public function attendFormResponder()
    {
        if ( !OW::getRequest()->isAjax() || !OW::getUser()->isAuthenticated() )
        {
            throw new Redirect404Exception();
        }

        $userId = OW::getUser()->getId();
        $respondArray = array('messageType' => 'error');
        
        if ( !empty($_POST['attend_status']) && in_array((int) $_POST['attend_status'], array(1, 2, 3)) && !empty($_POST['eventId']) && $this->eventService->canUserView($_POST['eventId'], $userId) )
        {
            $event = $this->eventService->findEvent($_POST['eventId']);
            
            if ( $event->getEndTimeStamp() < time() )
            {
                throw new Redirect404Exception();
            }

            $eventUser = $this->eventService->findEventUser($_POST['eventId'], $userId);

            if ( $eventUser !== null && (int) $eventUser->getStatus() === (int) $_POST['attend_status'] )
            {
                $respondArray['message'] = OW::getLanguage()->text('event', 'user_status_not_changed_error');
                exit(json_encode($respondArray));
            }

            if ($this->isClosed($event) && (int) $_POST['attend_status'] !== EVENT_BOL_EventService::USER_STATUS_NO ) {
                $respondArray['message'] = OW::getLanguage()->text('event', 'event_is_closed_error');
                exit(json_encode($respondArray));
            }

            $attendeeCount = $this->eventService->findEventUsersCount( $event->id, EVENT_BOL_EventService::USER_STATUS_YES );
            $attendeeLimit = $event->getAttendeeLimit();
            if ( isset( $attendeeLimit )  && $attendeeLimit > 0 && $attendeeCount >= $attendeeLimit )
	    {
		if ( (int) $_POST['attend_status'] == EVENT_BOL_EventService::USER_STATUS_YES ) 
		{
		    $respondArray['message'] = OW::getLanguage()->text('event', 'user_status_event_full_error');
		    exit(json_encode($respondArray));
		}
	    }
        
            if ( $eventUser === null )
            {
                $eventUser = new EVENT_BOL_EventUser();
                $eventUser->setUserId($userId);
                $eventUser->setEventId((int) $_POST['eventId']);
            }

            $eventUser->setStatus((int) $_POST['attend_status']);
            $eventUser->setTimeStamp(time());
            $this->eventService->saveEventUser($eventUser);

	    $attendeeCount = $this->eventService->findEventUsersCount( $event->id, EVENT_BOL_EventService::USER_STATUS_YES );
            $attendeeLimit = $event->getAttendeeLimit();
            if ( isset( $attendeeLimit )  && $attendeeCount >= $event->getAttendeeLimit() )
            {
		$respondArray['attendence_cap'] = OW::getLanguage()->text('event', 'full');
	    } 
	    else
	    {
	        $respondArray['attendence_cap'] = '';
            }

            $this->eventService->deleteUserEventInvites((int)$_POST['eventId'], OW::getUser()->getId());
            
            $respondArray['message'] = OW::getLanguage()->text('event', 'user_status_updated');
            $respondArray['messageType'] = 'info';
            $respondArray['currentLabel'] = OW::getLanguage()->text('event', 'user_status_label_' . $eventUser->getStatus());
            $eventUsersCmp = new EVENT_CMP_EventUsers((int) $_POST['eventId']);
            $respondArray['eventUsersCmp'] = $eventUsersCmp->render();
            $respondArray['newInvCount'] = $this->eventService->findUserInvitedEventsCount(OW::getUser()->getId());

            if ( $eventUser->getStatus() == EVENT_BOL_EventService::USER_STATUS_YES && $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_ANYBODY )
            {
//                $params = array(
//                    'pluginKey' => 'event',
//                    'entityType' => 'event_join',
//                    'entityId' => $eventUser->getUserId(),
//                    'userId' => $eventUser->getUserId()
//                );
//
//                $url = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId()));
//                $thumb = $this->eventService->generateImageUrl($event->getId(), true);
//
//
//
//                $dataValue = array(
//                    'time' => $eventUser->getTimeStamp(),
//                    'string' => OW::getLanguage()->text('event', 'feed_user_join_string'),
//                    'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
//                        <a href="' . $url . '"><img src="' . $thumb . '" /></a>
//                        </div><div class="ow_newsfeed_item_content">
//                        <a class="ow_newsfeed_item_title" href="' . $url . '">' . $event->getTitle() . '</a><div class="ow_remark">' . strip_tags($event->getDescription()) . '</div></div></div>',
//                    'view' => array(
//                        'iconClass' => 'ow_ic_calendar'
//                    )
//                );
//
//                $fEvent = new OW_Event('feed.action', $params, $dataValue);
//                OW::getEventManager()->trigger($fEvent);

                $userName = BOL_UserService::getInstance()->getDisplayName($event->getUserId());
                $userUrl = BOL_UserService::getInstance()->getUserUrl($event->getUserId());
                $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

                OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
                        'activityType' => 'event-join',
                        'activityId' => $eventUser->getId(),
                        'entityId' => $event->getId(),
                        'entityType' => 'event',
                        'userId' => $eventUser->getUserId(),
                        'pluginKey' => 'event'
                        ), array(
                        'eventId' => $event->getId(),
                        'userId' => $eventUser->getUserId(),
                        'eventUserId' => $eventUser->getId(),
                        'string' =>  OW::getLanguage()->text('event', 'feed_actiovity_attend_string' ,  array( 'user' => $userEmbed )),
                        'feature' => array()
                    )));
            }
        }
        else
        {
            $respondArray['message'] = OW::getLanguage()->text('event', 'user_status_update_error');
        }

        exit(json_encode($respondArray));
    }

    /**
     * Responder for event invite form
     */
    public function inviteResponder()
    {
        $respondArray = array();

        if ( empty($_POST['eventId']) || empty($_POST['userIdList']) || !OW::getUser()->isAuthenticated() )
        {
            $respondArray['messageType'] = 'error';
            $respondArray['message'] = '_ERROR_';
            echo json_encode($respondArray);
            exit;
        }

        $idList = json_decode($_POST['userIdList']);

        if ( empty($_POST['eventId']) || empty($idList) )
        {
            $respondArray['messageType'] = 'error';
            $respondArray['message'] = '_EMPTY_EVENT_ID_';
            echo json_encode($respondArray);
            exit;
        }

        $event = $this->eventService->findEvent($_POST['eventId']);

        if ( $event->getEndTimeStamp() < time() )
        {
            throw new Redirect404Exception();
        }

        if ( $event === null )
        {
            $respondArray['messageType'] = 'error';
            $respondArray['message'] = '_EMPTY_EVENT_';
            echo json_encode($respondArray);
            exit;
        }

        if ( (int) $event->getUserId() === OW::getUser()->getId() || (int) $event->getWhoCanInvite() === EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT )
        {
            $count = 0;

            foreach ( $idList as $userId )
            {
                $eventInvite = $this->eventService->findEventInvite($event->getId(), $userId);

                if ( $eventInvite === null )
                {
                    $eventInvite = $this->eventService->inviteUser($event->getId(), $userId, OW::getUser()->getId());
                    $eventObj = new OW_Event('event.invite_user', array('userId' => $userId, 'inviterId' => OW::getUser()->getId(), 'eventId' => $event->getId(), 'imageId' => $event->getImage(), 'eventTitle' => $event->getTitle(), 'eventDesc' => $event->getDescription(), 'dispalyInvitation' => $eventInvite->displayInvitation));
                    OW::getEventManager()->trigger($eventObj);
                    $count++;
                }
            }
        }

        $respondArray['messageType'] = 'info';
        $respondArray['message'] = OW::getLanguage()->text('event', 'users_invite_success_message', array('count' => $count));

        exit(json_encode($respondArray));
    }
}

/**
 * Event attend form
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.forms
 * @since 1.0
 */
class AttendForm extends Form
{

    public function __construct( $eventId, $contId )
    {
        parent::__construct('event_attend');
        $this->setAction(OW::getRouter()->urlFor('EVENT_CTRL_Base', 'attendFormResponder'));
        $this->setAjax();
        $hidden = new HiddenField('attend_status');
        $this->addElement($hidden);
        $eventIdField = new HiddenField('eventId');
        $eventIdField->setValue($eventId);
        $this->addElement($eventIdField);
        $this->setAjaxResetOnSuccess(false);
        $this->bindJsFunction(Form::BIND_SUCCESS, "function(data){
            var \$context = $('#" . $contId . "');
            if(data.messageType == 'error'){
                OW.error(data.message);
            }
            else{
		$('.attendence_cap', \$context).html(data.attendence_cap);
                $('.current_status span.status', \$context).empty().html(data.currentLabel);
                $('.current_status span.link', \$context).css({display:'inline'});
                $('.attend_buttons .buttons', \$context).fadeOut(500);
                $('.userList', \$context).empty().html(data.eventUsersCmp);
                OW.trigger('event_notifications_update', {count:data.newInvCount});
                OW.info(data.message);
            }
        }");
    }
}


/**
 * Add new event form
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.forms
 * @since 1.0
 */
class EventAddForm extends Form
{

    public function __construct( $name )
    {
        parent::__construct($name);

        $militaryTime = Ow::getConfig()->getValue('base', 'military_time');

        $language = OW::getLanguage();

        $currentYear = date('Y', time());

        $title = new TextField('title');
        $title->setRequired();
        $title->setLabel($language->text('event', 'add_form_title_label'));
        $this->addElement($title);

        $startDate = new DateField('start_date');
        $startDate->setMinYear($currentYear);
        $startDate->setMaxYear($currentYear + 5);
        $startDate->setRequired();
        $this->addElement($startDate);

        $startTime = new EventTimeField('start_time');
        $startTime->setMilitaryTime($militaryTime);
        
        if ( !empty($_POST['endDateFlag']) )
        {
            $startTime->setRequired();
        }

        $this->addElement($startTime);

        $endDate = new DateField('end_date');
        $endDate->setMinYear($currentYear);
        $endDate->setMaxYear($currentYear + 5);
        $this->addElement($endDate);

        $endTime = new EventTimeField('end_time');
        $endTime->setMilitaryTime($militaryTime);
        $this->addElement($endTime);

        $openDate = new DateField('open_date');
        $openDate->setMinYear($currentYear);
        $openDate->setMaxYear($currentYear + 5);
        $this->addElement($openDate);

	$openTime = new EventTimeField('open_time', false);
	$openTime->setMilitaryTime($militaryTime);
	$this->addElement($openTime);

        $openDate->setRequired();
        $openTime->setRequired();

        $closeDate = new DateField('close_date');
        $closeDate->setMinYear($currentYear);
        $closeDate->setMaxYear($currentYear + 5);
        $this->addElement($closeDate);

        $closeTime = new EventTimeField('close_time', false);
        $closeTime->setMilitaryTime($closeTime);
        $this->addElement($closeTime);

        $closeDate->setRequired();
        $closeTime->setRequired();

        $location = new TextField('location');
        $location->setRequired();
        $location->setLabel($language->text('event', 'add_form_location_label'));
        $this->addElement($location);

        $whoCanView = new RadioField('who_can_view');
        $whoCanView->setRequired();
        $whoCanView->addOptions(
            array(
                '1' => $language->text('event', 'add_form_who_can_view_option_anybody'),
                '2' => $language->text('event', 'add_form_who_can_view_option_invit_only')
            )
        );
        $whoCanView->setLabel($language->text('event', 'add_form_who_can_view_label'));
        $this->addElement($whoCanView);

        $whoCanInvite = new RadioField('who_can_invite');
        $whoCanInvite->setRequired();
        $whoCanInvite->addOptions(
            array(
                EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT => $language->text('event', 'add_form_who_can_invite_option_participants'),
                EVENT_BOL_EventService::CAN_INVITE_CREATOR => $language->text('event', 'add_form_who_can_invite_option_creator')
            )
        );
        $whoCanInvite->setLabel($language->text('event', 'add_form_who_can_invite_label'));
        $this->addElement($whoCanInvite);

        $submit = new Submit('submit');
        $submit->setValue($language->text('event', 'add_form_submit_label'));
        $this->addElement($submit);

        $desc = new WysiwygTextarea('desc');
        $desc->setLabel($language->text('event', 'add_form_desc_label'));
        $desc->setRequired();
        $this->addElement($desc);

        $imageField = new FileField('image');
        $imageField->setLabel($language->text('event', 'add_form_image_label'));
        $this->addElement($imageField);

	$limitField = new TextField('limit');
	$limitField->addValidator(new IntValidator( 0 ));
	$limitField->setLabel($language->text('event', 'limit'));
	$this->addElement($limitField);

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

    }
}

/**
 * Form element: CheckboxField.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_core
 * @since 1.0
 */
class EventTimeField extends FormElement
{
    private $militaryTime;

    private $allDay = false;

    private $allowAllDay = true;

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct( $name, $allowAllDay = true )
    {
        parent::__construct($name);
        $this->militaryTime = false;
        $this->allowAllDay = $allowAllDay;
    }

    public function setMilitaryTime( $militaryTime )
    {
        $this->militaryTime = (bool) $militaryTime;
    }

    public function setValue( $value )
    {
        if ( $value === null )
        {
            $this->value = null;
        }

        $this->allDay = false;
        
        if ( $value === 'all_day' )
        {
            $this->allDay = true;
            $this->value = null;
            return;
        }

        if ( is_array($value) && isset($value['hour']) && isset($value['minute']) )
        {
            $this->value = array_map('intval', $value);
        }

        if ( is_string($value) && strstr($value, ':') )
        {
            $parts = explode(':', $value);
            $this->value['hour'] = (int) $parts[0];
            $this->value['minute'] = (int) $parts[1];
        }
    }

    public function getValue()
    {
        if ( $this->allDay === true )
        {
            return 'all_day';
        }

        return $this->value;
    }

    /**
     *
     * @return string
     */
    public function getElementJs()
    {
        $jsString = "var formElement = new OwFormElement('" . $this->getId() . "', '" . $this->getName() . "');";

        /** @var $value Validator  */
        foreach ( $this->validators as $value )
        {
            $jsString .= "formElement.addValidator(" . $value->getJsValidator() . ");";
        }

        return $jsString;
    }

    private function getTimeString( $hour, $minute )
    {
        if ( $this->militaryTime )
        {
            $hour = $hour < 10 ? '0' . $hour : $hour;
            return $hour . ':' . $minute;
        }
        else
        {
            if ( $hour == 12 )
            {
                $dp = 'pm';
            }
            else if ( $hour > 12 )
            {
                $hour = $hour - 12;
                $dp = 'pm';
            }
            else
            {
                $dp = 'am';
            }

            $hour = $hour < 10 ? '0' . $hour : $hour;
            return $hour . ':' . $minute . $dp;
        }
    }

    /**
     * @see FormElement::renderInput()
     *
     * @param array $params
     * @return string
     */
    public function renderInput( $params = null )
    {
        parent::renderInput($params);
        
        for ( $hour = 0; $hour <= 23; $hour++ )
        {
            $valuesArray[$hour . ':0'] = array('label' => $this->getTimeString($hour, '00'), 'hour' => $hour, 'minute' => 0);
            $valuesArray[$hour . ':30'] = array('label' => $this->getTimeString($hour, '30'), 'hour' => $hour, 'minute' => 30);
        }

        $optionsString = UTIL_HtmlTag::generateTag('option', array('value' => ""), true, OW::getLanguage()->text('event', 'time_field_invitation_label'));

        $allDayAttrs = array( 'value' => "all_day"  );
       
        if ( $this->allowAllDay) { 
            if ( $this->allDay )
            {
                $allDayAttrs['selected'] = 'selected';
            } 
        
            $optionsString = UTIL_HtmlTag::generateTag('option', $allDayAttrs, true, OW::getLanguage()->text('event', 'all_day'));
        }

        foreach ( $valuesArray as $value => $labelArr )
        {
            $attrs = array('value' => $value);

            if ( !empty($this->value) && $this->value['hour'] === $labelArr['hour'] && $this->value['minute'] === $labelArr['minute'] )
            {
                $attrs['selected'] = 'selected';
            }

            $optionsString .= UTIL_HtmlTag::generateTag('option', $attrs, true, $labelArr['label']);
        }

        return UTIL_HtmlTag::generateTag('select', $this->attributes, true, $optionsString);
    }
}

class EVENT_CMP_EventUsersList extends BASE_CMP_Users
{

    public function getFields( $userIdList )
    {
        $fields = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate', 'sex');

        if ( $qBdate->onView )
            $qs[] = 'birthdate';

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex->onView )
            $qs[] = 'sex';

        $questionList = BOL_QuestionService::getInstance()->getQuestionData($userIdList, $qs);

        foreach ( $questionList as $uid => $q )
        {

            $fields[$uid] = array();

            $age = '';

            if ( !empty($q['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($q['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            }

            if ( !empty($q['sex']) )
            {
                $fields[$uid][] = array(
                    'label' => '',
                    'value' => BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $q['sex']) . ' ' . $age
                );
            }

            if ( !empty($q['birthdate']) )
            {
                $dinfo = date_parse($q['birthdate']);
            }
        }

        return $fields;
    }
}
