<?php

namespace App\Http\Controllers;

use App\Services\ResetApplicationData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDataController extends Controller
{
    public function reset(Request $request, ResetApplicationData $resetApplicationData): JsonResponse
    {
        $resetApplicationData->handle();

        Log::warning('application_data_reset', [
            'admin_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Semua data aplikasi berhasil direset. User admin tetap dipertahankan.',
        ]);
    }
}
