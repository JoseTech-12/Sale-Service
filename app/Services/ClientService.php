<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClientService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('PRODUCT_SERVICE_UR', 'http://localhost:8002/api');
    }

    public function getClient($id)
    {
        $response = Http::get("{$this->baseUrl}/showClient/{$id}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
