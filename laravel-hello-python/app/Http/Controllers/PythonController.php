<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class PythonController extends Controller
{
    /**
     * Fire off a script non‐blocking and return a task UUID.
     */
    public function dispatch(string $name): JsonResponse
    {
        $uuid    = Str::uuid()->toString();
        $script  = config('python.tools_dir') . DIRECTORY_SEPARATOR . $name;
        $outFile = storage_path("app/python/{$uuid}.out");
        $errFile = storage_path("app/python/{$uuid}.err");

        // ensure our storage dir exists
        @mkdir(dirname($outFile), 0755, true);

        // background + redirect stdout/stderr
        $cmd = implode(' ', [
          escapeshellcmd(config('python.binary')),
          escapeshellarg($script),
          "> $outFile 2> $errFile & echo $!"
        ]);

        $res = Process::run(['bash','-lc',$cmd]);

        if($res->failed()){
            throw new RuntimeException("Failed to start script: ".$res->errorOutput());
        }

        $pid = trim($res->output());

        // cache task meta for 10 minutes
        Cache::put("python_task:{$uuid}", [
          'pid'     => $pid,
          'outFile' => $outFile,
          'errFile' => $errFile,
        ], now()->addMinutes(10));

        return response()->json(compact('uuid','pid'));
    }

    /**
     * Poll this to see if the task is done and grab stdout / stderr.
     */
    public function status(string $uuid): JsonResponse
    {
        $meta = Cache::get("python_task:{$uuid}");
        if(!$meta){
            return response()->json(['error'=>'Unknown or expired task'], 404);
        }

        // check if process still alive
        $running = Process::run(['bash','-lc',"kill -0 {$meta['pid']} && echo 1 || echo 0"])
                          ->output() === "1\n";

        if($running){
            return response()->json(['status'=>'running']);
        }

        // process has exited → read files
        $out = @file_get_contents($meta['outFile']) ?: '';
        $err = @file_get_contents($meta['errFile']) ?: '';

        // cleanup
        Cache::forget("python_task:{$uuid}");
        @unlink($meta['outFile']);
        @unlink($meta['errFile']);

        return response()->json([
          'status' => 'done',
          'stdout' => $out,
          'stderr' => $err,
        ]);
    }
}
