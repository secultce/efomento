<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/test-forbidden', fn () => abort(403));
        Route::middleware('web')->get('/test-authorization-denied', function () {
            throw new AuthorizationException;
        });
        Route::middleware('api')->get('/api/test-forbidden', fn () => abort(403));
    }

    public function test_guest_receives_forbidden_page(): void
    {
        $this->get('/test-forbidden')
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 403)
                ->where('auth.user', null));
    }

    public function test_authenticated_inertia_request_receives_forbidden_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
                'Accept' => 'text/html, application/xhtml+xml',
            ])
            ->get('/test-authorization-denied')
            ->assertForbidden()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Errors/Error')
            ->assertJsonPath('props.status', 403)
            ->assertJsonPath('props.auth.user.id', $user->id);
    }

    public function test_json_request_keeps_json_error_response(): void
    {
        $this->getJson('/test-forbidden')
            ->assertForbidden()
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('component');
    }

    public function test_api_request_does_not_render_inertia_page(): void
    {
        $this->getJson('/api/test-forbidden')
            ->assertForbidden()
            ->assertJsonMissingPath('component');

        $this->get('/api/test-forbidden')
            ->assertForbidden()
            ->assertHeaderMissing('X-Inertia')
            ->assertDontSee('data-page=', false);
    }

    public function test_missing_page_renders_not_found_for_guest(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 404)
                ->where('auth.user', null));
    }

    public function test_missing_page_retains_authenticated_user_on_inertia_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? '',
                'Accept' => 'text/html, application/xhtml+xml',
            ])
            ->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Errors/Error')
            ->assertJsonPath('props.status', 404)
            ->assertJsonPath('props.auth.user.id', $user->id);
    }

    public function test_missing_resource_renders_not_found_page(): void
    {
        Route::middleware('web')->get('/test-missing-resource', function () {
            User::query()->findOrFail(999999);
        });

        $this->get('/test-missing-resource')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 404));
    }

    public function test_missing_json_and_api_routes_keep_original_error_responses(): void
    {
        foreach (['/this-page-does-not-exist', '/api/this-page-does-not-exist'] as $url) {
            $this->getJson($url)
                ->assertNotFound()
                ->assertJsonStructure(['message'])
                ->assertJsonMissingPath('component');
        }

        $this->get('/api/this-page-does-not-exist')
            ->assertNotFound()
            ->assertHeaderMissing('X-Inertia')
            ->assertDontSee('data-page=', false);
    }
}
