<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class ConnectedUsers extends Controller
{
    public function index(Request $request)
    {
        try {
            $response = Http::timeout(10)->get(config('services.onu_api.url'));

            if (! $response->successful()) {
                return view('connectedUsers.connectUsers', [
                    'aps' => collect([]),
                    'error' => 'Gagal mengambil data dari API',
                ]);
            }

            $aps = collect($response->json())->map(function ($ap) {
                $clients = $ap['wifiClients'] ?? [];

                $ap['connected'] =
                    count($clients['5G'] ?? []) +
                    count($clients['2_4G'] ?? []) +
                    count($clients['unknown'] ?? []);

                return $ap;
            });

            /* =======================
             | SEARCH
             ======================= */
            if ($search = $request->get('search')) {
                $aps = $aps->filter(fn ($ap) =>
                    str_contains(strtolower($ap['sn'] ?? ''), strtolower($search)) ||
                    str_contains(strtolower($ap['model'] ?? ''), strtolower($search))
                );
            }

            /* =======================
             | PAGINATION
             ======================= */
            $perPage = $request->get('perPage', 5);
            $page = $request->get('page', 1);

            $aps = new LengthAwarePaginator(
                $aps->forPage($page, $perPage),
                $aps->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return view('connectedUsers.connectUsers', [
                'aps' => $aps,
                'error' => null,
                'search' => $search,
            ]);

        } catch (ConnectionException) {
            return view('connectedUsers.connectUsers', [
                'aps' => collect([]),
                'error' => 'Koneksi ke API timeout',
            ]);
        }
    }
}
