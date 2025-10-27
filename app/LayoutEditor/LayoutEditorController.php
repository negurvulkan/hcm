<?php
declare(strict_types=1);

namespace App\LayoutEditor;

use App\LayoutEditor\Exceptions\TemplateSyntaxException;

class LayoutEditorController
{
    private TemplateEngine $engine;
    private PlaceholderCatalog $catalog;
    private DummyDataProvider $dataProvider;

    public function __construct(?TemplateEngine $engine = null, ?PlaceholderCatalog $catalog = null, ?DummyDataProvider $dataProvider = null)
    {
        $this->engine = $engine ?? new TemplateEngine();
        $this->catalog = $catalog ?? new PlaceholderCatalog();
        $this->dataProvider = $dataProvider ?? new DummyDataProvider();
    }

    public function meta(): array
    {
        return [
            'status' => 'ok',
            'placeholders' => $this->catalog->groups(),
            'dataset' => $this->dataProvider->example(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function render(string $template): array
    {
        try {
            $html = $this->engine->render($template, $this->dataProvider->example());
            return [
                'status' => 'ok',
                'html' => $html,
            ];
        } catch (TemplateSyntaxException $exception) {
            return [
                'status' => 'error',
                'code' => 'SYNTAX_ERROR',
                'message' => $exception->getMessage(),
                'line' => $exception->templateLine(),
                'column' => $exception->templateColumn(),
            ];
        }
    }
}
