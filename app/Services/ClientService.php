<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ClientService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('CLIENT_SERVICE_URL', 'http://localhost:8001/api');
    }

    public function getClient($id)
    {
        try {
            $token = request()->bearerToken();
            $response = Http::withToken($token)->get("{$this->baseUrl}/showClient/{$id}");


            if ($response->successful()) {
                return $response->json()['client'];
            }

            Log::error("ClientService Error: {$response->status()} - {$response->body()}");
        } catch (\Exception $e) {
            Log::error("ClientService Exception: " . $e->getMessage());
        }

        return null;
    }
}
