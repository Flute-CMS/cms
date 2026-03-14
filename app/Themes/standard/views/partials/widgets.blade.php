@if (page()->hasAnyBlocks())
    @php
        $__widgetsHtml = page()->renderAllWidgets();

        // If global layout rendered a Content marker, inject @stack('content') at that position
        if (page()->isGlobalContentRendered() && str_contains($__widgetsHtml, '<!-- __FLUTE_GLOBAL_CONTENT__ -->')) {
            $__stackContent = $__env->yieldPushContent('content');
            if (isset($sections['content'])) {
                $__stackContent .= $sections['content'];
            }
            $__widgetsHtml = str_replace('<!-- __FLUTE_GLOBAL_CONTENT__ -->', $__stackContent, $__widgetsHtml);
        }
    @endphp
    <section class="container mb-4">
        <div class="row">
            <div class="col-md-12">
                <div class="page-widgets" id="page-widgets">
                    {!! $__widgetsHtml !!}
                </div>
            </div>
        </div>
    </section>
@endif
