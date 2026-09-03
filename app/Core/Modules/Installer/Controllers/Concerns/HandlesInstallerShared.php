<?php

namespace Flute\Core\Modules\Installer\Controllers\Concerns;

use DateTimeZone;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Services\CsrfTokenService;
use Flute\Core\Support\FluteRequest;

trait HandlesInstallerShared
{
    #[Route('/install/{id}', name: 'installer.step', methods: ['GET'], where: ['id' => '\d+'])]
    public function step(FluteRequest $request, int $id): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        if ($id < 1 || $id > $this->installerConfig->getTotalSteps()) {
            return response()->error(404, 'Installer step not found');
        }

        return $this->renderStep($id, [], false);
    }

    protected function guardInstallerAccess(FluteRequest $request): mixed
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return null;
        }

        $token =
            $request->input('_csrf_token') ?? $request->input('x-csrf-token') ?? $request->headers->get(
                'X-CSRF-Token',
            ) ?? $request->headers->get('x-csrf-token');

        if (!is_string($token) || !app(CsrfTokenService::class)->validateToken($token)) {
            return response()->error(403, __('def.csrf_expired'));
        }

        return null;
    }

    protected function renderStep(int $id, array $extraData = [], bool $allowAdvance = true): mixed
    {
        $currentStep = $this->installerConfig->getCurrentStep();

        if ($id > ( $currentStep + 1 )) {
            $id = $currentStep > 0 ? $currentStep : 1;
        }

        if ($allowAdvance && $id > $currentStep && $id === ( $currentStep + 1 )) {
            $this->installerConfig->setCurrentStep($id);
            config()->save();
        }

        $stepData = $this->getStepData($id);
        $data = array_merge($stepData, $extraData);

        return $this->installerView->renderStep($id, $data);
    }

    protected function renderStepWithPush(int $id, array $extraData = []): mixed
    {
        $stepData = $this->getStepData($id);
        $data = array_merge($stepData, $extraData);
        $html = $this->installerView->renderStep($id, $data);
        $url = route('installer.step', ['id' => $id]);

        return response()->make($html, 200, [
            'HX-Push-Url' => $url,
        ]);
    }

    protected function getStepData(int $step): array
    {
        return match ($step) {
            1 => $this->getSystemCheckData(),
            2 => $this->getDatabaseData(),
            3 => $this->getAccountSiteData(),
            4 => $this->getLanguagesData(),
            5 => $this->getModulesData(),
            6 => $this->getLaunchData(),
            default => [],
        };
    }

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

    protected function getPreferredLanguage(): string
    {
        return \translation()->getPreferredLanguage();
    }
}
