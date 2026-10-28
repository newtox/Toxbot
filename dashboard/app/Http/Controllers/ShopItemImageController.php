<?php

namespace App\Http\Controllers;

use App\Models\ShopItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShopItemImageController extends Controller
{
    public function show(ShopItem $background): BinaryFileResponse
    {
        $path = $background->path();
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
