<?php

namespace App\Message;

use App\Dto\NotificationRequestDto;

final class SendNotification
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    public function __construct(
        public NotificationRequestDto $dto,
    )
    {
    }
}
