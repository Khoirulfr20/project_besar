<?php

// ============================================
// File: app/Http/Controllers/Api/FaceDataController.php
// ============================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FaceDataController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'face_encoding' => 'required|string',
            'photo' => 'required|image|max:5120',
            'quality_score' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Store photo
        $photoPath = $request->file('photo')->store('faces', 'public');

        // Get next sample number
        $sampleNumber = FaceData::where('user_id', $request->user_id)->max('face_sample_number') + 1;

        $faceData = FaceData::create([
            'user_id' => $request->user_id,
            'face_encoding' => $request->face_encoding,
            'face_photo' => $photoPath,
            'face_sample_number' => $sampleNumber,
            'quality_score' => $request->quality_score ?? 0,
            'is_primary' => $sampleNumber === 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Face data berhasil disimpan',
            'data' => $faceData
        ], 201);
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

