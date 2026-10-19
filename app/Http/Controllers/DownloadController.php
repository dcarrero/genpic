<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function download(Request $request, Order $order, string $format, StorageService $storage)
    {
        if (!$order->isPaid()) {
            abort(403, 'Pedido no pagado.');
        }

        if (!$order->download_ready) {
            abort(404, 'Los archivos aún no están listos.');
        }

        $item = $order->items()
            ->whereNotNull('export_paths')
            ->first();

        if (!$item || !isset($item->export_paths[$format])) {
            abort(404, 'Formato no encontrado.');
        }

        $path = $item->export_paths[$format];

        if (!$storage->exists($path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk($storage->disk())->download($path);
    }
}
