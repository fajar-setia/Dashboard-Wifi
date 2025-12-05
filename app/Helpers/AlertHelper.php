<?php

use Illuminate\Support\Facades\Http;

function getAlertData()
{
    // $api = config('services.onu_api.url');

    // $aps = Http::timeout(8)->get($api)->json() ?? [];

    // $apOffline = [];
    // $newDevices = [];
    // $allMac = [];

    // foreach ($aps as $ap) {
    //     if (($ap['state'] ?? '') === 'offline') {
    //         $apOffline[] = $ap;
    //     }

    //     $clients = $ap['wifiClients']['unknown'] ?? [];

    //     foreach ($clients as $client) {
    //         $mac = strtolower($client['wifi_terminal_mac']);
    //         $allMac[] = $mac;
    //     }
    // }

    // $newDevices = array_slice(array_unique($allMac), 0, 10);

    // return [
    //     'aps' => $aps,
    //     'apOffline' => $apOffline,
    //     'newDevices' => $newDevices,
    //     'alertcount' => count($apOffline) + count($newDevices),
    // ];
}
