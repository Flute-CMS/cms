<?php

namespace Flute\Core\Console\Command;

use Closure;
use Flute\Core\Router\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutesCommand extends Command
{
    protected static $defaultName = 'route:list';

    protected static $defaultDescription = 'Displays a list of all registered routes';

    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('route:list')
            ->setDescription('Displays a list of all registered routes')
            ->setHelp('This command allows you to list all registered routes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = $this->router->getRoutes();

        if ($routes->count() === 0) {
            $output->writeln('<info>No registered routes.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<info>List of registered routes:</info>');

        $table = new Table($output);
        $table->setHeaders(['Name', 'Methods', 'URI', 'Action', 'Middleware']);

        foreach ($routes as $routeName => $symfonyRoute) {
            $methods = implode(',', $symfonyRoute->getMethods());
            $uri = $symfonyRoute->getPath();
            $action = $this->getActionRepresentation($symfonyRoute->getDefault('_controller'));
            $middleware = implode(',', $symfonyRoute->getDefault('_middleware') ?? []);

            $table->addRow([$routeName, $methods, $uri, $action, $middleware]);
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
