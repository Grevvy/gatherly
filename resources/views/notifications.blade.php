@php
    use Illuminate\Support\Carbon;
    use App\Models\Community;

    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();
@endphp

<x-layout :title="'Notifications Center'" :communities="$communities">
    <div class="relative">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 via-blue-500/5 to-sky-500/10 blur-3xl transform scale-110"></div>

        <div class="relative max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                        Notifications Center
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Stay on top of everything happening across your communities.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span id="notify-unread-label"
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-xs font-semibold shadow-lg shadow-blue-500/30">
                        <span class="h-2 w-2 rounded-full bg-white/90 animate-pulse"></span>
                        {{ $unreadCount }} unread
                    </span>
                    <div class="flex items-center gap-3">
                        <button id="notify-mark-all"
                            class="px-4 py-2 text-sm font-semibold text-blue-600 bg-white border border-blue-100 rounded-xl shadow-md shadow-blue-500/10 hover:shadow-blue-500/30 hover:-translate-y-0.5 transition">
                            Mark all read
                        </button>
                        <button id="notify-clear-all"
                            class="px-4 py-2 text-sm font-semibold text-red-600 bg-white border border-red-100 rounded-xl shadow-md shadow-red-500/10 hover:shadow-red-500/30 hover:-translate-y-0.5 transition">
                            Clear all
                        </button>
                    </div>
                </div>
            </div>

            @if ($notifications->isEmpty())
                <div
                    class="relative w-full rounded-3xl bg-white/70 backdrop-blur-xl border border-dashed border-blue-200 p-10 text-center shadow-xl shadow-blue-100/40">
                    <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/40 mb-4">
                        <i data-lucide="bell-ring" class="w-7 h-7"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">All caught up!</h2>
                    <p class="text-sm text-gray-500">
                        You don’t have any notifications right now. We’ll let you know when something needs your
                        attention.
                    </p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach ($notifications as $item)
                        @php
                            $isUnread = empty($item['read_at']);
                            $created = $item['created_at'] ? Carbon::parse($item['created_at']) : null;
                            $meta = collect($item['meta'] ?? [])->reject(function ($value, $key) {
                                if (is_null($value) || $value === '') {
                                    return true;
                                }

                                return in_array($key, [
                                    'community_id',
                                    'community_slug',
                                    'community_name',
                                    'membership_id',
                                    'member_id',
                                    'member_name',
                                    'member_avatar',
                                    'post_id',
                                    'author_id',
                                    'author_name',
                                    'author_avatar',
                                    'attendee_id',
                                    'attendee_name',
                                    'attendee_avatar',
                                ]);
                            });
                        @endphp
                        <div
                            class="relative rounded-3xl overflow-hidden transition transform hover:-translate-y-1 {{ $isUnread ? 'shadow-[0_25px_55px_rgba(37,99,235,0.18)]' : 'shadow-[0_10px_30px_rgba(15,23,42,0.08)]' }}"
                            data-notification-card
                            data-notification-id="{{ $item['id'] }}">
                            <div
                                class="absolute inset-0 bg-gradient-to-r {{ $isUnread ? 'from-blue-500/20 via-indigo-500/10 to-transparent' : 'from-slate-200/30 via-slate-100/40 to-transparent' }}">
                            </div>
                            <div class="relative p-6 sm:p-7 lg:p-8 bg-white/90 backdrop-blur">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex-shrink-0 w-12 h-12 rounded-2xl bg-gradient-to-br {{ $isUnread ? 'from-blue-500 to-indigo-500' : 'from-slate-300 to-slate-200' }} text-white flex items-center justify-center shadow-lg shadow-blue-500/20">
                                        <i data-lucide="{{ $item['type'] === 'event_rsvp' ? 'calendar-check' : ($item['type'] === 'member_banned' ? 'shield-alert' : ($item['type'] === 'member_removed' ? 'user-x' : ($item['type'] === 'membership_request' ? 'users-round' : ($item['type'] === 'message' ? 'message-circle' : 'bell')))) }}"
                                            class="w-5 h-5"></i>
                                    </div>

                                    <div class="flex-1 min-w-0 space-y-2">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ $item['title'] ?: 'Notification' }}
                                            </h3>
                                            @if ($created)
                                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">
                                                    {{ $created->diffForHumans() }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="text-sm text-gray-600 leading-relaxed">
                                            {{ $item['body'] ?: 'There is an update waiting for you.' }}
                                        </p>

                                        @if ($meta->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                                @foreach ($meta as $key => $value)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-100">
                                                        <i data-lucide="sparkles" class="w-3 h-3"></i>
                                                        <span>{{ str_replace('_', ' ', ucfirst($key)) }}:
                                                            {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="pt-2 flex flex-wrap items-center gap-3">
                                            @if ($item['url'])
                                                <a href="{{ $item['url'] }}"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/30 hover:shadow-blue-500/40 transition">
                                                    View Details
                                                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                                                </a>
                                            @endif

                                            @if ($isUnread)
                                                <button data-notification-id="{{ $item['id'] }}"
                                                    class="mark-read-btn inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg text-blue-500 bg-blue-50 border border-blue-100 hover:bg-blue-100 transition">
                                                    Mark as read
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-6">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const markAllBtn = document.getElementById('notify-mark-all');
            const csrfToken = document.getElementById('csrf-token')?.value || '';
            const unreadLabel = document.getElementById('notify-unread-label');

            const updateCounters = (count) => {
                if (unreadLabel) {
                    unreadLabel.innerHTML = `
                        <span class="h-2 w-2 rounded-full bg-white/90 animate-pulse"></span>
                        ${count} unread`;
                }

                const badge = document.getElementById('notif-badge');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.textContent = '';
                        badge.classList.add('hidden');
                    }
                }
            };

            const markNotification = async (id, node) => {
                if (!csrfToken) return;

                try {
                    const res = await fetch(`/notifications/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({}),
                        credentials: 'same-origin'
                    });

                    if (res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        const card = node?.closest('[data-notification-card]');
                        card?.classList.remove('shadow-[0_25px_55px_rgba(37,99,235,0.18)]');
                        card?.classList.add('shadow-[0_10px_30px_rgba(15,23,42,0.08)]');
                        card?.classList.add('bg-white');
                        node?.remove();

                        if (typeof payload.unread_count === 'number') {
                            updateCounters(payload.unread_count);
                        }
                    }
                } catch (err) {
                    console.error(err);
                    showToastify('Failed to update notification.', 'error');
                }
            };

            document.querySelectorAll('.mark-read-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-notification-id');
                    markNotification(id, btn);
                });
            });

            const clearAll = async () => {
                if (!csrfToken) return;

                // Use the existing showConfirmToast function from layout.blade.php
                showConfirmToast(
                    'Are you sure you want to clear all notifications? This action cannot be undone.',
                    async () => {
                        try {
                            const res = await fetch('/notifications/clear-all', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                credentials: 'same-origin'
                            });

                            if (res.ok) {
                                showToastify('All notifications have been cleared successfully.', 'success');
                                // Short delay to show the success message before reload
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showToastify('Failed to clear notifications.', 'error');
                            }
                        } catch (err) {
                            console.error(err);
                            showToastify('Failed to clear notifications.', 'error');
                        }
                    },
                    'bg-red-500 hover:bg-red-600',
                    'Clear'
                );
            };

            document.getElementById('notify-clear-all')?.addEventListener('click', clearAll);

            markAllBtn?.addEventListener('click', async () => {
                if (!csrfToken) return;
                markAllBtn.disabled = true;

                try {
                    const res = await fetch('/notifications/read-all', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({}),
                        credentials: 'same-origin'
                    });

                    if (res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        document.querySelectorAll('.mark-read-btn').forEach(btn => btn.remove());
                        document.querySelectorAll('[data-notification-card]').forEach(card => {
                            card.classList.remove('shadow-[0_25px_55px_rgba(37,99,235,0.18)]');
                            card.classList.add('shadow-[0_10px_30px_rgba(15,23,42,0.08)]');
                        });
                        updateCounters(payload.unread_count ?? 0);
                        showToastify('All notifications marked as read.', 'success');
                    } else {
                        showToastify('Failed to mark notifications.', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showToastify('Failed to mark notifications.', 'error');
                } finally {
                    markAllBtn.disabled = false;
                }
            });
        });
    </script>
</x-layout>