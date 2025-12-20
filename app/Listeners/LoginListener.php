<?php

namespace App\Listeners;

use App\Events\LoginEvent;
use Illuminate\Support\Facades\DB;

class LoginListener
{
    /**
     * Create the event listener.
     */
    protected $request;

    public function __construct()
    {
        $request = $this->request;
    }

    /**
     * Handle the event.
     */
    public function handle(LoginEvent $event): void
    {
        DB::table('user_sessions')->insert([
            'user_id' => $event->user->id,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'description' => 'ورود',
            'type' => 1,
            'last_activity' => now()->timestamp,
        ]);
    }
}
