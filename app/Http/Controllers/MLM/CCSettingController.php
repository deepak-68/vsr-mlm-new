<?php

namespace App\Http\Controllers\MLM;
use App\Http\Controllers\Controller;
use App\Models\CCSetting;
use Illuminate\Http\Request;

class CCSettingController extends Controller
{
    /**
     * Single page for everything - View, Update, Activate/Deactivate, Delete
     */
    public function index()
    {
        $conversionRate = CCSetting::where('key', 'conversion_rate')->first();
        if (!$conversionRate) {
            $conversionRate = CCSetting::create([
                'key' => 'conversion_rate',
                'value' => 1.00,
                'is_active' => true,
            ]);
        }

        return view('admin.pages.mlm.cc-points-settings', compact('conversionRate'));
    }

    public function withdrawalChargeIndex()
    {
        $withdrawalCharge = CCSetting::where('key', 'withdrawal_charge')->first();
        if (!$withdrawalCharge) {
            $withdrawalCharge = CCSetting::create([
                'key' => 'withdrawal_charge',
                'value' => 0.00,
                'is_active' => true,
            ]);
        }

        return view('admin.pages.mlm.withdrawal-charge-settings', compact('withdrawalCharge'));
    }

    public function update(Request $request, CCSetting $ccSetting)
    {
        $validated = $request->validate([
            'value' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $ccSetting->update($validated);

        $label = $ccSetting->key === 'withdrawal_charge' ? 'Withdrawal Charge' : 'CC Point';

        return back()->with('success', "{$label} settings updated successfully.");
    }

    public function destroy(CCSetting $ccSetting)
    {
        $label = $ccSetting->key === 'withdrawal_charge' ? 'Withdrawal Charge' : 'CC Point';
        $ccSetting->delete();
        return back()->with('success', "{$label} setting deleted.");
    }
}