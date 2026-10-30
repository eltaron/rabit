<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public array $notifications = [];
    public int $unreadCount = 0;
    public bool $isOpen = false;

    protected static string $poll = '30s';

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $this->notifications = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(function ($n) {
                $data = json_decode($n->data ?? '[]', true) ?: [];
                return [
                    'id' => (string) $n->id,
                    'title' => $n->title ?? ($data['title'] ?? 'إشعار'),
                    'message' => $n->message ?? ($data['message'] ?? ''),
                    'url' => $data['url'] ?? null,
                    'icon' => $this->getNotificationIcon((string) $n->type),
                    'priority' => $n->priority ?? 'medium',
                    'is_read' => $n->read_at !== null,
                    'time' => \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans(),
                ];
            })
            ->toArray();

        $this->unreadCount = $user->unreadNotifications()->count();
    }

    public function markAsRead(string $notificationId): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        \Illuminate\Support\Facades\DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', $user->getMorphClass())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    public function deleteNotification(string $notificationId): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $user->notifications()->where('id', $notificationId)->delete();

        $this->loadNotifications();
    }

    /** فتح إشعار: يعلّمه مقروءاً ثم ينتقل لرابطه إن وجد */
    public function open(string $notificationId)
    {
        $this->markAsRead($notificationId);
        $url = collect($this->notifications)->firstWhere('id', $notificationId)['url'] ?? null;
        if ($url) return $this->redirect($url);
    }

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function getNotificationIcon(string $type): string
    {
        return match (true) {
            str_contains($type, 'Workflow') || str_contains($type, 'workflow') => 'fa-arrows-rotate',
            str_contains($type, 'Assign') || str_contains($type, 'assign') => 'fa-user-check',
            str_contains($type, 'Revision') || str_contains($type, 'revision') => 'fa-rotate-left',
            str_contains($type, 'Approv') || str_contains($type, 'approv') => 'fa-circle-check',
            str_contains($type, 'Reject') || str_contains($type, 'reject') => 'fa-circle-xmark',
            str_contains($type, 'report') || str_contains($type, 'Report') => 'fa-chart-column',
            default => 'fa-bell',
        };    }

    public function render()
    {
        return view('livewire.notification-dropdown');
    }
}
