<?php

namespace Flute\Core\Installer\Steps;

use Exception;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Http\Controllers\APIInstallerController;
use Flute\Core\Installer\Migrations\NavbarInstaller;
use Flute\Core\Installer\Migrations\RBACInstaller;
use Nette\Utils\Validators;
use Spiral\Database\Driver\MySQL\MySQLDriver;
use Spiral\Database\Driver\Postgres\PostgresDriver;
use Spiral\Database\Driver\SQLite\SQLiteDriver;
use Spiral\Database\Exception\DatabaseException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DatabaseStep extends AbstractStep
{
    protected array $defaultValues;

    /**
     * @throws Throwable
     */
    public function install(\Flute\Core\Support\FluteRequest $request, APIInstallerController $installController): Response
    {
        // Получаем данные из формы
        $data = $request->input();

        return $this->checkConnection($installController, $data);
    }

    /**
     * @throws Throwable
     */
    protected function checkConnection(APIInstallerController $installController, array $data)
    {
        if (
            Validators::is($data['driver'], 'string:4..') &&
            Validators::is($data['host'], 'string:1..') &&
            Validators::is((int) $data['port'], 'int:1..65535') &&
            Validators::is($data['db'], 'string:1..') &&
            Validators::is($data['user'], 'string:1..')
        ) {
            // Параметры валидны, можно пробовать подключиться
            $connectionString = "{$data['driver']}:host={$data['host']};port={$data['port']};dbname={$data['db']}";

            try {
                $driverString = $this->getDriverInstance($data['driver']);

                $db_array = [
                    'default' => 'default',
                    'debug' => false,
                    'databases' => [
                        'default' => ['connection' => 'default', 'prefix' => 'flute_'],
                    ],
                    'connections' => [
                        'default' => [
                            'driver' => $driverString,
                            'connection' => $connectionString,
                            'username' => $data['user'],
                            'password' => $data['pass'],
                        ],
                    ],
                ];

                // Запускаем миграцию всех таблиц и обновляем конфиг для новой БД
                $this->executeMigration($db_array);
                $this->updateConfig($installController, $db_array);

                // Заполняем стандартные значения в таблицы
                $this->executeInsert();

                return $installController->success();

            } catch (DatabaseException $e) {
                // Возвращаем сообщение об ошибке, если подключение не удалось
                return $installController->error($installController->trans('db_error', [
                    "%error%" => mb_convert_encoding($e->getTraceAsString(), 'UTF-8'),
                ]));
            }
        } else {
            // Выводим сообщение об ошибке
            return $installController->error($installController->trans("data_invalid"));
        }
    }

    protected function getDriverInstance(string $driver): string
    {
        switch ($driver) {
            case 'sqlite':
                return SQLiteDriver::class;
            case 'postgresql':
                return PostgresDriver::class;
            default:
                return MySQLDriver::class;
        }
    }

    protected function executeMigration(array $db_array)
    {
        cache()->delete(DatabaseConnection::CACHE_KEY);
        config()->set('database', $db_array);

        // Здесь я решил поступить таким образом.
        // При замене конфига глобально в контейнере, мы сможем
        // так же обмануть наш драйвер подключения к БД, и он
        // получит наши новые данные, и по ним сделает подключение
        app(DatabaseConnection::class);
    }

    /**
     * @throws Exception
     */
    protected function updateConfig(APIInstallerController $installController, array $db_array): void
    {
        fs()->updateConfig(BASE_PATH . 'config/database.php', $db_array);
        $installController->updateConfigStep('database', $db_array);
        $installController->setConfigStep(5);
    }

    /**
     * Все было желание перенести это отдельно, но так и не придумал зачем
     * @throws Throwable
     */
    protected function executeInsert(): void
    {
        $rbacInstaller = app(RBACInstaller::class);
        $rbacInstaller->installDefaultRolesAndPermissions();

        $navbarInstaller = app(NavbarInstaller::class);
        $navbarInstaller->initDefaultItems();

        // $paymentGatewayInstaller = new PaymentGatewayInstaller($this->orm);
        // $paymentGatewayInstaller->installDefaultGateways();
    }
}