@extends('Core/Http/Views/Installer/app.blade.php')

@section('title')
    {{ __('install.4.title') }}
@endsection

@push('header')
    @at('Core/Http/Views/Installer/assets/styles/pages/migration.scss')
@endpush

@push('content')
    <div class="migrations">
        <div class="container-installer choose_cms">
            <h1 class="first-title animate__animated">{{ __('install.4.card_head') }}</h1>
            <div class="card">
                <div class="card-header">
                    <a href="{{ url('install/3') }}" class="back-btn">
                        <i class="ph ph-caret-left"></i>
                    </a>
                    <p>{{ __('install.4.card_head_desc') }}</p>
                </div>

                <div class="body">
                    <div class="cms" data-id="lrweb">
                        <i class="ph-bold ph-check"></i>
                        <div class="icon">
                            <svg width="30" height="17" viewBox="0 0 30 17" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 16.8999V0.399902H4.73174V13.1992H12.7136V16.8999H0Z" />
                                <path
                                    d="M14.586 16.8999V0.399902H22.2333C23.7627 0.399902 25.0771 0.643474 26.1764 1.13062C27.2916 1.61776 28.1519 2.3249 28.7573 3.25204C29.3627 4.16347 29.6654 5.24776 29.6654 6.5049C29.6654 7.74633 29.3627 8.82276 28.7573 9.73419C28.1519 10.6299 27.2916 11.3213 26.1764 11.8085C25.0771 12.2799 23.7627 12.5156 22.2333 12.5156H17.2147L19.3177 10.5592V16.8999H14.586ZM24.9337 16.8999L20.7755 10.8892H25.8179L30 16.8999H24.9337ZM19.3177 11.0542L17.2147 8.90919H21.9465C22.9342 8.90919 23.6671 8.69705 24.1451 8.27276C24.639 7.84847 24.8859 7.25919 24.8859 6.5049C24.8859 5.7349 24.639 5.13776 24.1451 4.71347C23.6671 4.28919 22.9342 4.07705 21.9465 4.07705H17.2147L19.3177 1.93205V11.0542Z" />
                            </svg>
                        </div>
                        <i class="ph-bold ph-caret-double-right carot"></i>
                        <div class="content">
                            <p>{{ __('install.4.migrate_from') }}</p>
                            <span>Levels Ranks web interface</span>
                        </div>
                    </div>
                    <div class="cms" data-id="gcms">
                        <i class="ph ph-check"></i>
                        <div class="icon">
                            <svg width="30" height="31" viewBox="0 0 30 31" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M30 15.5C30 18.4667 29.1203 21.3668 27.472 23.8335C25.8238 26.3003 23.4811 28.2229 20.7403 29.3582C17.9994 30.4935 14.9834 30.7906 12.0736 30.2118C9.16393 29.633 6.49119 28.2044 4.3934 26.1066C2.29562 24.0088 0.867006 21.3361 0.288227 18.4263C-0.290551 15.5166 0.00649931 12.5006 1.14181 9.75975C2.27713 7.01886 4.19972 4.67618 6.66645 3.02796C9.13319 1.37973 12.0333 0.5 15 0.5V5.64646C13.0512 5.64646 11.1461 6.22436 9.52567 7.30708C7.90526 8.3898 6.64231 9.92871 5.89652 11.7292C5.15073 13.5297 4.95559 15.5109 5.3358 17.4223C5.716 19.3337 6.65446 21.0895 8.0325 22.4675C9.41054 23.8455 11.1663 24.784 13.0777 25.1642C14.9891 25.5444 16.9703 25.3493 18.7708 24.6035C20.5713 23.8577 22.1102 22.5947 23.1929 20.9743C24.2756 19.3539 24.8535 17.4488 24.8535 15.5H30Z" />
                                <path d="M17.0388 13.1699H30V18.1214H17.0388V13.1699Z" />
                            </svg>
                        </div>
                        <i class="ph-bold ph-caret-double-right carot"></i>
                        <div class="content">
                            <p>{{ __('install.4.migrate_from') }}</p>
                            <span>Game CMS</span>
                        </div>
                    </div>
                </div>

                <a class="a" href="{{ url('install/5') }}">{{ __('install.4.thanks_but_no') }}</a>
            </div>
        </div>
        <div class="container-installer database">
            <h1 class="first-title animate__animated">{{ __('install.4.card_head') }}</h1>
            <div class="card">
                <div class="card-header">
                    <a href="{{ url('install/3') }}" class="back-btn">
                        <i class="ph ph-caret-left"></i>
                    </a>
                    <p>{{ __('install.4.card_head_desc') }}</p>
                </div>

                <div class="body">
                    <div class="cms" data-id="lrweb">
                        <i class="ph-bold ph-check"></i>
                        <div class="icon">
                            <svg width="30" height="17" viewBox="0 0 30 17" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 16.8999V0.399902H4.73174V13.1992H12.7136V16.8999H0Z" />
                                <path
                                    d="M14.586 16.8999V0.399902H22.2333C23.7627 0.399902 25.0771 0.643474 26.1764 1.13062C27.2916 1.61776 28.1519 2.3249 28.7573 3.25204C29.3627 4.16347 29.6654 5.24776 29.6654 6.5049C29.6654 7.74633 29.3627 8.82276 28.7573 9.73419C28.1519 10.6299 27.2916 11.3213 26.1764 11.8085C25.0771 12.2799 23.7627 12.5156 22.2333 12.5156H17.2147L19.3177 10.5592V16.8999H14.586ZM24.9337 16.8999L20.7755 10.8892H25.8179L30 16.8999H24.9337ZM19.3177 11.0542L17.2147 8.90919H21.9465C22.9342 8.90919 23.6671 8.69705 24.1451 8.27276C24.639 7.84847 24.8859 7.25919 24.8859 6.5049C24.8859 5.7349 24.639 5.13776 24.1451 4.71347C23.6671 4.28919 22.9342 4.07705 21.9465 4.07705H17.2147L19.3177 1.93205V11.0542Z" />
                            </svg>
                        </div>
                        <i class="ph-bold ph-caret-double-right carot"></i>
                        <div class="content">
                            <p>{{ __('install.4.migrate_from') }}</p>
                            <span>Levels Ranks web interface</span>
                        </div>
                    </div>
                    <div class="cms" data-id="gcms">
                        <i class="ph ph-check"></i>
                        <div class="icon">
                            <svg width="30" height="31" viewBox="0 0 30 31" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M30 15.5C30 18.4667 29.1203 21.3668 27.472 23.8335C25.8238 26.3003 23.4811 28.2229 20.7403 29.3582C17.9994 30.4935 14.9834 30.7906 12.0736 30.2118C9.16393 29.633 6.49119 28.2044 4.3934 26.1066C2.29562 24.0088 0.867006 21.3361 0.288227 18.4263C-0.290551 15.5166 0.00649931 12.5006 1.14181 9.75975C2.27713 7.01886 4.19972 4.67618 6.66645 3.02796C9.13319 1.37973 12.0333 0.5 15 0.5V5.64646C13.0512 5.64646 11.1461 6.22436 9.52567 7.30708C7.90526 8.3898 6.64231 9.92871 5.89652 11.7292C5.15073 13.5297 4.95559 15.5109 5.3358 17.4223C5.716 19.3337 6.65446 21.0895 8.0325 22.4675C9.41054 23.8455 11.1663 24.784 13.0777 25.1642C14.9891 25.5444 16.9703 25.3493 18.7708 24.6035C20.5713 23.8577 22.1102 22.5947 23.1929 20.9743C24.2756 19.3539 24.8535 17.4488 24.8535 15.5H30Z" />
                                <path d="M17.0388 13.1699H30V18.1214H17.0388V13.1699Z" />
                            </svg>
                        </div>
                        <i class="ph-bold ph-caret-double-right carot"></i>
                        <div class="content">
                            <p>{{ __('install.4.migrate_from') }}</p>
                            <span>Game CMS</span>
                        </div>
                    </div>
                </div>

                <button form="form" class="a" data-correct="{{ __('install.3.data_correct') }}" data-default=" {{ __('install.3.check_data') }}">
                    {{ __('install.3.check_data') }}
                </button>
            </div>
        </div>
    </div>
    @btnInst(['text' => __('Продолжить'), 'id' => 'next', 'disabled' => true])
@endpush

@push('footer')
    {{-- @at('Core/Http/Views/Installer/assets/js/migration.js') --}}
@endpush
