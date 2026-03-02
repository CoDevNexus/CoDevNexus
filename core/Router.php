<?php
// ============================================================
// core/Router.php — get(), post(), group(), middleware dispatch
// ============================================================

namespace Core;

class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $prevPrefix     = $this->groupPrefix;
        $prevMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = $prevPrefix . $prefix;
        $this->groupMiddleware = array_merge($prevMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix     = $prevPrefix;
        $this->groupMiddleware = $prevMiddleware;
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $fullPath       = $this->groupPrefix . $path;
        $allMiddleware  = array_merge($this->groupMiddleware, $middleware);

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => $allMiddleware,
            'pattern'    => $this->buildPattern($fullPath),
        ];
    }

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request, Response $response): void
    {
        // Chequear modo mantenimiento antes de despachar (excepto admin autenticado)
        $this->checkMaintenance($request, $response);

        $method = $request->method();
        $uri    = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Parámetros de ruta
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Ejecutar middleware
                foreach ($route['middleware'] as $mw) {
                    $mwInstance = new $mw($request, $response);
                    $mwInstance->handle();
                }

                // Resolver controlador@método
                [$class, $action] = explode('@', $route['handler']);
                $controller = new $class($request, $response);
                $controller->$action(...array_values($params));
                return;
            }
        }

        $response->notFound();
    }

    private function checkMaintenance(Request $request, Response $response): void
    {
        $uri = $request->uri();

        // Rutas admin siempre accesibles
        if (str_starts_with($uri, '/admin')) {
            return;
        }

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = 'modo_mantenimiento'");
            $stmt->execute();
            $row  = $stmt->fetch();

            if ($row && $row['valor'] == '1') {
                $stmt2 = $db->prepare("SELECT valor FROM configuracion WHERE clave = 'mantenimiento_mensaje'");
                $stmt2->execute();
                $msg = $stmt2->fetch()['valor'] ?? 'Sitio en mantenimiento.';
                $response->maintenance($msg);
            }
        } catch (\Throwable) {
            // Si la BD no responde, continuar
        }
    }
}
