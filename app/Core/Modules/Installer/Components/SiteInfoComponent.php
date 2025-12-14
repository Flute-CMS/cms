<?php

namespace Flute\Core\Modules\Installer\Components;

use DateTimeZone;
use Exception;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Support\FluteComponent;

class SiteInfoComponent extends FluteComponent
{
    /**
     * @var string
     */
    public $name = 'Flute';

    /**
     * @var string
     */
    public $description = 'Flute - is modern and powerful engine for creating websites for game servers.';

    /**
     * @var string
     */
    public $keywords = 'Flute, game servers, gaming';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $timezone = 'UTC';

    /**
     * @var string
     */
    public $footer_description = '';

    /**
     * @var string|null
     */
    public $errorMessage = null;

    /**
     * @var array
     */
    protected $timezones = [];

    /**
     * Mount the component
     */
    public function mount()
    {
        $config = config('app');
        $this->name = request()->input('name', $config['name'] ?? $this->name);
        $this->description = request()->input('description', $config['description'] ?? $this->description);
        $this->keywords = request()->input('keywords', $config['keywords'] ?? $this->keywords);
        $this->url = request()->input('url', $config['url'] ?? $this->url);
        $this->timezone = request()->input('timezone', $config['timezone'] ?? $this->timezone);
        $this->footer_description = request()->input('footer_description', $config['footer_description'] ?? $this->footer_description);

        if (empty($this->url)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
            $this->url = $protocol . $_SERVER['HTTP_HOST'];
        }

        $this->timezones = $this->getTimezones();
    }

    /**
     * Save site configuration
     */
    public function saveSiteInfo()
    {
        try {
            $this->errorMessage = null;

            $validator = $this->validate([
                'name' => 'required|max-str-len:100',
                'description' => 'required|max-str-len:255',
                'keywords' => 'required',
                'url' => 'required|url',
                'timezone' => 'required',
                'footer_description' => 'nullable|max-str-len:255',
            ]);

            if (!$validator) {
                return;
            }

            $installerConfig = app(InstallerConfig::class);
            $installerConfig->setParam('site_info', [
                'name' => $this->name,
                'description' => $this->description,
                'keywords' => $this->keywords,
                'url' => $this->url,
                'timezone' => $this->timezone,
                'footer_description' => $this->footer_description,
            ]);

            $config = config('app');
            $config['name'] = $this->name;
            $config['description'] = $this->description;
            $config['keywords'] = $this->keywords;
            $config['url'] = $this->url;
            $config['timezone'] = $this->timezone;
            $config['footer_description'] = $this->footer_description;

            config()->set('app', $config);
            config()->save();

            $this->flashMessage(__('def.success'), 'success');

            return $this->redirectTo(route('installer.step', ['id' => 7]), 500);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('installer::yoyo.site-info', [
            'name' => $this->name,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'url' => $this->url,
            'timezone' => $this->timezone,
            'footer_description' => $this->footer_description,
            'timezones' => $this->timezones,
            'errorMessage' => $this->errorMessage,
        ]);
    }

    /**
     * Get list of timezones
     */
    protected function getTimezones(): array
    {
        $timezones = [];
        $regions = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Arctic' => DateTimeZone::ARCTIC,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
            'UTC' => DateTimeZone::UTC,
        ];

        foreach ($regions as $name => $mask) {
            $zones = DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $zone) {
                $timezones[$zone] = $zone;
            }
        }

        return $timezones;
    }
}
