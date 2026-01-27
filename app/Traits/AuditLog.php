<?php

namespace App\Traits;

use App\Models\Audit;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;

trait AuditLog
{
    public function createLog($collection, $request, $data)
    {
        $ip_address = $request->ip();
        $user_agent = $request->header('User-Agent');

        $agent = new Agent();
        
        $devicePlatform = [
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' =>  $agent->device()
        ];

        $log = [
            'ip_address'=> $ip_address,
            'user_agent' => $user_agent,
            'platform' => '',
            'url' => '',
            'method' => $request->method()
        ];

        $mergedData = array_merge($data, $log);

        $data = $collection->audit()->create($mergedData);

        $res = Audit::find($data->id);
        $res->platform = json_encode($devicePlatform);
        $res->url = $request->url();
        $res->save();
    }
}