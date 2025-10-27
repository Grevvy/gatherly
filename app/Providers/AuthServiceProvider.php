<?php

namespace App\Providers;

use App\Models\Channel;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Photo;
use App\Policies\ChannelPolicy;
use App\Policies\MessagePolicy;
use App\Policies\MessageThreadPolicy;
use App\Policies\PhotoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Channel::class => ChannelPolicy::class,
        Message::class => MessagePolicy::class,
        MessageThread::class => MessageThreadPolicy::class,
        Photo::class => PhotoPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('upload-photo', [PhotoPolicy::class, 'upload']);
    }
}