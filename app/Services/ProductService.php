<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClientService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('CLIENT_SERVICE_URL', 'http://localhost:8002/api');
    }

    public function getClient($id)
    {
        $response = Http::get("{$this->baseUrl}/showProduct/{$id}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
