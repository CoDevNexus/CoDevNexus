<?php
// ============================================================
// core/Controller.php — Base: render(), json(), redirect()
// ============================================================

namespace Core;

class Controller
{
    protected Request  $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;
    }

    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Extraer variables para las vistas
        extract($data);

        $viewFile = APP_ROOT . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        // Capturar contenido de la vista
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Renderizar con layout
        $layoutFile = APP_ROOT . '/app/Views/layouts/' . $layout . '.php';
        if ($layout && file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/admin';
        $this->redirect($ref);
    }
}
