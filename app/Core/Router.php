<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bộ định tuyến đơn giản để xử lý request GET và POST.
 * Ánh xạ URI tới hàm xử lý (closure hoặc phương thức class).
 */
final class Router
{
    private array $getRoutes = [];
    private array $postRoutes = [];

    /**
     * Đăng ký handler cho route GET.
     *
     * @param string $path Đường dẫn route (ví dụ: '/user')
     * @param callable|array $handler Closure hoặc [ClassName::class, 'methodName']
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    /**
     * Đăng ký handler cho route POST.
     *
     * @param string $path Đường dẫn route
     * @param callable|array $handler Closure hoặc [ClassName::class, 'methodName']
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->postRoutes[$path] = $handler;
    }

    /**
     * Điều phối request hiện tại tới handler phù hợp.
     * Trả về 404 nếu không tìm thấy route.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->extractPath();
        
        // Global CSRF protection for all POST requests (except webhooks and external APIs)
        $csrfExempt = ['/webhook.php', '/webhook', '/check_status.php', '/migrate_contacts.php'];
        $skipCsrf = in_array($path, $csrfExempt, true);
        
        if ($method === 'POST' && !$skipCsrf) {
            $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            if (!Csrf::verify($token)) {
                http_response_code(419);
                echo 'Mã bảo mật không hợp lệ.';
                exit(1);
            }
        }
        $routes = $this->selectRoutes($method);

        if (isset($routes[$path])) {
            $this->executeHandler($routes[$path]);
            return;
        }

        foreach ($routes as $routePath => $handler) {
            $params = $this->matchPath($routePath, $path);
            if ($params !== null) {
                $this->executeHandler($handler, $params);
                return;
            }
        }

        $this->handleNotFound();
    }

    /**
     * So khớp route động kiểu /bookings/{id}.
     */
    private function matchPath(string $routePath, string $path): ?array
    {
        if (!str_contains($routePath, '{')) {
            return null;
        }

        $paramNames = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];
                return '([^/]+)';
            },
            $routePath
        );

        if ($regex === null) {
            return null;
        }

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matched)) {
            return null;
        }

        array_shift($matched);
        $params = [];
        foreach ($paramNames as $index => $name) {
            $value = $matched[$index] ?? null;
            if (is_string($value) && ctype_digit($value)) {
                $params[$name] = (int)$value;
                continue;
            }

            $params[$name] = $value;
        }

        return $params;
    }

    /**
     * Trích xuất đường dẫn request từ URI.
     */
    private function extractPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return $path;
    }

    /**
     * Chọn bảng route tương ứng theo phương thức request.
     */
    private function selectRoutes(string $method): array
    {
        return $method === 'POST' ? $this->postRoutes : $this->getRoutes;
    }

    /**
     * Thực thi handler (closure hoặc phương thức class).
     *
     * @param callable|array $handler
     */
    private function executeHandler($handler, array $params = []): void
    {
        if (is_array($handler)) {
            $this->executeClassMethod($handler, $params);
        } else {
            $handler(...array_values($params));
        }
    }

    /**
     * Thực thi handler dạng phương thức class.
     *
     * @param array $handler [ClassName, 'methodName']
     */
    private function executeClassMethod(array $handler, array $params = []): void
    {
        [$class, $method] = $handler;
        $instance = new $class();
        $instance->$method(...array_values($params));
    }

    /**
     * Xử lý phản hồi 404 Not Found.
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        echo '404 Not Found';
    }
}
