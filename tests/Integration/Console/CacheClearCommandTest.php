<?php

namespace Tests\Integration\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Flute\Core\Console\Command\CacheClearCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Application;

class CacheClearCommandTest extends TestCase
{
    private Filesystem $filesystem;
    private CacheClearCommand $command;

    protected function setUp() : void
    {
        parent::setUp();
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->command = new class ($this->filesystem) extends CacheClearCommand {
            private $fs;

            public function __construct(Filesystem $fs)
            {
                parent::__construct();
                $this->fs = $fs;
            }
            protected function execute(
                \Symfony\Component\Console\Input\InputInterface $input,
                \Symfony\Component\Console\Output\OutputInterface $output
            ) : int {
                $filesystem = $this->fs;
                try {
                    $filesystem->remove(\glob(BASE_PATH . '/storage/app/cache/*'));
                    $output->writeln('<info>Flute cache has been deleted successfully.</info>');
                    return self::SUCCESS;
                } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                    return self::FAILURE;
                }
            }
        };
    }

    public function testExecuteRemovesCacheAndShowsSuccess() : void
    {
        $this->filesystem
            ->expects($this->once())
            ->method('remove')
            ->with($this->isType('array'));

        $app = new Application('TestApp', '1.0');
        $app->add($this->command);

        $tester = new CommandTester($app->find('cache:clear'));
        $tester->execute([]);

        $this->assertStringContainsString('Flute cache has been deleted successfully.', $tester->getDisplay());
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
