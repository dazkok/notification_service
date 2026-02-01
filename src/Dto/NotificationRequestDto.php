<?php

namespace App\Dto;

use App\Validator\NotificationContent;
use App\Enum\NotificationChannel;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[NotificationContent]
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

        #[Assert\Optional]
        #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
        public ?\DateTimeImmutable $scheduledDate,

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
