<?php

namespace Flute\Core\Modules\Installer\Components;

use Flute\Core\Support\FluteComponent;
use GuzzleHttp\Client;

class FluteKeyComponent extends FluteComponent
{
    /**
     * @var string
     */
    public $fluteKey;

    /**
     * @var string|null
     */
    public $error = null;

    /**
     * @var bool
     */
    public $isValid = false;

    /**
     * Mount the component
     */
    public function mount()
    {
        if (!request()->input('fluteKey')) {
            $this->fluteKey = config('installer.flute_key', '');
        }

        $this->validateKey();
    }

    /**
     * Validate the Flute key
     * 
     * @param string $key
     */
    public function validateKey()
    {
        $this->error = null;

        if (empty($this->fluteKey)) {
            $this->isValid = true; // we allow empty key
            return $this->redirectTo(route('installer.step', ['id' => 4]), 500);
        }

        try {
            $client = new Client();
            $response = $client->post(config('app.flute_market_url') . '/api/auth/accesskey', [
                'json' => [
                    'key' => $this->fluteKey,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody(), true);
                if ($body['valid'] === true) {
                    $app = config('app');
                    $app['flute_key'] = $this->fluteKey;

                    $this->isValid = true;

                    config()->set('app', $app);
                    config()->save();
                    
                    return $this->redirectTo(route('installer.step', ['id' => 4]), 500);
                }
            }
        } catch (\Exception $e) {
        }

        $this->error = $body['message'] ?? __('install.flute_key.error_invalid');
        $this->isValid = false;
    }

    /**
     * Render the component
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('installer::yoyo.flute-key', [
            'fluteKey' => $this->fluteKey,
            'error' => $this->error,
            'isValid' => $this->isValid,
        ]);
    }
}
