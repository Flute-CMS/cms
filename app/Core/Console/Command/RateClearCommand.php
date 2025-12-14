use Flute\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RateClearCommand extends AbstractCommand
{
    protected $signature = 'rate:clear {key?} {--all}';

    protected $description = 'Clear rate limiter keys';

    public function handle()
    {
        $key = $this->argument('key');
        $all = $this->option('all');

        $cache = cache();

        if ($all) {
            $keys = $cache->getKeys('rl:*');
            foreach ($keys as $k) {
                $cache->delete($k);
            }
            $this->info('Cleared all rate limiter keys');
        } elseif ($key) {
            $cache->delete($key);
            $this->info("Cleared key: $key");
        } else {
            $this->error('Provide a key or use --all');
        }
    }
}
