<?php

declare(strict_types=1);

namespace App\Graphql\Mutations;

use App\Models\User;
use Carbon\CarbonInterface;
use Fam\Accounts\Models\Account;
use Fam\Categories\Models\Category;
use Fam\Currencies\Models\Currency;
use Fam\Merchants\Models\Merchant;
use Fam\Sources\Models\Source;
use Fam\Transactions\Actions\CreateTransaction;
use Fam\Transactions\Actions\DeleteTransaction;
use Fam\Transactions\Actions\UpdateTransaction;
use Fam\Transactions\DataTransferObject\Transaction as TransactionDto;
use Fam\Transactions\Enums\TransactionType;
use Fam\Transactions\Models\Transaction;
use GraphQL\Error\Error;

/**
 * @phpstan-type TransactionArgs array{account_id: int|string, type: string, amount: float, currency_code: string, occurred_at: CarbonInterface, exchange_rate?: float|null, source_id?: int|string|null, merchant_id?: int|string|null, category_id?: int|string|null, description?: string|null, notes?: string|null}
 */
final class TransactionsMutationManagement
{
    /**
     * @param  TransactionArgs  $args
     */
    public function create(mixed $root, array $args): Transaction
    {
        /** @var User $user */
        $user = auth()->user();

        return (new CreateTransaction($this->transactionDto($user, $args)))->execute($args);
    }

    /**
     * @param  TransactionArgs&array{id: int|string}  $args
     */
    public function update(mixed $root, array $args): Transaction
    {
        /** @var User $user */
        $user = auth()->user();
        $transaction = $this->ownedTransaction($user, $args['id']);

        return (new UpdateTransaction($transaction, $this->transactionDto($user, $args)))->execute($args);
    }

    /**
     * @param  array{id: int|string}  $args
     */
    public function delete(mixed $root, array $args): bool
    {
        /** @var User $user */
        $user = auth()->user();
        $transaction = $this->ownedTransaction($user, $args['id']);

        return (new DeleteTransaction($transaction))->execute($args);
    }

    /**
     * Build the DTO. Every referenced record must be owned by the user, otherwise it is reported as not found.
     *
     * @param  TransactionArgs  $args
     */
    private function transactionDto(User $user, array $args): TransactionDto
    {
        return new TransactionDto(
            user: $user,
            account: Account::query()
                ->ownedBy($user)
                ->findOr($args['account_id'], fn () => throw new Error('Account not found.')),
            type: TransactionType::from($args['type']),
            amount: (float) $args['amount'],
            currency: Currency::query()->where('code', $args['currency_code'])->firstOrFail(),
            occurredAt: $args['occurred_at'],
            exchangeRate: (float) ($args['exchange_rate'] ?? 1),
            source: isset($args['source_id'])
                ? Source::query()
                    ->ownedBy($user)
                    ->findOr($args['source_id'], fn () => throw new Error('Source not found.'))
                : null,
            merchant: isset($args['merchant_id'])
                ? Merchant::query()
                    ->ownedBy($user)
                    ->findOr($args['merchant_id'], fn () => throw new Error('Merchant not found.'))
                : null,
            category: isset($args['category_id'])
                ? Category::query()
                    ->ownedBy($user)
                    ->findOr($args['category_id'], fn () => throw new Error('Category not found.'))
                : null,
            description: $args['description'] ?? null,
            notes: $args['notes'] ?? null,
        );
    }

    private function ownedTransaction(User $user, int|string $id): Transaction
    {
        return Transaction::query()
            ->ownedBy($user)
            ->findOr($id, fn () => throw new Error('Transaction not found.'));
    }
}
