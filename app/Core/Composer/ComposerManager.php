<?php

namespace Flute\Core\Composer;

use Composer\Console\Application;
use GuzzleHttp\Client;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

putenv('COMPOSER_HOME=' . BASE_PATH . '/vendor/bin/composer');

/**
 * Class ComposerManager
 *
 * Manages Composer package installation, removal, and retrieval.
 */
class ComposerManager
{
    /**
     * Installs a Composer package.
     *
     * @param string $package The name of the package to install.
     *
     * @return string The output of the Composer command.
     * @throws \Exception If the installation fails.
     */
    public function installPackage(string $package)
    {
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "require",
                'packages' => [$package],
                '--working-dir' => BASE_PATH,
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $app->run($input, $output);
            $outputContent = $output->fetch();

            // Check for errors in the output
            if (strpos($outputContent, 'Installation failed') !== false) {
                throw new \Exception('Installation failed: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new \Exception('Composer runtime error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Error during package installation: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "update",
                '--working-dir' => BASE_PATH,
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $exitCode = $app->run($input, $output);
            $outputContent = $output->fetch();

            if (
                strpos($outputContent, 'Your requirements could not be resolved') !== false ||
                strpos($outputContent, 'Problem ') !== false ||
                strpos($outputContent, 'Installation failed') !== false ||
                strpos($outputContent, 'Removal failed') !== false ||
                strpos($outputContent, 'error') !== false
            ) {
                throw new \Exception('Update failed: ' . $outputContent);
            }

            if ($exitCode !== 0) {
                throw new \Exception('Composer exit with error. Exit code: ' . $exitCode . '. Output: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new \Exception('Composer runtime error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Error during update: ' . $e->getMessage());
        }
    }

    /**
     * Removes a Composer package.
     *
     * @param string $package The name of the package to remove.
     *
     * @return string The output of the Composer command.
     * @throws \Exception If the removal fails.
     */
    public function removePackage(string $package)
    {
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "remove",
                'packages' => [$package],
                '--working-dir' => BASE_PATH,
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $app->run($input, $output);
            $outputContent = $output->fetch();

            // Check for errors in the output
            if (strpos($outputContent, 'Removal failed') !== false) {
                throw new \Exception('Removal failed: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new \Exception('Composer runtime error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Error during package removal: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves the list of Composer packages.
     *
     * @return array The array of required packages from composer.json.
     */
    public function getPackages()
    {
        $packages = json_decode(file_get_contents(BASE_PATH . '/composer.json'), true);

        return $packages['require'];
    }

    /**
     * Fetches package information from Packagist.
     *
     * @param int|null $page The page number for pagination.
     * @param string|null $search The search query.
     * @param int|null $length The number of results per page.
     *
     * @return array The array of package information.
     */
    public function getPackagistItems(?int $page, ?string $search, ?int $length)
    {
        set_time_limit(0);
        $guzzle = new Client();

        if (!empty($search)) {
            $res = $guzzle->get("https://packagist.org/search.json?q=$search&per_page=$length&page=$page");

            return json_decode($res->getBody()->getContents(), true);
        } else {
            $res = $guzzle->get("https://packagist.org/explore/popular.json?per_page=$length&page=$page");
            $content = json_decode($res->getBody()->getContents(), true);

            return [
                'results' => $content['packages'],
                'total' => $content['total'],
            ];
        }
    }
}
