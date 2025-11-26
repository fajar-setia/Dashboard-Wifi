<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessPointController extends Controller
{
    public function index()
    {
        // Data dummy untuk contoh
        $accessPoint = (object) [
            'name' => 'AP - 01',
            'status' => 'Online',
            'ip_address' => '192.168.100.1',
            'mac_address' => 'AA:BB:CC:DD:EE',
            'channel' => '6'
        ];

        return view('accessPoint.accessPoint', compact('accessPoint'));
    }
}