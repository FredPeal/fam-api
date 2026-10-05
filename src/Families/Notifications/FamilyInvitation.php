<?php

declare(strict_types=1);

namespace Fam\Families\Notifications;

use App\Models\User;
use Fam\Families\Models\Family;
use Fam\Families\Models\FamilyShareLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Family $family,
        public FamilyShareLink $shareLink,
        public User $invitedBy,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->invitedBy->name} invited you to {$this->family->name}")
            ->line("{$this->invitedBy->name} invited you to join the family \"{$this->family->name}\".")
            ->action('Join the family', $this->shareLink->full_link)
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
