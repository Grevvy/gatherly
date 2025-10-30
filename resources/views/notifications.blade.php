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
            @php
                $isSnoozed = $snoozedUntil && $snoozedUntil->isFuture();
                $snoozeHuman = $isSnoozed ? $snoozedUntil->diffForHumans() : null;
            @endphp

            @php
                $preferenceOptions = [
                    'posts' => [
                        'label' => 'Posts',
                        'on_classes' => 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white border-transparent shadow-md',
                    ],
                    'events' => [
                        'label' => 'Events',
                        'on_classes' => 'bg-gradient-to-r from-emerald-500 to-cyan-500 text-white border-transparent shadow-md',
                    ],
                    'photos' => [
                        'label' => 'Photos',
                        'on_classes' => 'bg-gradient-to-r from-purple-500 to-fuchsia-500 text-white border-transparent shadow-md',
                    ],
                    'memberships' => [
                        'label' => 'Members',
                        'on_classes' => 'bg-gradient-to-r from-amber-500 to-orange-500 text-white border-transparent shadow-md',
                    ],
                ];
            @endphp

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

            <div class="bg-white/90 border border-blue-100 rounded-3xl shadow-[0_15px_45px_rgba(37,99,235,0.08)] p-6 sm:p-8 space-y-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Notification Preferences
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Choose what you’d like to hear about from each community.
                        </p>
                        <p data-snooze-info
                            class="text-xs text-amber-600 font-medium mt-2 {{ $isSnoozed ? '' : 'hidden' }}">
                            @if ($isSnoozed && $snoozeHuman)
                                Snoozed until {{ $snoozedUntil->format('M j, g:i A') }} ({{ $snoozeHuman }}).
                            @else
                                Snoozed for 24 hours.
                            @endif
                        </p>
                    </div>
                    <button id="notif-snooze-toggle"
                        data-state="{{ $isSnoozed ? 'on' : 'off' }}"
                        data-on-classes="bg-amber-500 text-white shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50"
                        data-off-classes="bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                            {{ $isSnoozed
                                ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50'
                                : 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50' }}">
                        <i data-lucide="{{ $isSnoozed ? 'bell-off' : 'bell-minus' }}" class="w-3.5 h-3.5"></i>
                        <span>{{ $isSnoozed ? 'Resume notifications' : 'Snooze all for 24h' }}</span>
                    </button>
                </div>

                <p data-pref-status
                    class="text-xs font-medium text-gray-400 transition-opacity duration-200 opacity-0">
                    Preferences updated.
                </p>

                @if ($preferenceMemberships->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($preferenceMemberships as $membership)
                            <div class="border border-gray-100 rounded-2xl p-5 bg-white/90 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">
                                            {{ $membership['community_name'] }}
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Toggle the updates you want from this community.
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide rounded-full bg-slate-100 text-slate-500">
                                        {{ ucfirst($membership['role']) }}
                                    </span>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    @foreach ($preferenceOptions as $key => $option)
                                        @php
                                            $enabled = $membership['preferences'][$key] ?? true;
                                        @endphp
                                        <button type="button"
                                            data-pref-toggle
                                            data-community="{{ $membership['community_slug'] }}"
                                            data-key="{{ $key }}"
                                            data-enabled="{{ $enabled ? '1' : '0' }}"
                                            data-on-classes="{{ $option['on_classes'] }}"
                                            data-off-classes="bg-gray-100 text-gray-600 border-gray-200 hover:border-gray-300"
                                            class="pref-toggle inline-flex items-center justify-between gap-2 px-3 py-1.5 rounded-lg border text-xs font-semibold transition
                                                {{ $enabled ? $option['on_classes'] : 'bg-gray-100 text-gray-600 border-gray-200 hover:border-gray-300' }}">
                                            <span class="uppercase tracking-wide text-[11px] font-semibold">{{ $option['label'] }}</span>
                                            <span data-state-text class="text-xs font-medium">
                                                {{ $enabled ? 'On' : 'Muted' }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">
                        Join a community to manage notification preferences.
                    </p>
                @endif
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
                                            <script>
                                                document.querySelector('button[data-notification-id="{{ $item["id"] }}"]')?.addEventListener('click', function() {
                                                    const notifCard = this.closest('[data-notification-card]');
                                                    if (notifCard) {
                                                        notifCard.classList.remove('shadow-[0_25px_55px_rgba(37,99,235,0.18)]');
                                                        notifCard.classList.add('shadow-[0_10px_30px_rgba(15,23,42,0.08)]');
                                                        notifCard.querySelector('.bg-gradient-to-r').classList.remove('from-blue-500/20', 'via-indigo-500/10');
                                                        notifCard.querySelector('.bg-gradient-to-r').classList.add('from-slate-200/30', 'via-slate-100/40');
                                                        const iconDiv = notifCard.querySelector('.rounded-2xl');
                                                        iconDiv.classList.remove('from-blue-500', 'to-indigo-500');
                                                        iconDiv.classList.add('from-slate-300', 'to-slate-200');
                                                        this.remove();
                                                    }
                                                });
                                            </script>
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

            const snoozeBtn = document.getElementById('notif-snooze-toggle');
            const inlineStatus = document.querySelector('[data-pref-status]');
            let statusTimer = null;

            const showInlineStatus = (message, tone = 'info') => {
                if (!inlineStatus) {
                    return;
                }

                inlineStatus.textContent = message;
                inlineStatus.classList.remove('text-gray-400', 'text-emerald-600', 'text-amber-600', 'text-rose-600');

                const toneClass = {
                    info: 'text-gray-400',
                    success: 'text-emerald-600',
                    warning: 'text-amber-600',
                    error: 'text-rose-600',
                }[tone] ?? 'text-gray-400';

                inlineStatus.classList.add(toneClass);
                inlineStatus.classList.remove('opacity-0');
                inlineStatus.classList.add('opacity-100');

                if (statusTimer) {
                    clearTimeout(statusTimer);
                }
                statusTimer = setTimeout(() => {
                    inlineStatus.classList.remove('opacity-100');
                    inlineStatus.classList.add('opacity-0');
                }, 2500);
            };

            const applyToggleClasses = (button, isActive) => {
                if (!button) {
                    return;
                }

                const onClasses = (button.dataset.onClasses || '').split(' ').filter(Boolean);
                const offClasses = (button.dataset.offClasses || '').split(' ').filter(Boolean);

                button.classList.remove(...onClasses, ...offClasses);
                if (isActive) {
                    button.classList.add(...onClasses);
                    button.dataset.enabled = '1';
                } else {
                    button.classList.add(...offClasses);
                    button.dataset.enabled = '0';
                }

                const stateText = button.querySelector('[data-state-text]');
                if (stateText) {
                    stateText.textContent = isActive ? 'On' : 'Muted';
                }
            };

            const updatePrefButtonsForCommunity = (slug, prefs) => {
                document.querySelectorAll(`[data-pref-toggle][data-community="${slug}"]`).forEach(button => {
                    const key = button.dataset.key;
                    if (Object.prototype.hasOwnProperty.call(prefs, key)) {
                        applyToggleClasses(button, !!prefs[key]);
                    }
                });
            };

            document.querySelectorAll('[data-pref-toggle]').forEach(button => {
                button.addEventListener('click', async () => {
                    if (!csrfToken) return;

                    const slug = button.dataset.community;
                    const key = button.dataset.key;
                    const nextValue = !(button.dataset.enabled === '1');

                    button.disabled = true;

                    try {
                        const res = await fetch(`/notifications/preferences/${slug}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ [key]: nextValue })
                        });

                        if (!res.ok) {
                            throw new Error('Request failed');
                        }

                        const payload = await res.json().catch(() => ({}));
                        updatePrefButtonsForCommunity(slug, payload.preferences || { [key]: nextValue });
                        showInlineStatus('Preferences updated.', 'success');
                    } catch (error) {
                        console.error(error);
                        showInlineStatus('Unable to update preferences.', 'error');
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            const updateSnoozeButton = (state, until = null) => {
                if (!snoozeBtn) {
                    return;
                }

                const isOn = state === 'on';
                snoozeBtn.dataset.state = state;

                const onClasses = (snoozeBtn.dataset.onClasses || '').split(' ').filter(Boolean);
                const offClasses = (snoozeBtn.dataset.offClasses || '').split(' ').filter(Boolean);
                snoozeBtn.classList.remove(...onClasses, ...offClasses);
                snoozeBtn.classList.add(...(isOn ? onClasses : offClasses));

                const icon = snoozeBtn.querySelector('i[data-lucide]');
                if (icon) {
                    icon.setAttribute('data-lucide', isOn ? 'bell-off' : 'bell-minus');
                }

                const label = snoozeBtn.querySelector('span');
                if (label) {
                    label.textContent = isOn ? 'Resume notifications' : 'Snooze all for 24h';
                }

                if (window.Lucide) {
                    window.Lucide.createIcons();
                }

                const info = document.querySelector('[data-snooze-info]');
                if (info) {
                    if (isOn && until) {
                        const untilDate = new Date(until);
                        info.textContent = `Snoozed until ${untilDate.toLocaleString()}.`;
                        info.classList.remove('hidden');
                    } else if (isOn) {
                        info.textContent = 'Snoozed for 24 hours.';
                        info.classList.remove('hidden');
                    } else {
                        info.classList.add('hidden');
                    }
                }
            };

            snoozeBtn?.addEventListener('click', async () => {
                if (!csrfToken) return;

                const nextState = snoozeBtn.dataset.state === 'on' ? 'off' : 'on';
                snoozeBtn.disabled = true;

                try {
                    const res = await fetch('/notifications/preferences/snooze', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ state: nextState })
                    });

                    if (!res.ok) {
                        throw new Error('Request failed');
                    }

                    const payload = await res.json().catch(() => ({}));
                    updateSnoozeButton(nextState, payload.snoozed_until ?? null);
                    showInlineStatus(payload.message || 'Preference updated.', 'success');
                } catch (error) {
                    console.error(error);
                    showInlineStatus('Unable to update snooze.', 'error');
                } finally {
                    snoozeBtn.disabled = false;
                }
            });

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
                        // Update UI for all notification cards
                        document.querySelectorAll('[data-notification-card]').forEach(card => {
                            // Update shadow
                            card.classList.remove('shadow-[0_25px_55px_rgba(37,99,235,0.18)]');
                            card.classList.add('shadow-[0_10px_30px_rgba(15,23,42,0.08)]');
                            
                            // Update gradient background
                            const gradient = card.querySelector('.bg-gradient-to-r');
                            gradient.classList.remove('from-blue-500/20', 'via-indigo-500/10');
                            gradient.classList.add('from-slate-200/30', 'via-slate-100/40');
                            
                            // Update icon color
                            const iconDiv = card.querySelector('.rounded-2xl');
                            iconDiv.classList.remove('from-blue-500', 'to-indigo-500');
                            iconDiv.classList.add('from-slate-300', 'to-slate-200');
                            
                            // Remove mark as read button
                            card.querySelector('.mark-read-btn')?.remove();
                        });

                        // Update counters and show success message
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
