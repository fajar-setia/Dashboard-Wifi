<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ConnectedUsers extends Controller
{
    public function index(): Response
    {
        try {
            $response = Http::timeout(10)->get(config('services.onu_api.url'));

            if ($response->failed()) {
                return response()->view('connectedUsers.connectUsers', ['aps' => [], 'error' => 'Gagal mengambil data dari API'], 500);
            }

            $aps = $response->json();

            return response()->view('connectedUsers.connectUsers', compact('aps'));
        } catch (\Exception $e) {
            return response()->view('connectedUsers.connectUsers', ['aps' => [], 'error' => $e->getMessage()], 500);
        }
    }
}
