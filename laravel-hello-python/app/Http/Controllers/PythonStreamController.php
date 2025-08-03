<?php

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
        try {
            $rows = DB::table('stream_data')
                ->orderBy('id', 'asc')
                ->limit(300)
                ->get(['id','timestamp','value']);
            return response()->json($rows);
        } catch (\Throwable $e) {
            Log::error('History load failed: '.$e->getMessage());
            return response()->json(
                ['error'=>'Could not load history: '.$e->getMessage()],
                500
            );
        }
    }

    /**
     * Kick off the Python writer in the background and cache its PID.
     */
    public function start(Request $req): JsonResponse
    {
        $script = base_path('tools/stream_writer.py');
        // launch in background and echo its PID
        $cmd = "bash -lc 'nohup python3 ".escapeshellarg($script)." > /dev/null 2>&1 & echo \$!'";
        $res = Process::run(['bash','-lc',$cmd]);

        if ($res->failed()) {
            Log::error('Failed to start stream_writer.py: '.$res->errorOutput());
            return response()->json(
                ['error'=>trim($res->errorOutput())],
                500
            );
        }

        $pid = trim($res->output());
        Cache::put('stream_writer_pid', $pid, now()->addMinutes(10));
        Log::info("Started stream_writer.py with PID {$pid}");

        return response()->json(['started'=>true,'pid'=>$pid]);
    }

    /**
     * Kill the background writer by its cached PID.
     */
    public function stop(Request $req): JsonResponse
    {
        $pid = Cache::pull('stream_writer_pid');

        if (! $pid) {
            return response()->json(['error'=>'No running stream found'], 404);
        }

        $kill = Process::run(['kill','-TERM',$pid]);
        if ($kill->failed()) {
            Log::error("Failed to kill PID {$pid}: ".$kill->errorOutput());
            return response()->json(
                ['error'=>'Could not stop process: '.$kill->errorOutput()],
                500
            );
        }

        Log::info("Stopped stream_writer.py (PID {$pid})");
        return response()->json(['stopped'=>true]);
    }

    /**
     * SSE endpoint: polls DB for newest row ~5×/sec and pushes it.
     */
    public function stream(): StreamedResponse
    {
        return response()->stream(function(){
            $lastId = null;
            while (true) {
                $row = DB::table('stream_data')
                    ->latest('id')
                    ->first(['id','timestamp','value']);
                if ($row && $row->id !== $lastId) {
                    echo "data: ".json_encode($row)."\n\n";
                    @ob_flush(); @flush();
                    $lastId = $row->id;
                }
                usleep(200_000);
            }
        }, 200, [
            'Content-Type'=>'text/event-stream',
            'Cache-Control'=>'no-cache',
            'Connection'=>'keep-alive',
        ]);
    }
}
