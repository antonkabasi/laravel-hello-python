<?php
// app/Http/Controllers/PythonStreamController.php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PythonStreamController extends Controller
{
    /**
     * Return last 300 rows for initial chart seed.
     */
    public function history(): JsonResponse
    {
        $rows = DB::table('stream_data')
            ->orderBy('id', 'asc')
            ->limit(300)
            ->get(['id', 'timestamp', 'value']);
        return response()->json($rows);
    }

    /**
     * Kick off the Python writer in the background and cache its PID.
     */
    public function start(Request $req): JsonResponse
    {
        $script = base_path('tools/stream_writer.py');
        // wrap in bash -lc so we can background (&) and echo $!
        $cmd = "python3 {$script} > /dev/null 2>&1 & echo \$!";

        // Use the Laravel Process facade to run the shell command
        $res = Process::run(['bash', '-lc', $cmd]);

        if ($res->failed()) {
            Log::error('Failed to start stream_writer.py: '.$res->errorOutput());
            return response()->json(['error' => trim($res->errorOutput())], 500);
        }

        $pid = trim($res->output());
        Cache::put('stream_writer_pid', $pid, now()->addSeconds(20));

        return response()->json(['started' => true]);
    }

    /**
     * Kill all writer processes immediately.
     */
    public function stop(Request $req): JsonResponse
    {
        // Do a pkill over the script name
        $res = Process::run(['bash', '-lc', 'pkill -f stream_writer.py']);

        if ($res->failed()) {
            Log::error('Failed to pkill stream_writer.py: '.$res->errorOutput());
            return response()->json(['error' => trim($res->errorOutput())], 500);
        }

        // Also clear any cached individual PID just in case
        Cache::forget('stream_writer_pid');

        return response()->json(['stopped' => true]);
    }

    /**
     * SSE endpoint: polls DB for newest row ~5×/sec and pushes it.
     */
    public function stream(): StreamedResponse
    {
        return response()->stream(function () {
            $lastId = null;

            while (true) {
                $row = DB::table('stream_data')
                    ->latest('id')
                    ->first(['id','timestamp','value']);

                if ($row && $row->id !== $lastId) {
                    echo "data: " . json_encode($row) . "\n\n";
                    @ob_flush(); @flush();
                    $lastId = $row->id;
                }

                // sleep 200ms, but only poll DB when needed
                usleep(200_000);
            }
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
        ]);
    }
}
