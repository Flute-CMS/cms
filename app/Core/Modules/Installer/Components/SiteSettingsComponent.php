<?php

namespace Flute\Core\Modules\Installer\Components;

use Exception;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Support\FluteComponent;

class SiteSettingsComponent extends FluteComponent
{
    /**
     * @var bool
     */
    public $cron_mode = true;

    /**
     * @var bool
     */
    public $maintenance_mode = false;

    /**
     * @var bool
     */
    public $tips = true;

    /**
     * @var bool
     */
    public $share = true;

    /**
     * @var bool
     */
    public $flute_copyright = true;

    /**
     * @var bool
     */
    public $convert_to_webp = true;

    /**
     * @var bool
     */
    public $csrf_enabled = true;

    /**
     * @var string
     */
    public $robots = 'index, follow';

    /**
     * @var string|null
     */
    public $errorMessage = null;

    /**
     * Mount the component
     */
    public function mount()
    {
        $this->validateParams();
    }

    /**
     * Save site settings
     */
    public function saveSiteSettings()
    {
        try {
            $this->errorMessage = null;

            // $validate = $this->validate([
            //     'robots' => 'required|string',
            // ]);

            // if (! $validate) {
            //     return;
            // }

            $installerConfig = app(InstallerConfig::class);
            $installerConfig->setParams([
                'cron_mode' => $this->cron_mode,
                'maintenance_mode' => $this->maintenance_mode,
                'tips' => $this->tips,
                'share' => $this->share,
                'flute_copyright' => $this->flute_copyright,
                'convert_to_webp' => $this->convert_to_webp,
                'csrf_enabled' => $this->csrf_enabled,
                'robots' => $this->robots,
            ]);

            $config = config('app');
            $config['cron_mode'] = $this->cron_mode === 'on' ? true : false;
            $config['maintenance_mode'] = $this->maintenance_mode === 'on' ? true : false;
            $config['tips'] = $this->tips === 'on' ? true : false;
            $config['share'] = $this->share === 'on' ? true : false;
            $config['flute_copyright'] = $this->flute_copyright === 'on' ? true : false;
            $config['convert_to_webp'] = $this->convert_to_webp === 'on' ? true : false;
            $config['csrf_enabled'] = $this->csrf_enabled === 'on' ? true : false;
            $config['robots'] = $this->robots;

            config()->set('app', $config);
            config()->save();

            $user = User::query()->load('roles.permissions')->where('verified', true)->where(['roles.permissions.name' => 'admin.boss'])->fetchOne();

            auth()->authenticateById($user->id);

            $installerConfig->markAsInstalled();

            flash()->success(__('install.common.finish_success'));

            return $this->redirectTo(url('/'), 500);
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
        return view('installer::yoyo.site-settings', [
            'cron_mode' => filter_var($this->cron_mode, FILTER_VALIDATE_BOOLEAN),
            'maintenance_mode' => filter_var($this->maintenance_mode, FILTER_VALIDATE_BOOLEAN),
            'tips' => filter_var($this->tips, FILTER_VALIDATE_BOOLEAN),
            'share' => filter_var($this->share, FILTER_VALIDATE_BOOLEAN),
            'flute_copyright' => filter_var($this->flute_copyright, FILTER_VALIDATE_BOOLEAN),
            'convert_to_webp' => filter_var($this->convert_to_webp, FILTER_VALIDATE_BOOLEAN),
            'csrf_enabled' => filter_var($this->csrf_enabled, FILTER_VALIDATE_BOOLEAN),
            'robots' => $this->robots,
            'errorMessage' => $this->errorMessage,
        ]);
    }

    /**
     * Validate the parameters
     */
    protected function validateParams()
    {
        $config = config('app');

        if (!$this->cron_mode) {
            $this->cron_mode = $this->cron_mode === 'on' ? true : false ?? $config['cron_mode'] ?? $this->cron_mode;
            $this->maintenance_mode = $this->maintenance_mode === 'on' ? true : false ?? $config['maintenance_mode'] ?? $this->maintenance_mode;
            $this->tips = $this->tips === 'on' ? true : false ?? $config['tips'] ?? $this->tips;
            $this->share = $this->share === 'on' ? true : false ?? $config['share'] ?? $this->share;
            $this->flute_copyright = $this->flute_copyright === 'on' ? true : false ?? $config['flute_copyright'] ?? $this->flute_copyright;
            $this->convert_to_webp = $this->convert_to_webp === 'on' ? true : false ?? $config['convert_to_webp'] ?? $this->convert_to_webp;
            $this->csrf_enabled = $this->csrf_enabled === 'on' ? true : false ?? $config['csrf_enabled'] ?? $this->csrf_enabled;
            $this->robots ??= $config['robots'] ?? $this->robots;
        }
    }
}
