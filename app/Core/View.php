<?php
declare(strict_types=1);

namespace App\Core;

/**
 * View template engine supporting layouts, data extraction, and nested views
 */
class View
{
    private static string $viewsPath;

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function getViewsPath(): string
    {
        if (!isset(self::$viewsPath)) {
            self::$viewsPath = dirname(__DIR__) . '/Views';
        }
        return self::$viewsPath;
    }

    /**
     * Render a view file with optional data and layout
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        $viewPath = self::getViewsPath() . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file '{$viewPath}' not found.", 500);
        }

        // Shared view data
        $sharedData = [
            'appName' => App::getInstance()->getConfig('app.name', 'Sheth L.G. General Hospital'),
            'appSubtitle' => App::getInstance()->getConfig('app.subtitle', 'Multi-Floor Inspection & Complaint Facility Management'),
            'appUrl' => App::getInstance()->getConfig('app.url', ''),
            'currentPath' => Request::capture()->getPath(),
        ];

        $mergedData = array_merge($sharedData, $data);

        // Render inner view
        extract($mergedData, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // If layout specified, render with layout
        if ($layout !== null) {
            $layoutPath = self::getViewsPath() . '/' . str_replace('.', '/', $layout) . '.php';
            if (file_exists($layoutPath)) {
                $mergedData['content'] = $content;
                extract($mergedData, EXTR_SKIP);
                ob_start();
                require $layoutPath;
                $content = ob_get_clean();
            }
        }

        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
