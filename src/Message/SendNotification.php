<?php

namespace App\Message;

final class SendNotification
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    /**
     * @param int $notificationLogId
     */
    public function __construct(
        public int $notificationLogId
    )
    {
    }
}
