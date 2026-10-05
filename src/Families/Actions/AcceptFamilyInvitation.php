<?php

declare(strict_types=1);

namespace Fam\Families\Actions;

use App\Models\User;
use Fam\Contracts\Interfaces\ActionInterface;
use Fam\Families\Enums\MemberTypeName;
use Fam\Families\Exceptions\FamilyInvitationException;
use Fam\Families\Models\Family;
use Fam\Families\Models\FamilyShareLink;
use Fam\Families\Models\MemberType;
use Override;

class AcceptFamilyInvitation implements ActionInterface
{
    public function __construct(
        public FamilyShareLink $shareLink,
        public User $user,
    ) {}

    /**
     * Add the user to the family behind the invitation link as a regular member.
     */
    #[Override]
    public function execute(array $params): Family
    {
        $family = $this->shareLink->family;

        if ($family->user_id === $this->user->id) {
            throw FamilyInvitationException::alreadyOwner();
        }

        if ($family->members()->whereKey($this->user->id)->exists()) {
            throw FamilyInvitationException::alreadyMember();
        }

        $family->members()->attach($this->user->id, [
            'member_type_id' => MemberType::named(MemberTypeName::Member)->id,
            'invited_by' => $family->user_id,
        ]);

        return $family;
    }
}
