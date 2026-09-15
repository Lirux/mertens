<?php

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('employees can read assets but cannot open write forms or mutate assets', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create();
    $original = $asset->refresh()->getAttributes();
    $this->actingAs($user);

    foreach (['dashboard', 'assets.index', 'assets.show'] as $routeName) {
        $this->get(route($routeName, $routeName === 'assets.show' ? $asset : []))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('auth.canManageAssets', false));
    }

    $this->get(route('assets.create'))->assertForbidden();
    $this->get(route('assets.edit', $asset))->assertForbidden();
    $this->get(route('assets.maintenance.edit', $asset))->assertForbidden();
    $this->post(route('assets.store'), [])->assertForbidden();
    $this->put(route('assets.update', $asset), ['name' => 'Unerlaubt'])->assertForbidden();
    $this->put(route('assets.maintenance.update', $asset), [])->assertForbidden();
    $this->delete(route('assets.destroy', $asset))->assertForbidden();

    expect($asset->refresh()->getAttributes())->toEqual($original)
        ->and(Asset::query()->count())->toBe(1);
});

test('managers can open write forms and receive the management capability', function () {
    $asset = Asset::factory()->create();
    $this->actingAs(User::factory()->assetManager()->create());

    foreach (['assets.create', 'assets.edit', 'assets.maintenance.edit'] as $routeName) {
        $this->get(route($routeName, $routeName === 'assets.create' ? [] : $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('auth.canManageAssets', true));
    }
});

test('registration and profile updates cannot grant a manager role', function () {
    $this->post(route('register.store'), [
        'name' => 'Mitarbeiter',
        'email' => 'employee@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => User::ROLE_ASSET_MANAGER,
    ])->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'employee@example.com')->firstOrFail();
    expect($user->role)->toBe(User::ROLE_EMPLOYEE);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Neuer Name',
        'email' => $user->email,
        'role' => User::ROLE_ASSET_MANAGER,
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->role)->toBe(User::ROLE_EMPLOYEE);
});

test('the demo seeder creates two verified accounts with distinct roles and working passwords', function () {
    $this->seed();
    $firstIds = User::query()->orderBy('email')->pluck('id')->all();
    $this->seed();

    expect(User::query()->count())->toBe(2)
        ->and(User::query()->orderBy('email')->pluck('id')->all())->toBe($firstIds);

    foreach (['test@example.com' => User::ROLE_ASSET_MANAGER, 'mitarbeiter@example.com' => User::ROLE_EMPLOYEE] as $email => $role) {
        $user = User::query()->where('email', $email)->firstOrFail();
        expect($user->role)->toBe($role)
            ->and($user->hasVerifiedEmail())->toBeTrue()
            ->and(Hash::check('password', $user->password))->toBeTrue();

        $this->post(route('login.store'), ['email' => $email, 'password' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect();
    }
});

test('the administrative command promotes and demotes existing users only', function () {
    $user = User::factory()->create();

    foreach ([User::ROLE_ASSET_MANAGER, User::ROLE_EMPLOYEE] as $role) {
        $this->artisan('users:set-role', ['email' => $user->email, 'role' => $role])->assertSuccessful();
        expect($user->refresh()->role)->toBe($role);
    }

    $this->artisan('users:set-role', ['email' => $user->email, 'role' => 'admin'])->assertFailed();
    $this->artisan('users:set-role', ['email' => 'missing@example.com', 'role' => User::ROLE_ASSET_MANAGER])->assertFailed();
    expect($user->refresh()->role)->toBe(User::ROLE_EMPLOYEE)
        ->and(User::query()->count())->toBe(1);
});

test('the role migration assigns read access without overwriting existing roles', function () {
    $employee = User::factory()->create();
    $manager = User::factory()->assetManager()->create();
    $employee->unset('role');
    $migration = require database_path('migrations/2026_09_15_192555_add_role_to_users_collection.php');
    $migration->up();
    $migration->up();

    expect($employee->refresh()->role)->toBe(User::ROLE_EMPLOYEE)
        ->and($manager->refresh()->role)->toBe(User::ROLE_ASSET_MANAGER);

    $migration->down();
    expect($manager->refresh()->isAssetManager())->toBeFalse();
});
