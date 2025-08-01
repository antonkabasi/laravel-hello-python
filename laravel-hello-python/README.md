# laravel-hello-python

> 🚧 **Work in Progress**: This starter kit is under active development and a working example will be included from the second commit.

A lightweight starter kit to jumpstart **Laravel + Inertia + React** projects with seamless Python CLI orchestration. It includes:

- A sample **Laravel Job** & **Controller** using the `Process` façade to run Python scripts, manage timeouts, and parse JSON output.
- Out-of-the-box **queue setup** so long-running scans run in the background.

## Installation

1. **Clone the repo**
   ```bash
   git clone https://github.com/your-org/laravel-hello-python.git
   cd laravel-hello-python
   ```

2. **Install PHP and the Laravel installer:**
    
    /bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
    composer global require laravel/installer

3. **Install Dependencies and key:**

    npm install && npm run build

    composer install
    cp .env.example .env
    php artisan key:generate  

4. **Generate database (SQLITE):** Choose yes to generate SQLite database

    php artisan migrate

5. **Run the app:**

    php artisan serve

    Open application in browser at http://127.0.0.1:8000
   ```

## Directory Structure

```
laravel-hello-python/
├── app/
│   ├── Http/Controllers/PythonController.php
│   ├── Jobs/RunCodeAdvisor.php
│   └── Models/Python.php
└── README.md
```

## Usage


## Customizing the Python Engine


## Testing


## Contributing


## License

MIT © Anton Kabaši
