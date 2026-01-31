<?php

namespace App\Notification\Provider;

use App\Enum\NotificationChannel;
use App\Notification\HtmlCapableInterface;
use App\Notification\NotificationProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

readonly class AwsEmailProvider implements NotificationProviderInterface, HtmlCapableInterface
{
    /**
     * @param MailerInterface $mailer
     * @param string $fromEmail
     * @param LoggerInterface $logger
     */
    public function __construct(
        private MailerInterface $mailer,
        private string          $fromEmail,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @param array $content
     * @return bool
     * @throws TransportExceptionInterface
     */
    public function send(array $content): bool
    {
        try {
            $email = new Email()
                ->from($this->fromEmail)
                ->to($content['email'])
                ->subject($content['subject'] ?? 'Notification');

            if (!empty($content['html'])) {
                $email->html($content['html']);
            }

            $email->text($content['body'] ?? '');

            $this->mailer->send($email);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('AWS SES Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $channel
     * @return bool
     */
    public function supports(string $channel): bool
    {
        return $channel === NotificationChannel::EMAIL->value;
    }
}
