<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class VazifaService
{
    public function checkPremiumAccess(string $email): bool
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-AppApiToken' => config('services.vazifa.api_key') ,
        ])->post('https://vazifa.tj/api/checkUserRole', [
            'email' => $email
        ]);
        $user = User::query()->where('email', $email)->first();
        if ($response->successful()) {

            $userData = $response->json();

            if ($userData['tariff'] === 3)
            {
                $user->daily_request_limit = 1000000;
                $user->daily_requests_used = 0;
                $user->state = 'finished';
                $user->save();
            }
            $user->daily_request_limit = 10;
            $user->save();
        }


        $user->daily_request_limit = 10;
        $user->save();

        return true;
    }
}
