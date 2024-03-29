<?php

namespace Tests\Feature\Admin;


use App\Skill;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterUsersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function filter_users_by_state_active()
    {
        $activeUser = factory(User::class)->create();
        $inactiveUser = factory(User::class)->state('inactive')->create();

        $response = $this->get('usuarios?state=active');

        $response->assertViewCollection('users')
            ->contains($activeUser)
            ->notContains($inactiveUser);
    }

    /** @test */
    function filter_users_by_state_inactive()
    {
        $activeUser = factory(User::class)->create();
        $inactiveUser = factory(User::class)->state('inactive')->create();

        $response = $this->get('usuarios?state=inactive');

        $response->assertStatus(200);

        $response->assertViewCollection('users')
            ->contains($inactiveUser)
            ->notContains($activeUser);
    }

    /** @test */
    function filter_users_by_role_admin()
    {
        $admin = factory(User::class)
            ->create(['role' => 'admin']);

        $user = factory(User::class)
            ->create(['role' => 'user']);

        $response = $this->get('usuarios?role=admin');

        $response->assertViewCollection('users')
            ->contains($admin)
            ->notContains($user);
    }

    /** @test */
    function filter_users_by_role_user()
    {
        $admin = factory(User::class)
            ->create(['role' => 'admin']);

        $user = factory(User::class)
            ->create(['role' => 'user']);

        $response = $this->get('usuarios?role=user');

        $response->assertViewCollection('users')
            ->contains($user)
            ->notContains($admin);
    }

    /** @test */
    function filter_users_by_skills()
    {
        $php = factory(Skill::class)->create(['name' => 'php']);
        $css = factory(Skill::class)->create(['name' => 'css']);

        $backendDev = factory(User::class)->create();
        $backendDev->skills()->attach($php);

        $fullStackDev = factory(User::class)->create();
        $fullStackDev->skills()->attach([$php->id, $css->id]);

        $frontendDev = factory(User::class)->create();
        $frontendDev->skills()->attach($css);

        $response = $this->get("usuarios?skills[0]={$php->id}&skills[1]={$css->id}");

        $response->assertStatus(200);

        $response->assertViewCollection('users')
            ->contains($fullStackDev)
            ->notContains($backendDev)
            ->notContains($frontendDev);
    }

    /** @test */
    function filter_users_created_from_date()
    {
        $newestUser = factory(User::class)->create([
            'created_at' => '2018-10-02 12:00:00',
        ]);

        $oldestUser = factory(User::class)->create([
            'created_at' => '2018-09-29 12:00:00',
        ]);

        $newUser = factory(User::class)->create([
            'created_at' => '2018-10-01 00:00:00',
        ]);

        $oldUser = factory(User::class)->create([
            'created_at' => '2018-09-30 23:59:59',
        ]);

        $response = $this->get('usuarios?from=01/10/2018');

        $response->assertOk();

        $response->assertViewCollection('users')
            ->contains($newUser)
            ->contains($newestUser)
            ->notContains($oldUser)
            ->notContains($oldestUser);
    }

    /** @test */
    function filter_users_created_to_date()
    {
        $newestUser = factory(User::class)->create([
            'created_at' => '2018-10-02 12:00:00',
        ]);

        $oldestUser = factory(User::class)->create([
            'created_at' => '2018-09-29 12:00:00',
        ]);

        $newUser = factory(User::class)->create([
            'created_at' => '2018-10-01 00:00:00',
        ]);

        $oldUser = factory(User::class)->create([
            'created_at' => '2018-09-30 23:59:59',
        ]);

        $response = $this->get('usuarios?to=30/09/2018');

        $response->assertOk();

        $response->assertViewCollection('users')
            ->contains($oldestUser)
            ->contains($oldUser)
            ->notContains($newestUser)
            ->notContains($newUser);
    }

    /** @test */
    function it_paginates_the_users_filtered_by_php_skill()
    {
        $php = factory(\App\Skill::class)->create(['name' => 'php']);
        $css = factory(\App\Skill::class)->create(['name' => 'css']);

        $phpUsers = factory(\App\User::class, 20)->create();
        $phpUsers->each(function ($user) use ($php) {
            $user->skills()->attach($php->id);
        });

        $cssUsers = factory(\App\User::class, 20)->create();
        $cssUsers->each(function ($user) use ($css) {
            $user->skills()->attach($css->id);
        });

        $response = $this->get("usuarios?skills[0]={$php->id}")
            ->assertStatus(200)
            ->assertViewIs('users.index')
            ->assertViewHas('users')
            ->assertSeeText('php');

        $response = $this->get("usuarios?skills[0]={$php->id}&page=2")
            ->assertStatus(200)
            ->assertViewIs('users.index')
            ->assertViewHas('users')
            ->assertSeeText('php');
    }
}
