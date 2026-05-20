<?php

namespace Flute\Core\Template\Concerns\TemplateCore;

use Throwable;

trait HandlesSections
{
    /**
     * Add a stylesheet to the header stack.
     */
    public function addStyle(string $css): void
    {
        if ($this->loadedStyles[$css] ?? false) {
            return;
        }

        $this->prependToSection('head', sprintf("<link rel='stylesheet' href='%s' type='text/css'>", $css));
        $this->loadedStyles[$css] = true;
    }

    public function prependToSection(string $section, string $content): void
    {
        $this->sectionPushes[$section][] = $content;
    }

    public function prependToSectionDeferred(string $section, callable $callback): void
    {
        try {
            $content = $callback();
            $this->prependToSection($section, $content);
        } catch (Throwable $e) {
            logs('templates')->error("Error rendering section '{$section}': " . $e->getMessage());
        }
    }

    public function prependTemplateToSection(string $section, string $template, array $data = []): void
    {
        if (!$this->shouldRenderSection($section)) {
            return;
        }

        $this->prependToSectionDeferred($section, function () use ($template, $data) {
            try {
                return $this->render($template, $data)->render();
            } catch (Throwable $e) {
                logs('templates')->error("Error rendering template '{$template}': " . $e->getMessage());

                return '';
            }
        });
    }

    public function prependYoyoToSection(string $section, string $component, array $data = []): void
    {
        $this->prependToSectionDeferred($section, static function () use ($component, $data) {
            try {
                return \Yoyo\yoyo_render($component, $data);
            } catch (Throwable $e) {
                logs('templates')->error("Error rendering Yoyo component '{$component}': " . $e->getMessage());

                return '';
            }
        });
    }

    public function shouldRenderSection(string $section): bool
    {
        $path = request()->getPathInfo();

        if (strpos($section, 'profile_') === 0 && !str_contains((string) $path, '/profile')) {
            return false;
        }

        return !( strpos($section, 'navbar') === 0 && is_admin_path() );
    }

    public function flushSectionPushes(): void
    {
        foreach ($this->sectionPushes as $section => $content) {
            $this->blade->startPush($section);

            foreach ($content as $item) {
                echo $item;
            }

            $this->blade->stopPush();
        }
    }

    public function addInlineScript(string $scriptContent): void
    {
        $this->prependToSection('footer', sprintf('<script>%s</script>', $scriptContent));
    }

    public function addScript(string $js): void
    {
        if ($this->loadedScripts[$js] ?? false) {
            return;
        }

        $this->prependToSection('footer', sprintf("<script src='%s' defer></script>", $js));
        $this->loadedScripts[$js] = true;
    }
}
