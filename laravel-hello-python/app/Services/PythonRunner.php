<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PythonRunner
{
    public function __construct(
        private string $pythonBin = '',
        private string $toolsDir = '',
    ) {
        $this->pythonBin = $this->pythonBin ?: config('python.binary', 'python3');
        $this->toolsDir  = $this->toolsDir  ?: config('python.tools_dir', base_path('tools'));
    }

    public function run(string $script, array $args = [], int $timeout = 30): array
    {
        // If $script is a bare name, resolve under toolsDir
        if (!str_starts_with($script, '/') && !preg_match('~^\.{1,2}/~', $script)) {
            $script = rtrim($this->toolsDir, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . ltrim($script, DIRECTORY_SEPARATOR);
        }

        if (!is_file($script)) {
            throw new RuntimeException("Script not found: {$script}");
        }

        $command = array_merge([$this->pythonBin, $script], $args);

        $result = Process::timeout($timeout)->run($command);

        if ($result->failed()) {
            Log::error('Python failed', [
                'cmd' => $command,
                'stderr' => $result->errorOutput(),
            ]);
            throw new RuntimeException(trim($result->errorOutput()) ?: 'Python process failed');
        }

        return [
            'stdout' => $result->output(),
            'stderr' => $result->errorOutput(),
            'exit'   => $result->exitCode(),
        ];
    }

    public function start(string $script, array $args = []): InvokedProcess
    {
        $bin  = config('python.binary', 'python3');
        $dir  = rtrim(config('python.tools_dir'), '/');
        $path = $dir . '/' . ltrim($script, '/');

        $cmd = array_merge([$bin, $path], $args);

        // Non-blocking
        return Process::start($cmd);
    }
}
