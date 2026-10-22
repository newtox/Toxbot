<?php

namespace App\Http\Controllers;

use App\Models\Background;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackgroundImageController extends Controller
{
    public function show(Background $background): BinaryFileResponse
    {
        $path = $background->path();
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
