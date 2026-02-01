<?php

namespace App\Validator;

use App\Dto\NotificationRequestDto;
use App\Enum\NotificationChannel;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class NotificationContentValidator extends ConstraintValidator
{
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
            $channelEnum = NotificationChannel::tryFrom($channel);

            if (!$channelEnum) {
                continue;
            }

            $requiredField = $channelEnum->getRequiredField();

            if ($requiredField && empty($value->content[$requiredField])) {
                $this->context->buildViolation("Field '$requiredField' is required for channel $channel.")
                    ->atPath("content[$requiredField]")
                    ->addViolation();
            }
        }
    }
}
