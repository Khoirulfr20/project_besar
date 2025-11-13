<?php
// ============================================
// File: app/Http/Controllers/Api/SettingController.php
// ============================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $query = Setting::query();

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        $settings = $query->get();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function publicSettings()
    {
        $settings = Setting::where('is_public', true)->get();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json',
            'group' => 'required|string',
            'description' => 'nullable|string',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $setting = Setting::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Setting berhasil ditambahkan',
            'data' => $setting
        ], 201);
    }

    public function update(Request $request, $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'sometimes|in:string,integer,boolean,json',
            'group' => 'sometimes|string',
            'description' => 'nullable|string',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $setting->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Setting berhasil diupdate',
            'data' => $setting
        ]);
    }

    public function destroy($key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting tidak ditemukan'
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting berhasil dihapus'
        ]);
    }
}