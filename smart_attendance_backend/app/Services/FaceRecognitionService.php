<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FaceRecognitionService
{
    public function encode($image)
    {
        $url = config('app.face_api_url') . '/encode';

        $response = Http::timeout(15)->attach(
            'image', file_get_contents($image), 'face.jpg'
        )->post($url);

        return $response->json();
    }

    public function recognize($image)
    {
        $url = config('app.face_api_url') . '/recognize';

        $response = Http::timeout(15)->attach(
            'image', file_get_contents($image), 'face.jpg'
        )->post($url);

        return $response->json();
    }
}
