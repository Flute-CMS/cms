<?php


namespace Flute\Core\Services;

use eftec\bladeone\BladeOne;
use League\Config\Configuration;
use Nette\Schema\Expect;
use Spiral\Database\Driver\MySQL\MySQLDriver;

class ConfigurationService
{
    /**
     * @var string
     */
    protected string $configsPath;

    /**
     * Cache for loaded configs
     */
    protected ?Configuration $configCache;

    /**
     * @var array
     */
    protected array $_configsRaw = [];

    /**
     * Configuration constructor
     * 
     * @return void
     */
    public function __construct(string $configsPath = "/config")
    {
        $this->configsPath = $configsPath;

        $this->getConfiguration();
    }

    /**
     * Get expected configs
     * 
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        if (!empty($this->configCache)) {
            return $this->configCache;
        }

        $config = new Configuration($this->getExpectedConfig());

        $config->merge($this->load());

        $this->configCache = $config;

        return $config;
    }

    /**
     * Load and merge custom configuration from a specified file
     *
     * @param string $filePath Path to the custom config file
     * @param string|null $configName Optional name for the configuration
     *
     * @return void
     */
    public function loadCustomConfig(string $filePath, ?string $configName = null, ?Expect $schema = null): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Config file not found: {$filePath}");
        }

        $configName = $configName ?: basename($filePath, '.php');
        $customConfig = require $filePath;

        if (!is_array($customConfig)) {
            throw new \UnexpectedValueException("Config file must return an array: {$filePath}");
        }

        $this->_configsRaw[$configName] = $customConfig;

        $this->configCache->addSchema($configName, $schema ?? Expect::array());
        $this->configCache->set($configName, $customConfig);
    }

    /**
     * Import configs from config directory
     * 
     * @return array
     */
    public function load(): array
    {
        if (!empty($this->_configsRaw)) {
            return $this->_configsRaw;
        }

        $finder = new \Symfony\Component\Finder\Finder();

        $finder->files()->in($this->configsPath)->depth(0);

        foreach ($finder as $file) {
            $configName = $file->getBasename('.php');
            $this->_configsRaw[$configName] = require $file->getRealPath();
        }

        return $this->_configsRaw;
    }

    /**
     * Get configs path
     * 
     * @return string
     */
    public function getConfigsPath(): string
    {
        return $this->configsPath;
    }

    /**
     * Set configs path
     * 
     * @param string $configsPath
     * 
     * @return void
     */
    public function setConfigsPath(string $configsPath): void
    {
        $this->configsPath = $configsPath;
    }

    /**
     * Get all configurations as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->_configsRaw;
    }

    /**
     * Get expected config
     * 
     * @return array
     */
    public function getExpectedConfig(): array
    {
        return [
            "app" => Expect::structure([
                "name" => Expect::string("Flute")->required(),
                "url" => Expect::string()->required(),
                "steam_api" => Expect::string()->required(),
                "debug_ips" => Expect::array()->required(),
                "debug" => Expect::bool(true)->required(),
                "maintenance_mode" => Expect::bool(false),
                "discord_link_roles" => Expect::bool(false),
                "mode" => Expect::string("performance")->required(),
                "key" => Expect::string()->required(),
                "logo" => Expect::string()->required(),
                "bg_image" => Expect::string(''),
                "tips" => Expect::bool(false)->required(),
                "share" => Expect::bool(true)->required(),
                "flute_copyright" => Expect::bool(true)->required(),
                "timezone" => Expect::string()->required(),
                "notifications" => Expect::string('all')->required(),
                "widget_placeholders" => Expect::bool(true),
            ]),
            "lk" => Expect::structure([
                // "min_amount" => Expect::int(100)->required(),
                "currency_view" => Expect::string()->required(),
                "oferta_view" => Expect::bool(false)->required(),
                "pay_in_new_window" => Expect::bool(false),
            ]),
            'profile' => Expect::structure([
                'max_banner_size' => Expect::int()->required(),
                'max_avatar_size' => Expect::int()->required(),
                'banner_types' => Expect::array(),
                'avatar_types' => Expect::array(),
                'change_uri' => Expect::bool(true)->required(),
                'default_avatar' => Expect::string()->required(),
                'default_banner' => Expect::string()->required(),
                'convert_to_webp' => Expect::bool()->required(),
            ]),
            'mail' => Expect::structure([
                'smtp' => Expect::bool(false),
                'host' => Expect::string('localhost'),
                'port' => Expect::int(587),
                'from' => Expect::string(),
                'username' => Expect::string(),
                'password' => Expect::string(),
                'secure' => Expect::string('tls'),
                'auth_mode' => Expect::string('login'),
            ]),
            "auth" => Expect::structure([
                "remember_me" => Expect::bool(true)->required(),
                "remember_me_duration" => Expect::int()->required(),
                "csrf_enabled" => Expect::bool(true)->required(),
                "check_ip" => Expect::bool(true),
                "reset_password" => Expect::bool(true)->required(),
                "security_token" => Expect::bool(true)->required(),
                "only_social" => Expect::bool(false),
                "registration" => Expect::structure([
                    "confirm_email" => Expect::bool()->required(),
                    "social_supplement" => Expect::bool()->required()
                ]),
                "validation" => Expect::structure([
                    "login" => Expect::structure([
                        "min_length" => Expect::int()->required(),
                        "max_length" => Expect::int()->required()
                    ]),
                    "password" => Expect::structure([
                        "min_length" => Expect::int()->required(),
                        "max_length" => Expect::int()->required()
                    ]),
                    "name" => Expect::structure([
                        "min_length" => Expect::int()->required(),
                        "max_length" => Expect::int()->required()
                    ])
                ])->required(),
            ]),
            'tips_complete' => Expect::array([]),
            "cache" => Expect::structure([
                "directory" => Expect::string(BASE_PATH . "storage/app/cache"),
                "driver" => Expect::string("file")->required(),
            ]),
            "logging" => Expect::structure([
                'loggers' => Expect::arrayOf(
                    Expect::structure([
                        'path' => Expect::string()->required(),
                        'level' => Expect::int()->required(),
                    ])
                )->required(),
            ]),
            "view" => Expect::structure([
                "cache" => Expect::structure([
                    "mode" => Expect::int(BladeOne::MODE_FAST)->required(),
                    "path" => Expect::string(BASE_PATH . "storage/app/views")->required(),
                ]),
                "convert_to_webp" => Expect::bool(false)->required(),
            ]),
            "assets" => Expect::structure([
                "cache" => Expect::bool(true)->required(),
            ]),
            "lang" => Expect::structure([
                "locale" => Expect::string("ru")->required(),
                "cache" => Expect::bool(true)->required(),
                "available" => Expect::array([]),
                "all" => Expect::array([]),
            ]),
            "installer" => Expect::structure([
                "step" => Expect::int()->required(),
                "finished" => Expect::bool(false)->required(),
                "params" => Expect::array([])
            ]),
            "database" => Expect::structure([
                "default" => Expect::string("default")->required(),
                "debug" => Expect::bool(false)->required(),
                "databases" => Expect::array()->required(),
                "connections" => Expect::arrayOf(
                    Expect::structure([
                        "driver" => Expect::string(MySQLDriver::class)->required(),
                        "connection" => Expect::string("mysql:host=localhost;dbname=flute")->required(),
                        "username" => Expect::string("root")->required(),
                        "password" => Expect::string("")->required(),
                        "timezone" => Expect::string("UTC")
                    ])->castTo('array')
                )->required(),
            ]),
        ];
    }
}