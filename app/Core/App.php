<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application Bootstrap and Dependency Container
 */
class App
{
    private static ?App $instance = null;
    private array $config = [];
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $this->basePath);
        }
        self::$instance = $this;
        $this->registerAutoloader();
        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->bootstrap();
    }

    private function registerAutoloader(): void
    {
        $base = $this->basePath;
        spl_autoload_register(function (string $class) use ($base): void {
            $prefix = 'App\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $base . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('App instance has not been initialized.');
        }
        return self::$instance;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Minimal zero-dependency .env file parser
     */
    private function loadEnvironment(): void
    {
        $envPath = $this->basePath . '/.env';
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Strip quotes if wrapped
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }

    private function loadConfiguration(): void
    {
        $this->config['app'] = require $this->basePath . '/config/app.php';
        $this->config['database'] = require $this->basePath . '/config/database.php';
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $data = $this->config;

        // If top segment isn't 'app' or 'database', default to checking under 'app'
        if (!empty($segments) && !array_key_exists($segments[0], $data) && array_key_exists('app', $data)) {
            array_unshift($segments, 'app');
        }

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    private function bootstrap(): void
    {
        // 1. Timezone constraint: Asia/Kolkata
        $timezone = $this->getConfig('app.timezone', 'Asia/Kolkata');
        date_default_timezone_set($timezone);

        // 2. Error handling initialization
        ErrorHandler::register($this->getConfig('app.debug', false));

        // 3. Initialize secure session
        Session::init($this->getConfig('app.session', []));
    }

    public function run(): void
    {
        $router = new Router();
        require $this->basePath . '/routes/web.php';
        $router->dispatch(Request::capture());
    }
}
