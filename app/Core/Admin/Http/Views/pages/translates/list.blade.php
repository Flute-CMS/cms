@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.translate.title')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/translate.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.translate.title')</h2>
            <p>@t('admin.translate.setting_description')</p>
        </div>
    </div>

    <div class="translations">
        @foreach (app('lang.available') as $key => $lang)
            <a data-lang="{{ $lang }}" @if ($key === 0) class="active" @endif>
                <img src="{{ url('assets/img/langs/' . $lang . '.svg') }}" alt="">
                <p>@t('langs.' . $lang)</p>
            </a>
        @endforeach
    </div>

    <div class="translations-container">
        @foreach (app('lang.available') as $key => $lang)
            <div id="{{ $lang }}" class="row form-group"
                @if ($key !== 0) style="display: none;" @endif>
                <div class="parametersContainer col-md-12">
                    @foreach ($translations[$lang] as $key => $val)
                        <div class="param-group" id="param-group-{{ $lang }}-{{ $key }}">
                            <input type="text" name="paramNames[]" class="form-control" placeholder="Key" required=""
                                value="{{ $key }}">
                            <input type="text" name="paramValues[]" class="form-control" placeholder="Value"
                                required="" value="{{ $val }}">
                            <button type="button" class="removeParam btn size-s error"
                                data-lang="{{ $lang }}"
                                data-id="{{ $key }}">@t('def.delete')</button>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-12">
                    <button type="button" class="btn size-s addParam outline"
                        data-lang="{{ $lang }}">@t('def.add')</button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Кнопка отправки -->
    <div class="position-relative row form-check">
        <div class="col-sm-12">
            <button type="submit" data-save class="btn size-m btn--with-icon primary">
                @t('def.save')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/translate/list.js')
@endpush
