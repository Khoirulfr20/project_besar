<?php

// ============================================
// File: app/Http/Controllers/Api/FaceDataController.php
// ============================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FaceDataController extends Controller
{
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'photo' => 'required|image|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

        try {
            // Upload foto sementara ke storage
            $photoPath = $request->file('photo')->store('faces', 'public');
            $absolutePhotoPath = storage_path('app/public/' . $photoPath);

            // === KIRIM FOTO KE PYTHON API ===
            $pythonUrl = env('PYTHON_FACE_API_URL') . '/encode';

            $response = \Http::attach(
                'file', file_get_contents($absolutePhotoPath), basename($absolutePhotoPath)
            )->post($pythonUrl);

            if ($response->failed()) {
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengenali wajah dari server Python'
                ], 500);
            }

            $result = $response->json();
            if (!$result['success']) {
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Encoding gagal'
                ], 400);
            }

            // === SAVE KE DATABASE ===
            $encoding = implode(',', $result['encoding']);
            $quality = $result['quality'];

            $sampleNumber = FaceData::where('user_id', $request->user_id)->max('face_sample_number') + 1;

            $faceData = FaceData::create([
                'user_id' => $request->user_id,
                'face_encoding' => $encoding,
                'face_photo' => $photoPath,
                'face_sample_number' => $sampleNumber,
                'quality_score' => $quality,
                'is_primary' => $sampleNumber === 1,
                'registration_source' => 'admin_panel'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi wajah berhasil',
                'data' => $faceData
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getUserFaces($userId)
    {
        $faces = FaceData::where('user_id', $userId)->active()->get();

        return response()->json([
            'success' => true,
            'data' => $faces
        ]);
    }

    public function setPrimary($id)
    {
        $faceData = FaceData::find($id);

        if (!$faceData) {
            return response()->json([
                'success' => false,
                'message' => 'Face data tidak ditemukan'
            ], 404);
        }

        // Set all user's faces as non-primary
        FaceData::where('user_id', $faceData->user_id)->update(['is_primary' => false]);

        // Set this as primary
        $faceData->is_primary = true;
        $faceData->save();

        return response()->json([
            'success' => true,
            'message' => 'Primary face berhasil diset',
            'data' => $faceData
        ]);
    }

    public function destroy($id)
    {
        $faceData = FaceData::find($id);

        if (!$faceData) {
            return response()->json([
                'success' => false,
                'message' => 'Face data tidak ditemukan'
            ], 404);
        }

        Storage::disk('public')->delete($faceData->face_photo);
        $faceData->delete();

        return response()->json([
            'success' => true,
            'message' => 'Face data berhasil dihapus'
        ]);
    }
}

