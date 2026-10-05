<?php

declare(strict_types=1);

namespace App\Graphql\Mutations;

use App\Models\User;
use Fam\Families\Actions\AcceptFamilyInvitation;
use Fam\Families\Actions\CreateFamily;
use Fam\Families\Actions\DeleteFamily;
use Fam\Families\Actions\InviteToFamily;
use Fam\Families\Actions\RenewFamilyShareLink;
use Fam\Families\Actions\UpdateFamily;
use Fam\Families\DataTransferObject\Family as FamilyDto;
use Fam\Families\Models\Family;
use Fam\Families\Models\FamilyShareLink;
use GraphQL\Error\Error;

final class FamiliesMutationManagement
{
    /**
     * @param  array{name: string, address?: string|null}  $args
     */
    public function create(mixed $root, array $args): Family
    {
        /** @var User $user */
        $user = auth()->user();

        $familyDto = new FamilyDto(
            user: $user,
            name: $args['name'],
            address: $args['address'] ?? null,
        );

        return (new CreateFamily($familyDto))->execute($args);
    }

    /**
     * @param  array{id: int|string, name: string, address?: string|null}  $args
     */
    public function update(mixed $root, array $args): Family
    {
        /** @var User $user */
        $user = auth()->user();
        $family = $this->ownedFamily($user, $args['id']);

        $familyDto = new FamilyDto(
            user: $user,
            name: $args['name'],
            address: $args['address'] ?? null,
        );

        return (new UpdateFamily($family, $familyDto))->execute($args);
    }

    /**
     * @param  array{id: int|string}  $args
     */
    public function delete(mixed $root, array $args): bool
    {
        /** @var User $user */
        $user = auth()->user();
        $family = $this->ownedFamily($user, $args['id']);

        return (new DeleteFamily($family))->execute($args);
    }

    /**
     * @param  array{id: int|string, emails: array<int, string>}  $args
     */
    public function invite(mixed $root, array $args): FamilyShareLink
    {
        /** @var User $user */
        $user = auth()->user();
        $family = $this->ownedFamily($user, $args['id']);

        return (new InviteToFamily($family, $user, $args['emails']))->execute($args);
    }

    /**
     * @param  array{id: int|string}  $args
     */
    public function renewShareLink(mixed $root, array $args): FamilyShareLink
    {
        /** @var User $user */
        $user = auth()->user();
        $family = $this->ownedFamily($user, $args['id']);

        return (new RenewFamilyShareLink($family))->execute($args);
    }

    /**
     * @param  array{code: string}  $args
     */
    public function acceptInvitation(mixed $root, array $args): Family
    {
        /** @var User $user */
        $user = auth()->user();

        $shareLink = FamilyShareLink::query()
            ->where('code', $args['code'])
            ->firstOr(fn () => throw new Error('Invitation not found.'));

        return (new AcceptFamilyInvitation($shareLink, $user))->execute($args);
    }

    private function ownedFamily(User $user, int|string $id): Family
    {
        return Family::query()
            ->ownedBy($user)
            ->findOr($id, fn () => throw new Error('Family not found.'));
    }
}
