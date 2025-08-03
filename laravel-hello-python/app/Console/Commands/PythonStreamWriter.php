<?php

namespace App\Console\Commands;

use App\Services\PythonRunner;
use Illuminate\Console\Command;
/**
 * Command to manage the Python stream writer process.
 * Supports start, stop, and status actions.
 */
class PythonStreamWriter extends Command
{
    protected $signature = 'python:stream-writer {action : start|stop|status}';
    protected $description = 'Manage background Python stream_writer.py process';

    private string $pidFile = 'stream_writer.pid';

    public function handle(PythonRunner $runner): int
    {
        $action = $this->argument('action');

        try {
            return match ($action) {
                'start'  => $this->start($runner),
                'stop'   => $this->stop($runner),
                'status' => $this->status(),
                default  => $this->error('Unknown action') ?: self::INVALID,
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function start(PythonRunner $runner): int
    {
        $pid = $runner->runBackground('tools/stream_writer.py', [], $this->pidFile);
        $this->info("Started stream_writer.py (PID {$pid})");
        return self::SUCCESS;
    }

    private function stop(PythonRunner $runner): int
    {
        $stopped = $runner->stopByPidFile($this->pidFile);
        $this->info($stopped ? 'Stop signal sent (pidfile removed).' : 'No pidfile found.');
        return self::SUCCESS;
    }

    private function status(): int
    {
        $path = storage_path('app/' . $this->pidFile);
        if (!is_file($path)) {
            $this->line('Not running (no pidfile).');
            return self::SUCCESS;
        }

        $pid = trim((string) @file_get_contents($path));
        $this->line($pid !== '' ? "PID in pidfile: {$pid}" : 'Pidfile present but empty.');
        return self::SUCCESS;
    }
}
