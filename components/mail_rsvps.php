<?php

class EVENT_CMP_MailRsvps extends OW_Component 
{

    public function __construct( $url, $eventId ) 
    {
        parent::__construct();
        $this->assign('trigger', "
            OW.trigger('event.mail_attendees', [ '$url' , '$eventId' ]);
        ");
    }

}

