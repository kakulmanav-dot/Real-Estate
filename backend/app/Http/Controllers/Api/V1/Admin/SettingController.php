<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Setting;

class SettingController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(Setting::allSettings(), 'Settings retrieved successfully.');
    }

    public function update(UpdateSettingsRequest $request)
    {
        foreach ($request->validated() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Setting::flushCache();

        ActivityLog::record('settings.updated', 'Setting', null, $request->validated());

        return $this->success(Setting::allSettings(), 'Settings updated successfully.');
    }
}
