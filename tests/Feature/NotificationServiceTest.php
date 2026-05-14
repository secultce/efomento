<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);
    }

    private function createNotification(
        User $user,
        array $attributes = []
    ): DatabaseNotification {
        return DatabaseNotification::create(array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test notification'],
            'read_at' => null,
        ], $attributes));
    }

    #[Test]
    public function test_it_gets_paginated_notifications(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 10) as $i) {
            $this->createNotification($user);
        }

        $result = $this->service->getUserNotifications(
            user: $user,
            pagination: 5
        );

        $this->assertCount(5, $result['userNotifications']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['pageCount']);
        $this->assertEquals(10, $result['total']);
        $this->assertEquals(10, $result['unreadNotificationsCount']);
    }

    #[Test]
    public function test_it_filters_unread_notifications(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user, [
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'read_at' => now(),
        ]);

        $result = $this->service->getUserNotifications(
            user: $user,
            filters: ['read' => false]
        );

        $this->assertCount(1, $result['userNotifications']);
        $this->assertNull($result['userNotifications'][0]->read_at);
    }

    #[Test]
    public function test_it_filters_read_notifications(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user, [
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'read_at' => now(),
        ]);

        $result = $this->service->getUserNotifications(
            user: $user,
            filters: ['read' => true]
        );

        $this->assertCount(1, $result['userNotifications']);
        $this->assertNotNull($result['userNotifications'][0]->read_at);
    }

    #[Test]
    public function test_it_filters_notifications_by_type(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user, [
            'type' => 'App\Notifications\OrderNotification',
        ]);

        $this->createNotification($user, [
            'type' => 'App\Notifications\MessageNotification',
        ]);

        $result = $this->service->getUserNotifications(
            user: $user,
            filters: [
                'type' => 'App\Notifications\OrderNotification',
            ]
        );

        $this->assertCount(1, $result['userNotifications']);

        $this->assertEquals(
            'App\Notifications\OrderNotification',
            $result['userNotifications'][0]->type
        );
    }

    #[Test]
    public function test_it_gets_unread_count(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->createNotification($user, [
                'read_at' => null,
            ]);
        }

        foreach (range(1, 2) as $i) {
            $this->createNotification($user, [
                'read_at' => now(),
            ]);
        }

        $count = $this->service->getUnreadCount($user);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function test_it_marks_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $this->createNotification($user, [
            'read_at' => null,
        ]);

        $result = $this->service->markAsRead(
            user: $user,
            notificationId: $notification->id
        );

        $notification->refresh();

        $this->assertTrue($result);
        $this->assertNotNull($notification->read_at);
    }

    #[Test]
    public function test_it_returns_false_when_notification_does_not_exist(): void
    {
        $user = User::factory()->create();

        $result = $this->service->markAsRead(
            user: $user,
            notificationId: (string) Str::uuid()
        );

        $this->assertFalse($result);
    }

    #[Test]
    public function test_it_marks_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->createNotification($user, [
                'read_at' => null,
            ]);
        }

        $this->service->markAllAsRead($user);

        $this->assertEquals(
            0,
            $user->fresh()->unreadNotifications()->count()
        );
    }

    #[Test]
    public function test_it_does_not_change_already_read_notification(): void
    {
        $user = User::factory()->create();

        $readAt = Carbon::now()->subDay();

        $notification = $this->createNotification($user, [
            'read_at' => $readAt,
        ]);

        $this->service->markAsRead(
            user: $user,
            notificationId: $notification->id
        );

        $notification->refresh();

        $this->assertEquals(
            $readAt->timestamp,
            $notification->read_at->timestamp
        );
    }
}
