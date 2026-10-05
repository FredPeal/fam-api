<?php

namespace Tests\Feature\Graphql\Mutations;

use App\Models\User;
use Fam\Families\Actions\RenewFamilyShareLink;
use Fam\Families\Models\Family;
use Fam\Families\Notifications\FamilyInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

class FamiliesMutationManagementTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_unauthenticated_error_and_creates_nothing(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createFamily(input: { name: "Lovelace Family" }) { id } }
        ');

        $response->assertGraphQLErrorMessage('Unauthenticated.');
        $this->assertDatabaseCount('families', 0);
    }

    public function test_create_family_stores_the_family_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                createFamily(input: { name: "Lovelace Family", address: "12 Byron Street" }) {
                    uuid name address user { id }
                }
            }
        ');

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.createFamily.name', 'Lovelace Family');
        $response->assertJsonPath('data.createFamily.address', '12 Byron Street');
        $response->assertJsonPath('data.createFamily.user.id', (string) $user->id);
        $uuid = $response->json('data.createFamily.uuid');
        $this->assertTrue(Str::isUuid($uuid), 'Expected createFamily to return a generated uuid.');
        $this->assertDatabaseHas('families', [
            'uuid' => $uuid,
            'user_id' => $user->id,
            'name' => 'Lovelace Family',
            'address' => '12 Byron Street',
        ]);
    }

    public function test_create_family_with_blank_name_returns_validation_error_and_creates_nothing(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { createFamily(input: { name: "" }) { id } }
        ');

        $response->assertGraphQLValidationError('input.name', 'The input.name field is required.');
        $this->assertDatabaseCount('families', 0);
    }

    public function test_update_family_changes_the_owned_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create([
            'name' => 'Old Name',
            'address' => 'Old Address',
        ]);
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                updateFamily(id: $id, input: { name: "New Name", address: "New Address" }) {
                    id name address
                }
            }
        ', ['id' => $family->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.updateFamily', [
            'id' => (string) $family->id,
            'name' => 'New Name',
            'address' => 'New Address',
        ]);
        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'name' => 'New Name',
            'address' => 'New Address',
        ]);
    }

    public function test_update_family_owned_by_another_user_returns_not_found_error_and_changes_nothing(): void
    {
        $otherFamily = Family::factory()->create(['name' => 'Untouched']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                updateFamily(id: $id, input: { name: "Hijacked" }) { id }
            }
        ', ['id' => $otherFamily->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
        $this->assertDatabaseHas('families', ['id' => $otherFamily->id, 'name' => 'Untouched']);
    }

    public function test_delete_family_removes_the_owned_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteFamily(id: $id) }
        ', ['id' => $family->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.deleteFamily', true);
        $this->assertModelMissing($family);
    }

    public function test_delete_family_owned_by_another_user_returns_not_found_error_and_keeps_it(): void
    {
        $otherFamily = Family::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { deleteFamily(id: $id) }
        ', ['id' => $otherFamily->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
        $this->assertModelExists($otherFamily);
    }

    public function test_invite_to_family_emails_the_share_link_to_each_address_and_returns_it(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $family = Family::factory()->for($user)->create();
        $shareLink = $family->shareLink;
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                inviteToFamily(id: $id, emails: ["grace@example.com", "linus@example.com"]) {
                    code full_link
                }
            }
        ', ['id' => $family->id]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.inviteToFamily', [
            'code' => $shareLink->code,
            'full_link' => $shareLink->full_link,
        ]);
        foreach (['grace@example.com', 'linus@example.com'] as $email) {
            Notification::assertSentOnDemand(
                FamilyInvitation::class,
                fn (FamilyInvitation $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === $email
                    && $notification->shareLink->is($shareLink)
                    && $notification->invitedBy->is($user),
            );
        }
        Notification::assertCount(2);
    }

    public function test_invite_to_family_with_invalid_email_returns_validation_error_and_sends_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                inviteToFamily(id: $id, emails: ["not-an-email"]) { code }
            }
        ', ['id' => $family->id]);

        $response->assertGraphQLValidationError('emails.0', 'The emails.0 field must be a valid email address.');
        Notification::assertNothingSent();
    }

    public function test_invite_to_family_owned_by_another_user_returns_not_found_error_and_sends_nothing(): void
    {
        Notification::fake();
        $otherFamily = Family::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                inviteToFamily(id: $id, emails: ["grace@example.com"]) { code }
            }
        ', ['id' => $otherFamily->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
        Notification::assertNothingSent();
    }

    public function test_renew_family_share_link_replaces_the_code_and_keeps_a_single_link(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->for($user)->create();
        $previousCode = $family->shareLink->code;
        Sanctum::actingAs($user);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) {
                renewFamilyShareLink(id: $id) { code full_link }
            }
        ', ['id' => $family->id]);

        $response->assertGraphQLErrorFree();
        $newCode = $response->json('data.renewFamilyShareLink.code');
        $this->assertNotSame($previousCode, $newCode);
        $this->assertSame(
            'http://localhost:3000/families/join/'.$newCode,
            $response->json('data.renewFamilyShareLink.full_link'),
        );
        $this->assertDatabaseCount('families_share_links', 1);
        $this->assertDatabaseMissing('families_share_links', ['code' => $previousCode]);
        $this->assertDatabaseHas('families_share_links', ['families_id' => $family->id, 'code' => $newCode]);
    }

    public function test_renew_family_share_link_owned_by_another_user_returns_not_found_error_and_keeps_the_code(): void
    {
        $otherFamily = Family::factory()->create();
        $previousCode = $otherFamily->shareLink->code;
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($id: ID!) { renewFamilyShareLink(id: $id) { code } }
        ', ['id' => $otherFamily->id]);

        $response->assertGraphQLErrorMessage('Family not found.');
        $this->assertDatabaseHas('families_share_links', ['families_id' => $otherFamily->id, 'code' => $previousCode]);
    }

    public function test_accept_family_invitation_adds_the_user_as_a_member_invited_by_the_owner(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->for($owner)->create(['name' => 'Lovelace Family']);
        $invitee = User::factory()->create(['name' => 'Grace Hopper']);
        Sanctum::actingAs($invitee);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($code: String!) {
                acceptFamilyInvitation(code: $code) { id name members { name } }
            }
        ', ['code' => $family->shareLink->code]);

        $response->assertGraphQLErrorFree();
        $response->assertJsonPath('data.acceptFamilyInvitation', [
            'id' => (string) $family->id,
            'name' => 'Lovelace Family',
            'members' => [['name' => 'Grace Hopper']],
        ]);
        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'user_id' => $invitee->id,
            'invited_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('member_types', ['name' => 'member']);
    }

    public function test_accept_family_invitation_with_unknown_code_returns_not_found_error(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation { acceptFamilyInvitation(code: "does-not-exist") { id } }
        ');

        $response->assertGraphQLErrorMessage('Invitation not found.');
        $this->assertDatabaseCount('family_members', 0);
    }

    public function test_accept_family_invitation_with_renewed_code_rejects_the_old_code(): void
    {
        $family = Family::factory()->create();
        $oldCode = $family->shareLink->code;
        (new RenewFamilyShareLink($family))->execute([]);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($code: String!) { acceptFamilyInvitation(code: $code) { id } }
        ', ['code' => $oldCode]);

        $response->assertGraphQLErrorMessage('Invitation not found.');
        $this->assertDatabaseCount('family_members', 0);
    }

    public function test_owner_cannot_accept_the_invitation_to_their_own_family(): void
    {
        $owner = User::factory()->create();
        $family = Family::factory()->for($owner)->create();
        Sanctum::actingAs($owner);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($code: String!) { acceptFamilyInvitation(code: $code) { id } }
        ', ['code' => $family->shareLink->code]);

        $response->assertGraphQLErrorMessage('You already own this family.');
        $this->assertDatabaseCount('family_members', 0);
    }

    public function test_accepting_the_same_invitation_twice_returns_already_member_error(): void
    {
        $family = Family::factory()->create();
        $invitee = User::factory()->create();
        Sanctum::actingAs($invitee);
        $mutation = /** @lang GraphQL */ '
            mutation ($code: String!) { acceptFamilyInvitation(code: $code) { id } }
        ';
        $this->graphQL($mutation, ['code' => $family->shareLink->code])->assertGraphQLErrorFree();

        $response = $this->graphQL($mutation, ['code' => $family->shareLink->code]);

        $response->assertGraphQLErrorMessage('You are already a member of this family.');
        $this->assertDatabaseCount('family_members', 1);
    }
}
