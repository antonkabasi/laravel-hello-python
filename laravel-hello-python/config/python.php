<?php

return [
    'binary'   => env('PYTHON_BIN', 'python3'),
    'tools_dir'=> env('PYTHON_TOOLS_DIR', base_path('tools')),
    'pids'     => [
        'dir' => storage_path('app/pids'),
        'stream_writer' => storage_path('app/pids/stream_writer.pid'),
    ],
];
