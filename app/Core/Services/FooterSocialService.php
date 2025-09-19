<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\FooterSocial;

class FooterSocialService
{
    public const CACHE_KEY = 'flute.footer.social';

    protected const CACHE_TIME = 24 * 60 * 60;

    protected $footerSocial;

    protected bool $performance;

    public function __construct()
    {
        $this->performance = (bool) (is_performance());

        $this->footerSocial = $this->performance ? cache()->callback(self::CACHE_KEY, fn () => $this->getFooterSocial(), self::CACHE_TIME) : $this->getFooterSocial();
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
