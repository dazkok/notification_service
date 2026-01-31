<?php

namespace App\Dto;

use App\Enum\NotificationChannel;
use Symfony\Component\Validator\Constraints as Assert;

readonly class NotificationRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $userId,

        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\Choice(callback: [NotificationChannel::class, 'values'])
        ])]
        public array  $channels,

        #[Assert\Collection(
            fields: [
                'subject' => new Assert\NotBlank(),
                'body' => new Assert\NotBlank(),
            ],
            allowExtraFields: true
        )]
        public array  $content,
    )
    {

    }
}
