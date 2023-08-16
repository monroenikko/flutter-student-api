<?php

namespace App\Listeners;

use Exception;
use App\Traits\AuditLog;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoginListener
{
    use AuditLog;
    private $request;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Login $event)
    {
        try {
            $data = [
                'user_id' => $event->user->id,
                'data' => 'User is Successfully Logged-in'
            ];

            $this->createLog($event->user, $this->request, $data);
            $playerId = $this->request->player_id ?? null;
            if($playerId)
            {
                $isSubscribed = Subscription::where('subscribable_id', $event->user->id)
                    ->where('subscribable_type', get_class($event->user))
                    ->where('player_id', $playerId)
                    ->exists();

                if (!$isSubscribed) {
                    $subscriber = [
                        'player_id' => $playerId
                    ];
                    $event->user->subscribes()->create($subscriber);
                }
            }
            return true;
        } catch (Exception $e) {
            dd($e);
            Log::error($e);
            return false;
        }
        return true;
    }
}
