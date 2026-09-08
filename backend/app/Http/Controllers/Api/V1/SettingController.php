<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(Setting::allSettings(), 'Settings retrieved successfully.');
    }
}
