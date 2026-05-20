<?php

namespace Flute\Core\Template\Concerns\TemplateCore;

use Flute\Core\Events\AfterRenderEvent;
use Flute\Core\Events\BeforeRenderEvent;
use Illuminate\View\View;
use Throwable;

trait HandlesTemplateRendering
{
    protected function runTemplate(string $path, array $variables, array $mergeData = []): View
    {
        $startRender = microtime(true);
        $path = $this->searchReplacementForInterface($path);

        $params = $this->beforeRenderEvent($path, $variables);

        try {
            $content = $this->blade->make($params->view, $params->variables, $mergeData);
        } catch (Throwable $e) {
            $root = $e;
            while ($root->getPrevious() !== null) {
                $root = $root->getPrevious();
            }

            if ($root !== $e) {
                throw $root;
            }

            throw $e;
        }

        $elapsed = microtime(true) - $startRender;

        \Flute\Core\Template\TemplateRenderTiming::add($params->view, $elapsed);

        return $this->afterRenderEvent($content);
    }

    protected function beforeRenderEvent(string $template, array $variables = []): object
    {
        $event = events()->dispatch(new BeforeRenderEvent($template, $variables), BeforeRenderEvent::NAME);

        return (object) [
            'view' => $event->getView(),
            'variables' => $event->getData() ?? [],
        ];
    }

    protected function afterRenderEvent(View $view): View
    {
        $event = new AfterRenderEvent($view);

        return events()->dispatch($event, AfterRenderEvent::NAME)->getView();
    }
}
