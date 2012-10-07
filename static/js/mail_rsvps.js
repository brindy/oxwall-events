   OW.bind('event.mail_attendees', function( $url, $eventId ) 
   {
      
      $message = $('#mail_body').val();
      if ($.trim($message).length == 0) {
         OW.error('No message entered.');
         return;
      }

      $yes = $('#mail_yes').is(':checked') ? 'yes' : '';
      $no = $('#mail_no').is(':checked') ? 'no' : '';
      $maybe = $('#mail_maybe').is(':checked') ? 'maybe' : '';
      $comment = $('#mail_as_comment').is(':checked') ? 'comment' : '';

      $params = 'eventId=' + $eventId + '&message=' + $message + '&yes=' + $yes + '&no=' + $no + '&maybe=' + $maybe + '&comment=' + $comment;

      $('#mail_container').hide();
      $('#mail_wait').hide();

      // make the call
      $.ajax({
        type: 'POST',
        url: $url,
        data: $params,
        dataType: 'json',
        success : function(data)
          {
             if ( data.messageType == 'error' )
             {
               OW.error(data.message);
               $('#mail_container').show();
               $('#mail_wait').show();
             }
             else
             {
               OW.info(data.message);
               OW.trigger('event.mail_sent', []);
             }
          },
        error : function( XMLHttpRequest, textStatus, errorThrown )
          {
            OW.error(textStatus);
          }
      });

   });

