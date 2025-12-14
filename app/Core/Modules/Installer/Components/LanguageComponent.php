<?php

namespace Flute\Core\Modules\Installer\Components;

use Flute\Core\Support\FluteComponent;

class LanguageComponent extends FluteComponent
{
    /**
     * @var array
     */
    public $languages = [];

    /**
     * @var string
     */
    public $selectedLanguage;

    /**
     * @var string
     */
    public $preferredLanguage;

    /**
     * @var array
     */
    public $props = [
        'selectedLanguage',
    ];

    /**
     * Mount the component
     */
    public function mount()
    {
        $this->languages = $this->getAvailableLanguages();
        $this->preferredLanguage = translation()->getPreferredLanguage();
        $this->selectedLanguage = config('lang.locale', 'en');
    }

    /**
     * Set the selected language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->selectedLanguage = $language;

        $lang = config('lang');
        $lang['locale'] = $language;

        config()->set('lang', $lang);
        config()->save();

        app()->setLang($language);
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('installer::yoyo.language', [
            'languages' => $this->languages,
            'selectedLanguage' => $this->selectedLanguage,
            'preferredLanguage' => $this->preferredLanguage,
        ]);
    }

    /**
     * Get available languages
     *
     * @return array
     */
    protected function getAvailableLanguages()
    {
        return config('lang.available');
    }
}
