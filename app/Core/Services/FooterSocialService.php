<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\FooterSocial;

class FooterSocialService
{
    public const CACHE_KEY = 'flute.footer.social';

    public const CACHE_TAG = 'footer';

    protected const CACHE_TIME = 24 * 60 * 60;

    protected $footerSocial;

    protected bool $performance;

    public function __construct()
    {
        $this->performance = (bool) is_performance();

        if ($this->performance) {
            cache()->tagKey(self::CACHE_TAG, self::CACHE_KEY);
            $this->footerSocial = cache()->callback(
                self::CACHE_KEY,
                fn() => $this->getFooterSocial(),
                self::CACHE_TIME,
            );
        } else {
            $this->footerSocial = $this->getFooterSocial();
        }
    }

    public function add(FooterSocial $footerSocial): self
    {
        $this->footerSocial[] = $footerSocial;

        return $this;
    }

    public function all()
    {
        return $this->footerSocial;
    }

    protected function getFooterSocial()
    {
        return FooterSocial::findAll();
    }
}
