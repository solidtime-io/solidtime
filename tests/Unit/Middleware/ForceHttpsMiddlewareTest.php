<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ForceHttps;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ForceHttps::class)]
#[UsesClass(ForceHttps::class)]
class ForceHttpsMiddlewareTest extends MiddlewareTestAbstract
{
    private function createTestRoute(): string
    {
        return Route::get('/test-route', function () {
            return [
                'is_secure' => request()->secure(),
            ];
        })->middleware(ForceHttps::class)->uri;
    }

    public function test_if_config_app_force_https_is_true_then_the_request_will_be_modified_to_make_the_app_think_it_was_a_https_request(): void
    {
        // Arrange
        Config::set('app.force_https', true);
        $route = $this->createTestRoute();

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertSuccessful();
        $response->assertJson(['is_secure' => true]);
    }

    public function test_if_config_app_force_https_is_true_then_the_request_will_be_modified_to_make_the_app_think_it_was_a_https_request_even_if_a_load_balancer_says_it_was_a_http_request(): void
    {
        // Arrange
        Config::set('app.force_https', true);
        $route = $this->createTestRoute();

        // Act
        $response = $this->get($route, ['X-Forwarded-Proto' => 'http']);

        // Assert
        $response->assertSuccessful();
        $response->assertJson(['is_secure' => true]);
    }

    public function test_if_config_app_force_https_is_false_then_the_request_will_not_be_modified_to_make_the_app_think_it_was_a_https_request(): void
    {
        // Arrange
        Config::set('app.force_https', false);
        $route = $this->createTestRoute();

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertSuccessful();
        $response->assertJson(['is_secure' => false]);
    }

    public function test_if_config_app_force_https_is_false_then_the_request_will_not_be_modified_but_the_request_can_still_be_https(): void
    {
        // Arrange
        Config::set('app.force_https', false);
        $route = $this->createTestRoute();

        // Act
        $response = $this->get($route, ['X-Forwarded-Proto' => 'https']);

        // Assert
        $response->assertSuccessful();
        $response->assertJson(['is_secure' => true]);
    }
}
