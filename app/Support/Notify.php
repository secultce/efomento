<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class Notify
{
    protected Collection $targets;

    public function __construct()
    {
        $this->targets = collect();
    }

    public function user(?User $user = null): self
    {
        $this->targets = collect([
            $user ?? Auth::user(),
        ])->filter();

        return $this;
    }

    public function users(iterable $users): self
    {
        $this->targets = collect($users);

        return $this;
    }

    public function allUsers(): self
    {
        $this->targets = User::all();

        return $this;
    }

    public function where(callable $callback): self
    {
        $this->targets = $this->targets->filter($callback);

        return $this;
    }

    protected function reset(): void
    {
        $this->targets = collect();
    }

    public function send(string $message, string $type = 'default', ?string $title = null, array $meta = []): void
    {
        if ($this->targets->isNotEmpty()) {
            Notification::send(
                $this->targets,
                new AppNotification($message, $type, $title, $meta)
            );
        }

        $this->reset();
    }

    public function default(string $message, ?string $title = null, array $meta = []): void
    {
        $this->send($message, 'default', $title, $meta);
    }

    public function success(string $message, ?string $title = null, array $meta = []): void
    {
        $this->send($message, 'success', $title, $meta);
    }

    public function error(string $message, ?string $title = null, array $meta = []): void
    {
        $this->send($message, 'error', $title, $meta);
    }

    public function warning(string $message, ?string $title = null, array $meta = []): void
    {
        $this->send($message, 'warning', $title, $meta);
    }

    public function info(string $message, ?string $title = null, array $meta = []): void
    {
        $this->send($message, 'info', $title, $meta);
    }
}
