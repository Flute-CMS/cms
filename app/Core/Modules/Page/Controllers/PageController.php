<?php

namespace Flute\Core\Modules\Page\Controllers;

use Exception;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Modules\Page\Services\PageManager;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Validator\FluteValidator;

class PageController extends BaseController
{
    public function offline()
    {
        return view('flute::pages.offline');
    }

    public function seo(FluteRequest $request, PageManager $pageManager)
    {
        $page = $pageManager->getPage($request->input('route'));

        return view('flute::partials.seo-form', [
            'page' => $page,
            'route' => $request->input('route'),
        ]);
    }

    /**
     * Save SEO information for the current page.
     *
     * @param FluteRequest  $request      The request instance.
     * @param PageManager   $pageManager  The page manager service.
     * @param FluteValidator $validator    The validator service.
     */
    public function saveSEO(FluteRequest $request, PageManager $pageManager, FluteValidator $validator)
    {
        $rules = [
            'title' => 'required|string|max-str-len:255',
            'description' => 'nullable|string|max-str-len:500',
            'keywords' => 'nullable|string|max-str-len:500',
            'robots' => 'nullable|string|max-str-len:255',
            'og_image' => 'nullable|string|max-str-len:500',
            'route' => 'required|string|max-str-len:255',
        ];

        if (!$validator->validate($request->input(), $rules)) {
            $errors = collect($validator->getErrors()->getMessages());
            $firstError = $errors->first()[0] ?? 'Invalid input.';

            return $this->json([
                'error' => $firstError,
                'errors' => $errors->toArray(),
            ], 422);
        }

        try {
            $page = Page::findOne(['route' => $request->input('route')]);

            if (!$page) {
                $page = new Page();
            }

            $page->title = $request->input('title');
            $page->description = $request->input('description');
            $page->keywords = $request->input('keywords');
            $page->robots = $request->input('robots');
            $page->og_image = $request->input('og_image');
            $page->route = $request->input('route');

            $page->save();

            $this->toast(__('page.seo.saved'), 'success');

            return response()->htmx()->setTriggers(['close-modal' => 'page-seo-dialog']);
        } catch (Exception $e) {
            logs()->error("Failed to save SEO settings: " . $e->getMessage());
            $this->toast(__('page.seo.error'), 'error');

            return $this->json([
                'error' => __('page.seo.error'),
                'debug' => is_debug() ? [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ] : null,
            ], 500);
        }
    }
}
