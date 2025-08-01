<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class PythonController extends Controller
{
    public function handle(): JsonResponse
    {
        $script = base_path('tools/hello.py');

        // Run via python3 explicitly
        $result = Process::run(['python3', $script]);

        if ($result->failed()) {
            // log the stderr for debugging
            Log::error("Python failed: " . $result->errorOutput());
            return response()->json([
                'error' => trim($result->errorOutput()),
            ], 500);
        }

        return response()->json([
            'output' => trim($result->output()),
        ]);
    }

    public function plotSine(): JsonResponse
    {
        $script = base_path('tools/plot_sine.py');

        // Run via python3 explicitly
        $result = Process::run(['python3', $script]);

        if ($result->failed()) {
            // log stderr for debugging
            Log::error("plot_sine.py failed: " . $result->errorOutput());
            return response()->json([
                'error' => trim($result->errorOutput()),
            ], 500);
        }

        return response()->json([
            'img' => trim($result->output()),
        ]);
    }

}
