<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Karabin\Fabriq\Models\User;
use Karabin\Fabriq\Tests\AdminUserTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;

class UserFeatureTest extends AdminUserTestCase
{
    /** @test */
    public function it_can_create_a_user_from_the_command()
    {
        config(['auth.providers.users.model' => User::class]);

        $this->artisan('fabriq:make-user')
            ->expectsQuestion("What is the new user's email address?", 'new@example.com')
            ->expectsQuestion("What is the new user's name?", 'New User')
            ->expectsQuestion("What is the new user's password? (blank generates a random one)", 'password')
            ->expectsConfirmation('Should the password be encrypted?', 'yes')
            ->expectsConfirmation('Do you want to send a password reset email?', 'no')
            ->expectsQuestion('Do you have any custom user fields to add? Field=Value (blank continues)', false)
            ->assertSuccessful()
            ->run();

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'name' => 'New User']);
    }

    #[DataProvider('invalidCommandEmails')]
    public function test_make_user_rejects_invalid_emails(string $email, string|false $customField, string $failedRule)
    {
        config(['auth.providers.users.model' => User::class]);
        $userCount = User::count();

        $command = $this->artisan('fabriq:make-user')
            ->expectsQuestion("What is the new user's email address?", $email)
            ->expectsQuestion("What is the new user's name?", 'New User')
            ->expectsQuestion("What is the new user's password? (blank generates a random one)", 'password')
            ->expectsConfirmation('Should the password be encrypted?', 'yes')
            ->expectsConfirmation('Do you want to send a password reset email?', 'no')
            ->expectsQuestion('Do you have any custom user fields to add? Field=Value (blank continues)', $customField);

        if ($customField !== false) {
            $command->expectsQuestion('Do you have any custom user fields to add? Field=Value (blank continues)', false);
        }

        try {
            $command->run();
            $this->fail('Expected email validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($failedRule, $exception->validator->failed()['email']);
        }

        $this->assertSame($userCount, User::count());
    }

    public static function invalidCommandEmails(): array
    {
        return [
            'empty' => ['', false, 'Required'],
            'malformed' => ['not-an-email', false, 'Email'],
            'duplicate' => ['albin@infab.io', false, 'Unique'],
            'custom field override' => ['new@example.com', 'email=not-an-email', 'Email'],
        ];
    }

    /** @test **/
    public function it_can_get_all_users()
    {
        // Arrange
        $users = User::factory()->count(3)->create();

        // Act
        $response = $this->json('GET', '/users');

        // Assert
        $response->assertOk();
        // 4 because the requester is one as well
        $response->assertJsonCount(4, 'data');
    }

    /** @test **/
    public function it_can_sort_users()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $user = User::factory()->create([
            'name' => 'Zebra Green',
        ]);
        $users = User::factory()->count(1)->create();

        // Act
        $response = $this->json('GET', '/users?sort=-name');

        // Assert
        $response->assertOk();
        // 4 because the requester is one as well
        $response->assertJsonCount(3, 'data');
        $this->assertEquals('Zebra Green', $response->json()['data'][0]['name']);
    }

    /** @test **/
    public function it_can_store_a_new_user()
    {
        // Arrange

        // Act
        $response = $this->json('POST', '/users', [
            'name' => 'Ralf Edström',
            'email' => 'ralf@spray.se',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'name' => 'Ralf Edström',
            'email' => 'ralf@spray.se',
            'email_verified_at' => null,
        ]);
    }

    /** @test **/
    public function it_will_validate_input_before_creating_a_user()
    {
        // Arrange
        app()->setLocale('sv');
        $user = User::factory()->create([
            'email' => 'ralf@spray.se',
        ]);

        // Act
        $response = $this->json('POST', '/users', [
            'email' => 'ralf@spray.se',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'name']);
        $response->assertJsonFragment([
            'E-post används redan.',
        ]);
    }

    /** @test **/
    public function it_will_only_allow_admins_to_view_other_users()
    {
        // Arrange
        $this->markTestSkipped('Skipped since there is no auth middleware');
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->json('GET', '/users');

        // Assert
        $response->assertStatus(403);
    }

    /** @test **/
    public function it_can_show_a_single_user()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $adminRole = DB::table('roles')->insert([
            'name' => 'peasant',
            'display_name' => 'Peasant',
            'description' => '',
            'guard_name' => 'web',
        ]);
        $user->assignRole('peasant');

        // Act
        $response = $this->json('GET', '/users/'.$user->id.'?include=roles');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'role_list' => ['peasant'],
        ]);
    }

    /** @test **/
    public function it_can_update_a_user()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        DB::table('roles')->insert([
            'name' => 'editor',
            'display_name' => 'Editor',
            'description' => 'Redaktörer',
            'guard_name' => 'web',
        ]);
        $editorRole = Role::where('name', 'editor')->first();

        // Act
        $response = $this->json('PATCH', '/users/'.$user->id, [
            'name' => 'Alfons Åberg',
            'email' => 'alfons@aaberg.se',
            'role_list' => ['editor'],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'name' => 'Alfons Åberg',
            'email' => 'alfons@aaberg.se',
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $editorRole->id,
            'model_id' => $user->id,
        ]);
    }

    /** @test **/
    public function it_can_search_for_a_specific_user()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Alfons',
        ]);
        $user = User::factory()->create([
            'name' => 'Jörgen',
        ]);

        // Act
        $response = $this->json('GET', '/users?filter[search]=alfons');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Alfons']);
    }

    /** @test **/
    public function it_can_delete_a_user()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        // Act
        $response = $this->json('DELETE', '/users/'.$user->id);

        // Assert
        $response->assertOk();
    }
}
