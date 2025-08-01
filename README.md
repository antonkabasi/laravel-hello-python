# laravel-hello-python

Based on the Starter kit for Laravel + Inertia + React projects with integrated Python CLI and live data streaming.

A lightweight boilerplate to orchestrate Python scripts from Laravel, stream and visualize data in real time with React and Chart.js, backed by SQLite.

## Features

- **Python Integration**
  - Run Python scripts (`hello.py`, `plot_sine.py`) via Laravel controllers using Symfony Process.
  - Background Python worker (`stream_writer.py`) writing random data into SQLite.
- **Live Streaming**
  - SSE endpoint (`PythonStreamController@stream`) to serve latest data.
  - React components to manage and visualize streaming data.
- **Database**
  - SQLite with `stream_data` table (id, timestamp, value, created_at, updated_at).
  - Migration provided for `stream_data`.

## Installation

1. **Clone repository**
   ```bash
   git clone https://github.com/antonkabasi/laravel-hello-python.git
   cd laravel-hello-python
   ```
2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```
3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Database migration**
   ```bash
   php artisan migrate
   ```
5. **Build assets**
   ```bash
   npm run build
   ```
6. **Serve application**
   ```bash
   composer run dev
   ```
   Access at `http://127.0.0.1:8000`.

## Python Requirements

This project uses Python 3.x. Install dependencies:

```bash
pip install -r tools/requirements.txt
```

**requirements.txt**:
```
numpy
matplotlib
pillow
```

- Third party licenses included and compatible with the MIT license of this project.

## Controllers

### PythonController

Handles simple Python script execution:

- **handle()**  
  Runs `tools/hello.py` via `python3` and returns its stdout as `output` in JSON.
- **plotSine()**  
  Executes `tools/plot_sine.py`, captures its base64 GIF output as `img` in JSON.

Both methods log errors and return HTTP 500 with an `error` field if the Python script fails.

### PythonStreamController

Manages live streaming:

- **history()**  
  `GET /python-stream/history` — Returns the full `stream_data` table as JSON.
- **start()**  
  `POST /python-stream/start` — Launches `tools/stream_writer.py` in the background to populate the database.
- **stop()**  
  `POST /python-stream/stop` — Kills the background writer process.
- **stream()**  
  `GET /python-stream/stream` — SSE endpoint that pushes the latest database entry continuously.

## React Components

- **PythonRunner** — Executes Python scripts on demand.
- **LiveStreamRunner** — Controls live data streaming, lists raw values, and renders a Chart.js line chart.

## Directory Structure

```
laravel-hello-python/
├── app/Http/Controllers/PythonController.php
├── app/Http/Controllers/PythonStreamController.php
├── database/migrations/0001_01_01_000003_create_stream_data_table.php
├── resources/js/Components/PythonRunner.tsx
├── resources/js/Components/LiveStreamRunner.tsx
├── resources/js/Components/LiveStreamChart.tsx
├── tools/
│   ├── hello.py
│   ├── plot_sine.py
│   ├── stream_writer.py
│   └── requirements.txt
└── README.md
└── requirements.md
```

## License

MIT © Anton Kabaši
