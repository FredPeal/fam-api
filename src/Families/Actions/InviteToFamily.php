<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use App\Models\User;
use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\Models\Family;
use Fam\Families\Models\FamilyShareLink;
use Fam\Families\Notifications\FamilyInvitation;
use Illuminate\Support\Facades\Notification;
use Override;

class InviteToFamily implements ActionInterface
{
    /**
     * @param  array<int, string>  $emails
     */
    public function __construct(
        public Family $family,
        public User $invitedBy,
        public array $emails,
    ) {}

    /**
     * Send the family invitation link to every email address.
     */
    #[Override]
    public function execute(array $params): FamilyShareLink
    {
        $shareLink = $this->family->shareLink
            ?? (new CreateFamilyShareLink($this->family))->execute($params);

        foreach (array_unique($this->emails) as $email) {
            Notification::route('mail', $email)
                ->notify(new FamilyInvitation($this->family, $shareLink, $this->invitedBy));
        }

        return $shareLink;
    }
}
