<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function getNumber()
    {
        $setting = SystemSetting::where('key', 'whatsapp_number')->first();

        return response()->json([
            'status'  => true,
            'number'  => $setting ? $setting->value : null,
        ]);
    }

    public function updateNumber(Request $request)
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
        ]);

        SystemSetting::updateOrCreate(
            ['key' => 'whatsapp_number'],
            ['value' => $request->whatsapp_number]
        );

        return response()->json([
            'status'  => true,
            'message' => 'WhatsApp number updated successfully.',
        ]);
    }
}
