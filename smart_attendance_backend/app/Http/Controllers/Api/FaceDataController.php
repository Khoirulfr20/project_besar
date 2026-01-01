<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            // ✅ CEK JUMLAH SAMPLE YANG SUDAH ADA
            $existingCount = FaceData::where('user_id', $request->user_id)->count();
            
            if ($existingCount >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maksimal 5 sample wajah per user. Silakan hapus sample lama terlebih dahulu.',
                ], 400);
            }

            // ✅ FIXED: Simpan foto dulu ke storage
            $file = $request->file('photo');
            $photoPath = $file->store('faces', 'public');

            // ✅ FIXED: Kirim ke Python API dengan parameter yang benar
            $pythonUrl = config('services.face_api.url') . '/encode';
            $binary = file_get_contents($file->getRealPath());

            Log::info('Sending to Python API', ['url' => $pythonUrl]);

            $response = Http::timeout(15)
                ->attach('image', $binary, 'photo.jpg') // ✅ FIXED: 'image' bukan 'file'
                ->post($pythonUrl);

            Log::info('Python API Response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->failed()) {
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengenali wajah dari server Python: ' . $response->body()
                ], 500);
            }

            // ✅ FIXED: Parse response structure yang benar
            $body = $response->json();
            
            if (!isset($body['success']) || !$body['success']) {
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => $body['message'] ?? 'Encoding gagal'
                ], 400);
            }

            // ✅ FIXED: Ambil dari 'data' object
            if (!isset($body['data']['embedding'])) {
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Embedding tidak ditemukan dalam response'
                ], 500);
            }

            $embedding = $body['data']['embedding'];
            $quality = $body['data']['quality_score'] ?? 1.0;
            $method = $body['data']['method'] ?? 'LBPH';

            // ✅ VALIDASI: Cek ukuran embedding LBPH (harus 40,000)
            $expectedSize = 40000; // 200x200 pixels
            if (count($embedding) !== $expectedSize) {
                Log::warning('Embedding size mismatch', [
                    'expected' => $expectedSize,
                    'actual' => count($embedding)
                ]);
                
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Ukuran embedding tidak valid. Expected: ' . $expectedSize . ', Got: ' . count($embedding)
                ], 500);
            }

            // ✅ FIXED: Simpan sebagai JSON, BUKAN CSV string!
            $encodingJson = json_encode($embedding);

            // Sample number
            $sampleNumber = $existingCount + 1;
            $isPrimary = ($sampleNumber === 1);

            // ✅ CREATE FACE DATA
            $faceData = FaceData::create([
                'user_id' => $request->user_id,
                'face_encoding' => $encodingJson, // ✅ JSON format
                'face_photo' => $photoPath,
                'face_sample_number' => $sampleNumber,
                'quality_score' => $quality,
                'is_primary' => $isPrimary,
                'is_active' => true,
                'face_registered_at' => now(),
                'registration_source' => 'mobile_app'
            ]);

            Log::info('Face data saved successfully', [
                'id' => $faceData->id,
                'user_id' => $request->user_id,
                'sample_number' => $sampleNumber,
                'method' => $method
            ]);

            return response()->json([
                'success' => true,
                'message' => "Sample wajah ke-{$sampleNumber} berhasil ditambahkan menggunakan {$method}!",
                'data' => [
                    'id' => $faceData->id,
                    'sample_number' => $sampleNumber,
                    'total_samples' => $sampleNumber,
                    'method' => $method,
                    'quality_score' => $quality,
                    'is_primary' => $isPrimary,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Face data store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserFaces($userId)
    {
        $faces = FaceData::where('user_id', $userId)
                        ->where('is_active', true)
                        ->orderBy('face_sample_number')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $faces->map(function($face) {
                return [
                    'id' => $face->id,
                    'user_id' => $face->user_id,
                    'photo_url' => $face->full_photo_url,
                    'sample_number' => $face->face_sample_number,
                    'quality_score' => $face->quality_score,
                    'is_primary' => $face->is_primary,
                    'is_active' => $face->is_active,
                    'registered_at' => $face->face_registered_at,
                ];
            })
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
        FaceData::where('user_id', $faceData->user_id)
                ->update(['is_primary' => false]);

        // Set this as primary
        $faceData->is_primary = true;
        $faceData->save();

        Log::info('Primary face updated', [
            'face_id' => $id,
            'user_id' => $faceData->user_id
        ]);

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

        // Delete photo from storage
        if ($faceData->face_photo && Storage::disk('public')->exists($faceData->face_photo)) {
            Storage::disk('public')->delete($faceData->face_photo);
        }

        $userId = $faceData->user_id;
        $faceData->delete();

        // ✅ FIXED: Jika yang dihapus adalah primary, set sample pertama sebagai primary
        $remainingFaces = FaceData::where('user_id', $userId)
                                  ->where('is_active', true)
                                  ->orderBy('face_sample_number')
                                  ->get();

        if ($remainingFaces->count() > 0 && !$remainingFaces->where('is_primary', true)->count()) {
            $firstFace = $remainingFaces->first();
            $firstFace->is_primary = true;
            $firstFace->save();
        }

        Log::info('Face data deleted', ['id' => $id, 'user_id' => $userId]);

        return response()->json([
            'success' => true,
            'message' => 'Face data berhasil dihapus'
        ]);
    }
}