@if (!page()->isEditorDisabled())
    @if (page()->isEditMode())
        @push('footer')
            <script>
                window.editorData = {
                    blocks: {!! json_encode(page()->getBlocks()) !!}
                };
            </script>
            <script src="@asset('assets/js/editor/table.js')"></script>
            <script src="@asset('assets/js/editor/alignment.js')"></script>
            <script src="@asset('assets/js/editor/raw.js')"></script>
            <script src="@asset('assets/js/editor/delimiter.js')"></script>
            <script src="@asset('assets/js/editor/embed.js')"></script>
            <script src="@asset('assets/js/editor/header.js')"></script>
            <script src="@asset('assets/js/editor/image.js')"></script>
            <script src="@asset('assets/js/editor/list.js')"></script>
            <script src="@asset('assets/js/editor/marker.js')"></script>

            {{-- BUG - Remove ALL blocks. --}}
            {{-- <script src="https://cdn.jsdelivr.net/npm/editorjs-undo"></script> --}}

            <script src="@asset('assets/js/editor.js')"></script>

            @at(tt('assets/js/editor.js'))
        @endpush

        <div class="row">
            {{-- <div class="col-md-12">
                <h2 class="editor_title mb-0">@t('def.page_editor') - {{ request()->getPathInfo() }}</h2>
            </div> --}}
            {{-- <div class="col-md-4 ms-auto">
            <button id="saveButton">Сохранить</button>
        </div> --}}
            <div class="col-md-12">
                <div id="editor"></div>
            </div>
        </div>

        <div class="save_container">
            <div class="save_text">@t('def.attention_save')</div>
            <button id="saveButton">@t('def.save')</button>
        </div>
    @else
        <div class="mb-4 editor-content">
            {!! page()->run() !!}
        </div>
    @endif
@endif
