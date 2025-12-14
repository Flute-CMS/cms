use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RateLimit
{
    private string $by;
    private int $maxAttempts;
    private string|int $decaySeconds;
    private ?string $policy;

    public function __construct(
        string $by = 'ip',
        int $maxAttempts = 60,
        string|int $decaySeconds = 60,
        ?string $policy = null
    ) {
        $this->by = $by;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->policy = $policy;
    }

    // Getters
    public function getBy(): string { return $this->by; }
    public function getMaxAttempts(): int { return $this->maxAttempts; }
    public function getDecaySeconds(): string|int { return $this->decaySeconds; }
    public function getPolicy(): ?string { return $this->policy; }
}
