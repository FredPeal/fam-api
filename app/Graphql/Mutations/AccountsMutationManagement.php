<?php

declare(strict_types=1);

namespace App\Graphql\Mutations;

use App\Models\User;
use Fam\Accounts\Actions\CreateAccount;
use Fam\Accounts\Actions\DeleteAccount;
use Fam\Accounts\Actions\UpdateAccount;
use Fam\Accounts\DataTransferObject\Account as AccountDto;
use Fam\Accounts\Enums\AccountTypeId;
use Fam\Accounts\Models\Account;
use Fam\Currencies\Models\Currency;
use GraphQL\Error\Error;

final class AccountsMutationManagement
{
    /**
     * @param  array{name: string, account_type: int, currency_code: string, description?: string|null, icon?: string|null, initial_balance?: float|null}  $args
     */
    public function create(mixed $root, array $args): Account
    {
        /** @var User $user */
        $user = auth()->user();

        return (new CreateAccount($this->accountDto($user, $args)))->execute($args);
    }

    /**
     * @param  array{id: int|string, name: string, account_type: int, currency_code: string, description?: string|null, icon?: string|null, initial_balance?: float|null}  $args
     */
    public function update(mixed $root, array $args): Account
    {
        /** @var User $user */
        $user = auth()->user();
        $account = $this->ownedAccount($user, $args['id']);

        return (new UpdateAccount($account, $this->accountDto($user, $args)))->execute($args);
    }

    /**
     * @param  array{id: int|string}  $args
     */
    public function delete(mixed $root, array $args): bool
    {
        /** @var User $user */
        $user = auth()->user();
        $account = $this->ownedAccount($user, $args['id']);

        return (new DeleteAccount($account))->execute($args);
    }

    /**
     * @param  array{name: string, account_type: int, currency_code: string, description?: string|null, icon?: string|null, initial_balance?: float|null}  $args
     */
    private function accountDto(User $user, array $args): AccountDto
    {
        return new AccountDto(
            user: $user,
            name: $args['name'],
            accountTypeId: AccountTypeId::from($args['account_type']),
            currency: Currency::query()->where('code', $args['currency_code'])->firstOrFail(),
            description: $args['description'] ?? null,
            icon: $args['icon'] ?? null,
            initialBalance: (float) ($args['initial_balance'] ?? 0),
        );
    }

    private function ownedAccount(User $user, int|string $id): Account
    {
        return Account::query()
            ->ownedBy($user)
            ->findOr($id, fn () => throw new Error('Account not found.'));
    }
}
