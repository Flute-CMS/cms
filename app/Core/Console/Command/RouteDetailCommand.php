<?php

namespace Flute\Core\Console\Command;

use Closure;
use Flute\Core\Router\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteDetailCommand extends Command
{
    protected static $defaultName = 'route:detail';

    protected static $defaultDescription = 'Displays detailed information about a specified route';

    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('route:detail')
            ->addArgument('path', InputArgument::REQUIRED, 'Route path (URI)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = '/' . trim($input->getArgument('path'), '/');

        $routes = $this->router->getRoutes();
        $foundRoute = null;
        $foundRouteName = null;

        foreach ($routes as $routeName => $symfonyRoute) {
            if ($symfonyRoute->getPath() === $path) {
                $foundRoute = $symfonyRoute;
                $foundRouteName = $routeName;

                break;
            }
        }

        if (!$foundRoute) {
            $output->writeln("<error>Route with path '{$path}' not found.</error>");

            return Command::FAILURE;
        }

        /*
         * @var \Symfony\Component\Routing\Route $foundRoute
         */

        $output->writeln("<info>Information about route '{$foundRouteName}':</info>");

        $methods = implode(', ', $foundRoute->getMethods());
        $uri = $foundRoute->getPath();
        $action = $this->getActionRepresentation($foundRoute->getDefault('_controller'));
        $middleware = implode(',', $foundRoute->getDefault('_middleware') ?? []);
        $requirements = $foundRoute->getRequirements();

        $table = new Table($output);
        $table->setHeaders(['Parameter', 'Value']);

        $table->addRow(['Name', $foundRouteName]);
        $table->addRow(['Methods', $methods]);
        $table->addRow(['URI', $uri]);
        $table->addRow(['Action', $action]);
        $table->addRow(['Middleware', $middleware]);

        if (!empty($requirements)) {
            foreach ($requirements as $param => $regex) {
                if (in_array($param, ['_scheme', '_method'])) {
                    continue;
                }
                $table->addRow([$param, $regex]);
            }
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function getActionRepresentation($action): string
    {
        if (is_string($action)) {
            return $action;
        } elseif (is_array($action)) {
            if (is_object($action[0])) {
                $class = get_class($action[0]);
            } else {
                $class = $action[0];
            }

            return $class . '@' . $action[1];
        } elseif ($action instanceof Closure) {
            return 'Closure';
        }

        return 'Unknown';

    }
}
