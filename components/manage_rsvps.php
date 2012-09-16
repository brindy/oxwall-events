<?php

class EVENT_CMP_ManageRsvps extends OW_Component 
{

    public function __construct( $eventId ) 
    {
        parent::__construct();

        $eventService = EVENT_BOL_EventService::getInstance();
        $userService = BOL_UserService::getInstance();
        $avatarService = BOL_AvatarService::getInstance();

        $defaultAvatarUrl = $avatarService->getDefaultAvatarUrl();

        $allUsers = $userService->findList(0, 100, false);

        $responseList = array();

        foreach ($allUsers as $user) 
        {
            $eventUser = $eventService->findEventUser($eventId, $user->getId());
            $status = (null === $eventUser) ? 0 : $eventUser->getStatus();
            $avatarUrl = $avatarService->getAvatarUrl($user->getId(), 1);
	
            $yesClick = 'OW.trigger(\'event.manage_user\', [' . $eventId . ',' . $user->getId() . ', 1])';
            $maybeClick = 'OW.trigger(\'event.manage_user\', [' . $eventId . ',' . $user->getId() . ', 2])';
            $noClick = 'OW.trigger(\'event.manage_user\', [' . $eventId . ',' . $user->getId() . ', 3])';

            $responseList[] = array(
                'avatar' => ($avatarUrl !== null) ? $avatarUrl : $defaultAvatarUrl,
                'user' => $user,
	        'yes_click' => $yesClick,
                'no_click' => $noClick,
                'maybe_click' => $maybeClick,
                'status' => $status
            );

	}

        usort($responseList, array('EVENT_CMP_ManageRsvps', 'compareResponses'));

        $this->assign('userResponseList', $responseList);
    }

    public static function compareResponses($array1, $array2) 
    {
        return strcmp(strtolower($array1['user']->getUsername()), strtolower($array2['user']->getUsername()));
    }

}

