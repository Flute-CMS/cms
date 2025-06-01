<div class="right_sidebar__content w-100 mt-2 h-full" id="page-seo-dialog-content">

    <form id="page-seo-form" class="auth-form" hx-swap="morph:outerHTML">
        <input type="hidden" name="route" value="{{ $route }}">
        <x-forms.field class="mb-3">
            <x-forms.label for="page-title" required>@t('page.seo.page_title'):</x-forms.label>
            <x-fields.input name="title" id="page-title" value="{{ $page->title ?? '' }}" required
                placeholder="{{ __('page.seo.page_title') }}" />
            <x-fields.small>{{ __('page.seo.page_title_help') }}</x-fields.small>
        </x-forms.field>

        <x-forms.field class="mb-3">
            <x-forms.label for="page-description">@t('page.seo.description'):</x-forms.label>
            <x-fields.textarea name="description" id="page-description"
                rows="3">{{ $page->description ?? '' }}</x-fields.textarea>
            <x-fields.small>@t('page.seo.description_help')</x-fields.small>
        </x-forms.field>

        <x-forms.field class="mb-3">
            <x-forms.label for="page-keywords">@t('page.seo.keywords'):</x-forms.label>
            <x-fields.input name="keywords" id="page-keywords" value="{{ $page->keywords ?? '' }}"
                placeholder="{{ __('page.seo.keywords') }}" />
            <x-fields.small>@t('page.seo.keywords_help')</x-fields.small>
        </x-forms.field>

        <x-forms.field class="mb-3">
            <x-forms.label for="page-robots">@t('page.seo.robots'):</x-forms.label>
            <x-fields.select name="robots" id="page-robots" :options="[
                'index, follow' => __('page.seo.robots_index_follow'),
                'index, nofollow' => __('page.seo.robots_index_nofollow'),
                'noindex, follow' => __('page.seo.robots_noindex_follow'),
                'noindex, nofollow' => __('page.seo.robots_noindex_nofollow'),
            ]" :value="$page->robots ?? 'index, follow'" />
            <x-fields.small>@t('page.seo.robots_help')</x-fields.small>
        </x-forms.field>

        <x-forms.field class="mb-3">
            <x-forms.label for="page-og-image">@t('page.seo.og_image'):</x-forms.label>
            <x-fields.input name="og_image" id="page-og-image" value="{{ $page->og_image ?? '' }}"
                placeholder="{{ __('page.seo.og_image') }}" />
            <x-fields.small>{{ __('page.seo.og_image_help') }}</x-fields.small>
        </x-forms.field>
    </form>
</div>
