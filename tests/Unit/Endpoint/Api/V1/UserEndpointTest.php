<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Weekday;
use App\Mail\VerifyUpdatedEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

class UserEndpointTest extends ApiEndpointTestAbstract
{
    public function test_me_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.users.me'));

        // Assert
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_me_returns_information_about_the_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.me'));

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'id' => $data->user->getKey(),
                'name' => $data->user->name,
                'email' => $data->user->email,
                'profile_photo_url' => $data->user->profile_photo_url,
                'timezone' => $data->user->timezone,
                'week_start' => $data->user->week_start->value,
            ],
        ]);
    }

    public function test_update_changes_user_name_timezone_and_week_start(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'name' => 'Updated Name',
            'timezone' => 'America/New_York',
            'week_start' => Weekday::Sunday->value,
        ]);

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'id' => $data->user->getKey(),
                'name' => 'Updated Name',
                'timezone' => 'America/New_York',
                'week_start' => Weekday::Sunday->value,
            ],
        ]);

        $user = $data->user->fresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertSame(Weekday::Sunday, $user->week_start);
    }

    public function test_update_does_not_change_user_fields_that_are_not_given(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $data->user->name = 'Original Name';
        $data->user->timezone = 'Europe/Vienna';
        $data->user->week_start = Weekday::Monday;
        $data->user->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), []);

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'id' => $data->user->getKey(),
                'name' => 'Original Name',
                'timezone' => 'Europe/Vienna',
                'week_start' => Weekday::Monday->value,
            ],
        ]);

        $user = $data->user->fresh();
        $this->assertSame('Original Name', $user->name);
        $this->assertSame('Europe/Vienna', $user->timezone);
        $this->assertSame(Weekday::Monday, $user->week_start);
    }

    public function test_update_email_stores_pending_email_and_sends_verification_email(): void
    {
        // Arrange
        Mail::fake();
        $data = $this->createUserWithPermission();
        $data->user->email = 'current@example.com';
        $data->user->email_verified_at = now();
        $data->user->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'email' => 'New.Email@Example.com',
        ]);

        // Assert
        $response->assertSuccessful();

        $user = $data->user->fresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('new.email@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        Mail::assertSent(VerifyUpdatedEmailMail::class, function (VerifyUpdatedEmailMail $mail): bool {
            return $mail->hasTo('new.email@example.com') && $mail->email === 'new.email@example.com';
        });
    }

    public function test_resend_email_verification_sends_pending_email_verification_email(): void
    {
        // Arrange
        Mail::fake();
        $data = $this->createUserWithPermission();
        $data->user->pending_email = 'new.email@example.com';
        $data->user->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.users.resend-email-verification', $data->user->getKey()));

        // Assert
        $response->assertNoContent();
        Mail::assertNotSent(VerifyUpdatedEmailMail::class);
        Mail::assertQueued(VerifyUpdatedEmailMail::class, function (VerifyUpdatedEmailMail $mail): bool {
            return $mail->hasTo('new.email@example.com') && $mail->email === 'new.email@example.com';
        });
    }

    public function test_resend_email_verification_fails_if_given_id_is_not_the_authenticated_user(): void
    {
        // Arrange
        Mail::fake();
        $data = $this->createUserWithPermission();
        $otherData = $this->createUserWithPermission();
        Passport::actingAs($otherData->user);

        // Act
        $response = $this->postJson(route('api.v1.users.resend-email-verification', $data->user->getKey()));

        // Assert
        $response->assertForbidden();
        Mail::assertNotSent(VerifyUpdatedEmailMail::class);
        Mail::assertNotQueued(VerifyUpdatedEmailMail::class);
    }

    public function test_resend_email_verification_fails_without_pending_email(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $data->user->pending_email = null;
        $data->user->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.users.resend-email-verification', $data->user->getKey()));

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'error' => true,
            'key' => 'user_resend_email_verification_no_pending_email',
            'message' => 'Resend email not possible, no pending email.',
        ]);
        Mail::assertNotSent(VerifyUpdatedEmailMail::class);
        Mail::assertNotQueued(VerifyUpdatedEmailMail::class);
    }

    public function test_update_changes_user_photo_from_base64_encoded_image(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $photoDisk = (string) config('jetstream.profile_photo_disk', 'public');
        $previousPhotoPath = 'profile-photos/previous.png';
        $photo = file_get_contents(resource_path('testfiles/test.png'));
        $this->assertIsString($photo);
        Storage::fake($photoDisk);
        Storage::disk($photoDisk)->put($previousPhotoPath, 'previous photo');
        $data->user->profile_photo_path = $previousPhotoPath;
        $data->user->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'photo' => base64_encode($photo),
        ]);

        // Assert
        $response->assertSuccessful();

        $user = $data->user->fresh();
        $this->assertNotNull($user->profile_photo_path);
        $this->assertNotSame($previousPhotoPath, $user->profile_photo_path);
        $this->assertStringStartsWith('profile-photos/', $user->profile_photo_path);
        $this->assertStringEndsWith('.png', $user->profile_photo_path);
        Storage::disk($photoDisk)->assertExists($user->profile_photo_path);
        Storage::disk($photoDisk)->assertMissing($previousPhotoPath);
        $this->assertSame($photo, Storage::disk($photoDisk)->get($user->profile_photo_path));
    }

    public function test_update_fails_if_name_is_not_a_string(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'name' => 123,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_update_fails_if_given_user_is_not_the_authenticated_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $otherData = $this->createUserWithPermission();
        Passport::actingAs($otherData->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_fails_if_name_is_too_long(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'name' => str_repeat('a', 256),
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_update_fails_if_timezone_is_invalid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'timezone' => 'not-a-timezone',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['timezone']);
    }

    public function test_update_fails_if_week_start_is_invalid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'week_start' => 'not-a-weekday',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['week_start']);
    }

    public function test_update_fails_if_photo_is_not_a_string(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'photo' => 123,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['photo']);
    }

    public function test_update_fails_if_photo_is_not_base64_encoded(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'photo' => 'not base64 encoded',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['photo']);
    }

    public function test_update_fails_if_photo_is_not_a_jpg_or_png(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $csv = file_get_contents(resource_path('testfiles/generic_projects_import_test_1.csv'));
        $this->assertIsString($csv);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.users.update', $data->user->getKey()), [
            'photo' => base64_encode($csv),
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['photo']);
    }

    public function test_delete_fails_if_given_user_is_not_the_authenticated_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $otherData = $this->createUserWithPermission();
        Passport::actingAs($otherData->user);

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', $data->user->getKey()));

        // Assert
        $response->assertForbidden();
    }

    public function test_delete_fails_if_not_authenticated(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', $data->user->getKey()));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_delete_fails_if_user_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', 'not-valid'));

        // Assert
        $response->assertNotFound();
    }

    public function test_delete_removes_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', $data->user->getKey()));

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing(User::class, ['id' => $data->user->getKey()]);
    }
}
