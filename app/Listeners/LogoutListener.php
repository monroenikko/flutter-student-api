<?php

namespace App\Listeners;

use Exception;
use App\Traits\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogoutListener
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
    public function handle(Logout $event)
    {
        try {
            $data = [
                'user_id' => $event->user->id,
                'data' => 'User is Successfully Logged-out'
            ];    
            $this->createLog($event->user, $this->request, $data);
        } catch (Exception $e) {
            Log::error($e);
            return false;
        }
        return true;
    }
}
