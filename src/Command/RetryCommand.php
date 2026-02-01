<?php

namespace App\Command;

use App\Enum\NotificationStatus;
use App\Message\SendNotification;
use App\Repository\NotificationLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:retry',
    description: 'Retry failed notifications and check for pending jobs'
)]
final class RetryCommand extends Command
{
    public function __construct(
        private NotificationLogRepository $repository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be retried without actually retrying'
            )
            ->setHelp('This command allows you to retry all failed notifications and check for any pending jobs that might be stuck.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        $io->title('Notification Retry Command');

        // Check for failed notifications
        $failedNotifications = $this->repository->findFailedNotifications();
        $failedCount = count($failedNotifications);

        if ($failedCount === 0) {
            $io->success('No failed notifications found.');
        } else {
            $io->warning("Found {$failedCount} failed notifications:");
            
            foreach ($failedNotifications as $notification) {
                $io->writeln(sprintf(
                    '  - ID: %d | User: %s | Type: %s | Recipient: %s | Scheduled: %s | Created: %s',
                    $notification->getId(),
                    $notification->getUserId(),
                    $notification->getType(),
                    $notification->getRecipient(),
                    $notification->getScheduledAt()->format('Y-m-d H:i:s'),
                    $notification->getCreatedAt()->format('Y-m-d H:i:s')
                ));
            }

            if (!$isDryRun) {
                $io->section('Retrying failed notifications...');
                
                foreach ($failedNotifications as $notification) {
                    // Reset status to PENDING
                    $notification->setStatus(NotificationStatus::PENDING);
                    $notification->setSentAt(null);
                    
                    // Send to message bus for retry
                    $this->bus->dispatch(new SendNotification($notification->getId()));
                    
                    $io->writeln("  ✓ Retried notification ID: {$notification->getId()}");
                }
                
                $this->entityManager->flush();
                $io->success("Successfully retried {$failedCount} notifications.");
            } else {
                $io->note('Dry run mode: No notifications were actually retried.');
            }
        }

        // Check for pending notifications
        $io->section('Checking pending notifications...');
        
        $pendingCount = $this->repository->countPendingNotifications();
        $pendingNotifications = $this->repository->findPendingNotifications();

        if ($pendingCount === 0) {
            $io->success('No pending notifications found.');
        } else {
            $io->warning("Found {$pendingCount} pending notifications:");
            
            $stuckNotifications = [];
            $now = new \DateTimeImmutable();
            
            foreach ($pendingNotifications as $notification) {
                $age = $now->getTimestamp() - $notification->getCreatedAt()->getTimestamp();
                $ageMinutes = round($age / 60);
                
                $io->writeln(sprintf(
                    '  - ID: %d | User: %s | Type: %s | Age: %d minutes | Scheduled: %s | Created: %s',
                    $notification->getId(),
                    $notification->getUserId(),
                    $notification->getType(),
                    $ageMinutes,
                    $notification->getScheduledAt()->format('Y-m-d H:i:s'),
                    $notification->getCreatedAt()->format('Y-m-d H:i:s')
                ));
                
                // Consider notifications stuck if older than 30 minutes
                if ($ageMinutes > 30) {
                    $stuckNotifications[] = $notification;
                }
            }
            
            if (!empty($stuckNotifications)) {
                $io->error(sprintf(
                    'Found %d potentially stuck pending notifications (older than 30 minutes):',
                    count($stuckNotifications)
                ));
                
                foreach ($stuckNotifications as $notification) {
                    $io->writeln(sprintf(
                        '  ⚠️  ID: %d | User: %s | Type: %s | Created: %s',
                        $notification->getId(),
                        $notification->getUserId(),
                        $notification->getType(),
                        $notification->getCreatedAt()->format('Y-m-d H:i:s')
                    ));
                }
                
                if (!$isDryRun) {
                    if ($io->confirm('Would you like to retry these stuck notifications?')) {
                        foreach ($stuckNotifications as $notification) {
                            $this->bus->dispatch(new SendNotification($notification->getId()));
                            $io->writeln("  ✓ Re-queued stuck notification ID: {$notification->getId()}");
                        }
                        $io->success("Successfully re-queued " . count($stuckNotifications) . " stuck notifications.");
                    }
                }
            }
        }

        $io->section('Summary');
        $io->writeln("Failed notifications: {$failedCount}");
        $io->writeln("Pending notifications: {$pendingCount}");
        
        if (!empty($stuckNotifications)) {
            $io->writeln("Stuck notifications (>30 min): " . count($stuckNotifications));
        }

        return Command::SUCCESS;
    }
}
