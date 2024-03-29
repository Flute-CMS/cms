<?php

namespace Flute\Core\Http\Controllers;

use Exception;
use Flute\Core\Installer\Steps\AbstractStep;
use Flute\Core\Services\FileSystemService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;
use Flute\Core\Installer\Steps\StepFactory;

class APIInstallerController extends AbstractController
{
    public const CONFIG_PATH = BASE_PATH . 'config/installer.php';

    public int $step;
    public FileSystemService $fileSystemService;
    public array $defaultConfig;

     /**
     * APIInstallerController constructor.
     * Initializes the FileSystemService and sets the default configuration.
     */
    public function __construct()
    {
        $this->fileSystemService = fs();
        $this->setDefaultConfig();
    }

    /**
     * Main function to install the API.
     *
     * @param FluteRequest $fluteRequest The request to install the API.
     * @param int $id The current step id.
     *
     * @return Response The response of the step method.
     */
    public function installApi(FluteRequest $fluteRequest, int $id): Response
    {
        $this->step = $id;
        $this->setDefaultConfig();

        $stepClass = StepFactory::create($id); // Create step instance dynamically
        return $this->executeStep($fluteRequest, $stepClass);
    }

    /**
     * Executes the installation step.
     *
     * @param FluteRequest $fluteRequest The request object.
     * @param AbstractStep $stepClass The installation step class.
     *
     * @return Response The response from the installation step.
     */
    protected function executeStep(FluteRequest $fluteRequest, AbstractStep $stepClass): Response
    {
        return $stepClass->install($fluteRequest, $this);
    }

    /**
     * Sets the default configuration from the installer configuration file.
     */
    protected function setDefaultConfig(): void
    {
        $this->defaultConfig = config('installer');
    }

    /**
     * Updates the configuration for the given step.
     *
     * @param string $key The key to be updated.
     * @param mixed $value The new value.
     * @param bool $finished Whether the installation is finished or not.
     */
    public function updateConfigStep(string $key, $value, bool $finished = false) : void
    {
        $newConfig = (array) $this->mergeParams($key, $value);

        if( $this->step >= $this->defaultConfig["step"] )
            $newConfig['step'] = $this->defaultConfig["step"] + 1;

        $newConfig["finished"] = $finished;

        $this->fileSystemService->updateConfig(self::CONFIG_PATH, $newConfig);

        $this->defaultConfig = $newConfig;

        if( function_exists('opcache_reset') ) opcache_reset();
    }

    /**
     * Updates the configuration for the given step.
     *
     * @param string $key The key to be updated.
     * @param mixed $value The new value.
     * @param bool $finished Whether the installation is finished or not.
     */
    public function setConfigStep(int $step) : void
    {
        $this->defaultConfig['step'] = $step;

        $this->fileSystemService->updateConfig(self::CONFIG_PATH, $this->defaultConfig);

        if( function_exists('opcache_reset') ) opcache_reset();
    }

    /**
     * Merges the given key and value into the default configuration.
     *
     * @param string $key The key to be added.
     * @param mixed $value The value to be added.
     *
     * @return array The new configuration.
     */
    public function mergeParams(string $key, $value) : array
    {
        $config = $this->defaultConfig;
        $config['params'] = array_merge($this->defaultConfig["params"], [$key => $value]);

        return $config;
    }

    /**
     * Just sets finished in the default configuration
     *
     * @param bool $status Finished or not
     *
     * @return void
     * @throws Exception
     */
    public function setFinished( bool $status = true ) : void
    {
        $newConfig = $this->defaultConfig;
        $newConfig['finished'] = $status;

        $this->fileSystemService->updateConfig(self::CONFIG_PATH, $newConfig);

        if( function_exists('opcache_reset') ) opcache_reset();
    }

    /**
     * Call install class
     * 
     * @param string $classPath The path to the class
     * 
     * @return Response
     */
    protected function installClass(FluteRequest $request, $classPath) : Response
    {
        /** @var AbstractStep $installer */
        $installer = new $classPath();
        
        $class = $installer->install($request, $this);

        return $class;
    }

    /**
     * Translates a message for the given step.
     *
     * @param string $message The message to be translated.
     * @param array $params The parameters
     *
     * @return string The translated message.
     */
    public function trans(string $message, array $params = []) : string
    {
        return __("install.{$this->step}.{$message}", $params);
    }
}
