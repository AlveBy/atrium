<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Laravel\Dusk\Browser;

/**
 * An administrator with a read-only role: the menu has only what the role
 * allows, a record opens without a Save button, and other sections answer 403.
 */
final class PermissionsTest extends AdminTestCase
{
    public function test_an_administrator_sees_only_what_the_role_allows(): void
    {
        $reader = $this->reader(['articles.view']);
        $article = Article::query()->create(['name' => 'Hello', 'code' => 'hello', 'active' => true, 'sort' => 500]);

        $this->browse(function (Browser $browser) use ($reader, $article): void {
            $browser->loginAs($reader, 'admin')
                ->visit('/admin')
                ->waitFor('[data-menu-route="admin.articles.index"]')
                ->assertMissing('[data-menu-route="admin.users.index"]')
                ->assertMissing('[data-menu-route="admin.roles.index"]')
                ->assertMissing('[data-menu-route="admin.sites.index"]')
                ->assertMissing('[data-menu-route="admin.api-tokens.index"]')
                ->click('[data-menu-route="admin.articles.index"]')
                ->waitForText('Hello')
                ->assertMissing('[data-action="create"]')
                ->visit("/admin/articles/{$article->id}/edit")
                ->waitFor('[data-read-only]')
                ->assertMissing('[data-action="save"]')
                ->visit('/admin/users')
                ->assertSee('403')
                ->visit('/admin/articles/create')
                ->assertSee('403');
        });
    }

    public function test_a_superadmin_builds_a_role_and_gives_it_to_an_administrator(): void
    {
        $admin = $this->admin();
        $reader = $this->reader([]);

        $this->browse(function (Browser $browser) use ($admin, $reader): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/roles/create')
                ->waitFor('#role-name')
                ->type('#role-name', 'Editor')
                // Editing needs viewing: it is ticked along.
                ->check('[data-permission="articles.update"]')
                ->assertChecked('[data-permission="articles.view"]')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/roles')
                ->waitFor('[data-role-row]')
                ->assertSee('Editor');

            $role = AdminRole::query()->where('name', 'Editor')->sole();
            $this->assertEqualsCanonicalizing(['articles.update', 'articles.view'], $role->permissions);

            $reader->roles()->sync([$role->id]);

            $browser->logout('admin')
                ->loginAs($reader->fresh(), 'admin')
                ->visit('/admin')
                ->waitFor('[data-menu-route="admin.articles.index"]')
                ->assertMissing('[data-menu-route="admin.roles.index"]');
        });
    }

    /**
     * @param  array<int,string>  $permissions
     */
    private function reader(array $permissions): AdminUser
    {
        $user = AdminUser::query()->create([
            'name' => 'Rita Reader',
            'email' => 'rita@example.com',
            'password' => 'correct-horse',
            'is_active' => true,
        ]);

        $user->roles()->attach(AdminRole::query()->create(['name' => 'Reader', 'permissions' => $permissions]));

        return $user;
    }
}
