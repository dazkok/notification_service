<?php

namespace App\Validator;

use App\Dto\NotificationRequestDto;
use App\Enum\NotificationChannel;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class NotificationContentValidator extends ConstraintValidator
{
    private const CHANNEL_REQUIRED_FIELDS = [
        NotificationChannel::EMAIL->value => 'email',
        NotificationChannel::SMS->value => 'phone'
    ];

    /**
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof NotificationRequestDto) {
            return;
        }

        foreach ($value->channels as $channel) {
            $requiredField = self::CHANNEL_REQUIRED_FIELDS[$channel] ?? null;

            if ($requiredField && empty($value->content[$requiredField])) {
                $this->context->buildViolation("Field '$requiredField' is required for channel $channel.")
                    ->atPath("content[$requiredField]")
                    ->addViolation();
            }
        }
    }
}
