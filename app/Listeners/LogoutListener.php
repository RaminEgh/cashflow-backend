<?php

namespace App\Listeners;

use App\Events\LogoutEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class LogoutListener
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
    public function handle(LogoutEvent $event): void
    {
//        DB::table('user_sessions')->insert([
//            'user_id' => $user->id,
//            'ip_address' => $request->ip(),
//            'user_agent' => $request->userAgent(),
//            'description' => 'خروج',
//            'type' => 1,
//            'last_activity' => now()->timestamp,
//        ]);
    }
}
