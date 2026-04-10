<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Support\Notify;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class NotifyTest extends TestCase
{
    use RefreshDatabase;

    protected Notify $notify;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notify = app(Notify::class);
    }

    #[Test]
    public function it_is_not_a_singleton()
    {
        $instance1 = app(Notify::class);
        $instance2 = app(Notify::class);

        $this->assertNotSame($instance1, $instance2);
    }

    #[Test]
    public function it_does_not_leak_state_between_resolutions()
    {
        Notification::fake();

        [$user1, $user2] = User::factory()->count(2)->create();

        app(Notify::class)
            ->user($user1)
            ->success('First');

        app(Notify::class)
            ->user($user2)
            ->success('Second');

        Notification::assertSentTo($user1, AppNotification::class);
        Notification::assertSentTo($user2, AppNotification::class);
    }

    #[Test]
    public function it_sends_notification_to_a_single_user()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->notify
            ->user($user)
            ->success('Hello world');

        Notification::assertSentTo($user, AppNotification::class);
    }

    #[Test]
    public function it_sends_correct_payload()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->notify
            ->user($user)
            ->success('Test message', 'Title');

        Notification::assertSentTo(
            $user,
            AppNotification::class,
            fn ($notification) =>
                $notification->message === 'Test message' &&
                $notification->type === 'success' &&
                $notification->title === 'Title'
        );
    }

    #[Test]
    public function it_filters_users_correctly()
    {
        Notification::fake();

        [$user1, $user2] = User::factory()->count(2)->create();

        $this->notify
            ->users([$user1, $user2])
            ->where(fn ($user) => $user->id === $user1->id)
            ->success('Filtered');

        Notification::assertSentTo($user1, AppNotification::class);
        Notification::assertNotSentTo($user2, AppNotification::class);
    }

    #[Test]
    public function it_resets_targets_after_sending()
    {
        Notification::fake();

        [$user1, $user2] = User::factory()->count(2)->create();

        $this->notify->user($user1)->success('First');
        $this->notify->user($user2)->success('Second');

        Notification::assertSentTo($user1, AppNotification::class);
        Notification::assertSentTo($user2, AppNotification::class);
    }
}