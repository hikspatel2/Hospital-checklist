<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller providing view rendering, response formatting,
 * validation, and security check utilities.
 */
abstract class Controller
{
    /**
     * Render an HTML view
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        return View::render($view, $data, $layout);
    }

    /**
     * Return a JSON response
     */
    protected function json(mixed $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    /**
     * Redirect to another URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        Response::redirect($url, $statusCode);
    }

    /**
     * Basic server-side input validation
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && ($value === null || trim((string)$value) === '')) {
                    $errors[$field][] = "The " . str_replace('_', ' ', $field) . " field is required.";
                }

                if (str_starts_with($rule, 'min:') && $value !== null) {
                    $min = (int)substr($rule, 4);
                    if (strlen(trim((string)$value)) < $min) {
                        $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be at least {$min} characters.";
                    }
                }

                if (str_starts_with($rule, 'max:') && $value !== null) {
                    $max = (int)substr($rule, 4);
                    if (strlen(trim((string)$value)) > $max) {
                        $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must not exceed {$max} characters.";
                    }
                }

                if ($rule === 'email' && $value !== null && $value !== '') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be a valid email address.";
                    }
                }

                if ($rule === 'numeric' && $value !== null && $value !== '') {
                    if (!is_numeric($value)) {
                        $errors[$field][] = "The " . str_replace('_', ' ', $field) . " must be numeric.";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Set a flash message
     */
    protected function flash(string $type, string $message): void
    {
        Session::setFlash($type, $message);
    }
}
