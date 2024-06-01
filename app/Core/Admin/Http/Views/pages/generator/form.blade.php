@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __($title)]),
])

@php
    $hasEditor = false;
@endphp

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url($backUrl) }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t($title)</h2>
            <p>@t($description)</p>
        </div>
    </div>

    <form data-form="{{ $formType }}" data-page="{{ $page }}">
        @csrf
        @foreach ($inputs as $val)
            @if ($val['hidden'] === true)
                <input name="{{ $val['key'] }}" id="{{ $val['key'] }}" type="hidden"
                    @if (!empty($val['value'])) value="{{ $val['value'] }}" @endif class="form-control">
            @else
                <div class="position-relative row form-group @if ($val['type'] === 'editorjs') align-items-start @endif">
                    <div class="col-sm-3 col-form-label @if ($val['required'] === true) required @endif">
                        <label for="{{ $val['key'] }}">
                            @t($val['label'])
                        </label>
                        @if ($val['description'])
                            <small>@t($val['description'])</small>
                        @endif
                    </div>
                    <div class="col-sm-9">
                        @if ($val['type'] === 'checkbox')
                            <input name="{{ $val['key'] }}" role="switch" id="{{ $val['key'] }}" type="checkbox"
                                class="form-check-input" @if (!empty($val['value']) && $val['value'] == true) checked @endif>
                            <label for="{{ $val['key'] }}"></label>
                        @elseif($val['type'] === 'editorjs')
                            <div data-editorjs id="{{ $page }}-{{ $val['key'] }}-{{ $formType }}"></div>

                            @php
                                $hasEditor = true;
                                $editorValue = $val['value'];
                                $editorKey = $val['key'];
                            @endphp
                        @else
                            <input name="{{ $val['key'] }}" id="{{ $val['key'] }}" placeholder="@t($val['label'])"
                                type="{{ $val['type'] ?? 'text' }}" class="form-control"
                                @if (!empty($val['value'])) value="{{ $val['value'] }}" @endif
                                @if ($val['required'] === true) required @endif>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        <div class="position-relative row form-check">
            <div class="col-sm-9 offset-sm-3">
                <button type="submit" data-save class="btn size-m btn--with-icon primary">
                    @t($formType === 'add' ? 'def.add' : 'def.edit')
                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </form>
@endpush


@push('footer')

    @if ($hasEditor)
        <script src="@asset('assets/js/editor/table.js')"></script>
        <script src="@asset('assets/js/editor/alignment.js')"></script>
        <script src="@asset('assets/js/editor/raw.js')"></script>
        <script src="@asset('assets/js/editor/delimiter.js')"></script>
        <script src="@asset('assets/js/editor/embed.js')"></script>
        <script src="@asset('assets/js/editor/header.js')"></script>
        <script src="@asset('assets/js/editor/image.js')"></script>
        <script src="@asset('assets/js/editor/list.js')"></script>
        <script src="@asset('assets/js/editor/marker.js')"></script>

        @if (!empty($editorValue))
            <script data-loadevery>
                window.defaultEditorData['{{ $page }}-{{ $editorKey }}-{{ $formType }}'] = {
                    blocks: {!! $editorValue ?? '[]' !!}
                };
            </script>
        @endif

        <script src="@asset('assets/js/editor.js')"></script>

        @at('Core/Admin/Http/Views/assets/js/editor.js')
    @endif
@endpush
