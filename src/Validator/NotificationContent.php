<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NotificationContent extends Constraint
{
    public string $message = 'The content is missing required fields for the selected channels.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
