<?php

namespace App\Validator;

use App\Dto\NotificationRequestDto;
use App\Enum\NotificationChannel;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NotificationContentValidator extends ConstraintValidator
{
    public function __construct(
        #[Autowire('%enabled_channels%')]
        private array $enabledChannels
    )
    {
    }

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
            // Checking whether such a channel exists
            if (!NotificationChannel::isValid($channel)) {
                $this->context->buildViolation("Channel '$channel' is invalid.")
                    ->atPath('channels')
                    ->addViolation();
                continue;
            }

            // Checking whether the channel is allowed
            if (!in_array($channel, $this->enabledChannels, true)) {
                $this->context->buildViolation("Channel '$channel' is disabled.")
                    ->atPath('channels')
                    ->addViolation();
                continue;
            }

            $channelEnum = NotificationChannel::tryFrom($channel);
            $requiredField = $channelEnum->getRequiredField();

            if ($requiredField && empty($value->content[$requiredField])) {
                $this->context->buildViolation("Field '$requiredField' is required for channel $channel.")
                    ->atPath("content[$requiredField]")
                    ->addViolation();
            }
        }
    }
}
