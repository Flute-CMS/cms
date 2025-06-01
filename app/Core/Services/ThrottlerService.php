<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Repositories\BucketRepository;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Database\Entities\Bucket;

class ThrottlerService
{
    private bool $throttling;

    /**
     * @var BucketRepository $bucketRepository
     */
    private $bucketRepository;

    public function __construct(bool $throttling = true)
    {
        $this->throttling = $throttling;
        $this->bucketRepository = rep(Bucket::class);
    }

    /**
     * Throttle the given request based on the provided criteria.
     *
     * @param array     $criteria     The criteria to identify the request.
     * @param int       $supply       The maximum number of requests allowed in the interval.
     * @param int       $interval     The time interval (in seconds) for which the requests are counted.
     * @param int|null  $rustiness   The maximum number of requests allowed to exceed the supply within the interval.
     * @param bool|null $simulated    Flag to indicate whether to simulate the request without actually decrementing tokens.
     * @param int|null  $cost         The number of tokens required for the request.
     * @param bool|null $force        Flag to force the request without considering throttling rules.
     *
     * @return float  The remaining number of tokens after the request is throttled.
     *
     * @throws TooManyRequestsException  When the request exceeds the allowed rate limit.
     */
    public function throttle(
        array $criteria,
        int   $supply,
        int   $interval,
        ?int  $rustiness = null,
        ?bool $simulated = null,
        ?int  $cost = null,
        ?bool $force = null
    ): float {
        if( is_debug() ) return 100;

        $force = $force !== null && (bool) $force;
    
        if (!$this->throttling && !$force) {
            return $supply;
        }
    
        $key = $this->generateKey($criteria);
        $rustiness = $rustiness !== null ? (int) $rustiness : 1;
        $simulated = $simulated !== null && (bool)$simulated;
        $cost = $cost !== null ? (int) $cost : 1;
        $now = time();
    
        $capacity = $this->calculateCapacity($supply, $rustiness);
        $bandwidthPerSecond = $this->calculateBandwidthPerSecond($supply, $interval);
    
        $bucket = $this->getBucket($key, $now, $capacity);
    
        $accepted = $bucket->getTokens() >= $cost;
    
        if (!$simulated) {  
            $this->updateBucket($bucket, $accepted, $cost, $now, $capacity, $bandwidthPerSecond);
            $this->storeBucket($bucket);
        }
    
        if ($accepted) {
            return $bucket->getTokens();
        } else {
            $tokensMissing = $cost - $bucket->getTokens();
            $estimatedWaitingTimeSeconds = $this->calculateEstimatedWaitingTime($tokensMissing, $bandwidthPerSecond);
    
            throw new TooManyRequestsException('', $estimatedWaitingTimeSeconds);
        }
    }

    /**
     * Generate a unique key for the request based on the provided criteria.
     *
     * @param array $criteria  The criteria to identify the request.
     *
     * @return string  The generated unique key.
     */
    private function generateKey(array $criteria): string
    {
        return base64_encode(hash('sha256', implode("\n", $criteria), true));
    }

    /**
     * Calculate the capacity of the bucket based on the supply and rustiness.
     *
     * @param int $supply      The maximum number of requests allowed in the interval.
     * @param int $rustiness  The maximum number of requests allowed to exceed the supply within the interval.
     *
     * @return int  The calculated capacity of the bucket.
     */
    private function calculateCapacity(int $supply, int $rustiness): int
    {
        return $rustiness * $supply;
    }

    /**
     * Calculate the bandwidth per second based on the supply and interval.
     *
     * @param int $supply    The maximum number of requests allowed in the interval.
     * @param int $interval  The time interval (in seconds) for which the requests are counted.
     *
     * @return float  The calculated bandwidth per second.
     */
    private function calculateBandwidthPerSecond(int $supply, int $interval): float
    {
        return $supply / $interval;
    }

    /**
     * Retrieve the bucket from the cache or create a new one if not found.
     *
     * @param string $key The unique key for the bucket.
     * @param int $now The current timestamp.
     * @param int $capacity The capacity of the bucket.
     * @return Bucket  The bucket.
     */
    private function getBucket(string $key, int $now, int $capacity): Bucket
    {
        $bucket = $this->bucketRepository->findById($key);

        if ($bucket === null) {
            $bucket = new Bucket();
            $bucket->setId($key);
            $bucket->setTokens($capacity);
            $bucket->setReplenishedAt($now);
            $this->bucketRepository->save($bucket);
        }

        return $bucket;
    }

    /**
     * Update the bucket based on the request acceptance and other parameters.
     *
     * @param Bucket $bucket                The bucket to update.
     * @param bool   $accepted              Flag indicating if the request is accepted.
     * @param int    $cost                  The number of tokens required for the request.
     * @param int    $now                   The current timestamp.
     * @param int    $capacity              The capacity of the bucket.
     * @param float  $bandwidthPerSecond    The bandwidth per second of the bucket.
     *
     * @return void
     */
    private function updateBucket(
        Bucket $bucket,
        bool $accepted,
        int $cost,
        int $now,
        int $capacity,
        float $bandwidthPerSecond
    ): void {
        // Calculate the time passed since the last replenishment
        $timePassed = $now - $bucket->getReplenishedAt();
    
        // Calculate the number of tokens to be regenerated based on the bandwidth per second
        $tokensToBeAdded = $timePassed * $bandwidthPerSecond;
    
        // Make sure the number of tokens does not exceed the capacity
        $bucket->setTokens(min($bucket->getTokens() + $tokensToBeAdded, $capacity));
    
        if ($accepted) {
            $bucket->setTokens(max(0, $bucket->getTokens() - $cost));
        }
    
        // Update the last replenishment time
        $bucket->setReplenishedAt($now);

        $bucket->setExpiresAt($now + floor($capacity / $bandwidthPerSecond * 2));
    }

    /**
     * Store the bucket.
     *
     * @param Bucket $bucket  The bucket to store.
     *
     * @return void
     */
    private function storeBucket(Bucket $bucket): void
    {
        $this->bucketRepository->save($bucket);
    }

    /**
     * Calculate the estimated remaining waiting time in seconds for the request.
     *
     * @param int   $tokensMissing         The number of tokens missing for the request.
     * @param float $bandwidthPerSecond    The bandwidth per second of the bucket.
     *
     * @return int  The estimated remaining waiting time in seconds.
     */
    private function calculateEstimatedWaitingTime(int $tokensMissing, float $bandwidthPerSecond): int
    {
        $remainingTokens = max(0, $tokensMissing);

        return ceil($remainingTokens / $bandwidthPerSecond);
    }
}