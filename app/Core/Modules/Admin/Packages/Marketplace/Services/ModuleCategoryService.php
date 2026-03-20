<?php

namespace Flute\Admin\Packages\Marketplace\Services;

class ModuleCategoryService
{
    public const CATEGORY_ALL = 'all';

    public const CATEGORY_PAYMENT = 'payment';

    public const CATEGORY_GAME = 'game';

    public const CATEGORY_CONTENT = 'content';

    public const CATEGORY_SHOP = 'shop';

    public const CATEGORY_STEAM = 'steam';

    public const CATEGORY_UI = 'ui';

    public const CATEGORY_UTILITY = 'utility';

    protected const CATEGORY_MAP = [
        // Payment gateways
        'freekassa' => self::CATEGORY_PAYMENT,
        'yookassa' => self::CATEGORY_PAYMENT,
        'yoomoney' => self::CATEGORY_PAYMENT,
        'monobank' => self::CATEGORY_PAYMENT,
        'paypal' => self::CATEGORY_PAYMENT,
        'foxypay' => self::CATEGORY_PAYMENT,
        'cshost' => self::CATEGORY_PAYMENT,
        'mercadopago' => self::CATEGORY_PAYMENT,
        'stripe' => self::CATEGORY_PAYMENT,
        'tbankpayment' => self::CATEGORY_PAYMENT,
        'paypalych' => self::CATEGORY_PAYMENT,
        'aifopayment' => self::CATEGORY_PAYMENT,

        // Game-related
        'stats' => self::CATEGORY_GAME,
        'statsfree' => self::CATEGORY_GAME,
        'bansmanager' => self::CATEGORY_GAME,
        'monitoring' => self::CATEGORY_GAME,
        'skinchanger' => self::CATEGORY_GAME,
        'modes' => self::CATEGORY_GAME,

        // Content
        'faq' => self::CATEGORY_CONTENT,
        'rules' => self::CATEGORY_CONTENT,
        'wiki' => self::CATEGORY_CONTENT,
        'hero' => self::CATEGORY_CONTENT,
        'banners' => self::CATEGORY_CONTENT,
        'tabs' => self::CATEGORY_CONTENT,
        'cardlink' => self::CATEGORY_CONTENT,
        'profileposts' => self::CATEGORY_CONTENT,

        // Shop
        'shop' => self::CATEGORY_SHOP,
        'givecore' => self::CATEGORY_SHOP,
        'viplist' => self::CATEGORY_SHOP,

        // Steam integrations
        'steamenter' => self::CATEGORY_STEAM,
        'steamfriends' => self::CATEGORY_STEAM,
        'steaminfo' => self::CATEGORY_STEAM,
        'steamprofile' => self::CATEGORY_STEAM,

        // UI/UX
        'acceptcookie' => self::CATEGORY_UI,
        'snow' => self::CATEGORY_UI,
        'lightbox' => self::CATEGORY_UI,
        'welcomepopup' => self::CATEGORY_UI,
        'announcement' => self::CATEGORY_UI,
        'search' => self::CATEGORY_UI,

        // Utility
        'api' => self::CATEGORY_UTILITY,
        'fluteusers' => self::CATEGORY_UTILITY,
        'notifications' => self::CATEGORY_UTILITY,
        'minibalance' => self::CATEGORY_UTILITY,
        'reviews' => self::CATEGORY_UTILITY,
        'gamecmsmigration' => self::CATEGORY_UTILITY,
        'faceitinfo' => self::CATEGORY_UTILITY,
    ];

    protected const CATEGORY_ICONS = [
        self::CATEGORY_ALL => 'ph.bold.squares-four-bold',
        self::CATEGORY_PAYMENT => 'ph.bold.credit-card-bold',
        self::CATEGORY_GAME => 'ph.bold.game-controller-bold',
        self::CATEGORY_CONTENT => 'ph.bold.article-bold',
        self::CATEGORY_SHOP => 'ph.bold.storefront-bold',
        self::CATEGORY_STEAM => 'ph.bold.steam-logo-bold',
        self::CATEGORY_UI => 'ph.bold.paint-brush-bold',
        self::CATEGORY_UTILITY => 'ph.bold.wrench-bold',
    ];

    public function getCategory(string $slug): string
    {
        return self::CATEGORY_MAP[strtolower($slug)] ?? self::CATEGORY_UTILITY;
    }

    public function getCategoryIcon(string $category): string
    {
        return self::CATEGORY_ICONS[$category] ?? self::CATEGORY_ICONS[self::CATEGORY_UTILITY];
    }

    public function getCategoriesWithCounts(array $modules): array
    {
        $counts = [];
        foreach ($modules as $module) {
            $cat = $this->getModuleCategory($module);
            $counts[$cat] = ( $counts[$cat] ?? 0 ) + 1;
        }

        $categories = [];
        $categories[self::CATEGORY_ALL] = [
            'key' => self::CATEGORY_ALL,
            'label' => __('admin-marketplace.categories.all'),
            'icon' => self::CATEGORY_ICONS[self::CATEGORY_ALL],
            'count' => count($modules),
        ];

        $order = [
            self::CATEGORY_GAME,
            self::CATEGORY_SHOP,
            self::CATEGORY_PAYMENT,
            self::CATEGORY_CONTENT,
            self::CATEGORY_STEAM,
            self::CATEGORY_UI,
            self::CATEGORY_UTILITY,
        ];

        foreach ($order as $cat) {
            if (isset($counts[$cat]) && $counts[$cat] > 0) {
                $categories[$cat] = [
                    'key' => $cat,
                    'label' => __('admin-marketplace.categories.' . $cat),
                    'icon' => self::CATEGORY_ICONS[$cat],
                    'count' => $counts[$cat],
                ];
            }
        }

        return $categories;
    }

    public function getModuleCategory(array $module): string
    {
        $slug = strtolower($module['slug'] ?? $module['name'] ?? '');

        if (!empty($module['tags'])) {
            foreach ($module['tags'] as $tag) {
                $tagLower = strtolower(is_array($tag) ? $tag['name'] ?? '' : (string) $tag);
                if (in_array($tagLower, [
                    self::CATEGORY_PAYMENT,
                    self::CATEGORY_GAME,
                    self::CATEGORY_CONTENT,
                    self::CATEGORY_SHOP,
                    self::CATEGORY_STEAM,
                    self::CATEGORY_UI,
                    self::CATEGORY_UTILITY,
                ])) {
                    return $tagLower;
                }
            }
        }

        return self::CATEGORY_MAP[$slug] ?? self::CATEGORY_UTILITY;
    }

    public function getLocalizedDescription(string $description, ?string $lang = null): string
    {
        if (empty($description)) {
            return '';
        }

        $lang ??= app()->getLang();
        $isRussian = in_array($lang, ['ru', 'uk', 'bg']);

        $parts = preg_split('/\n{2,}/', $description);
        if (count($parts) < 2) {
            return trim($description);
        }

        $ruParts = [];
        $enParts = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            if (preg_match('/[а-яА-ЯёЁіІїЇєЄґҐ]/u', $part)) {
                $ruParts[] = $part;
            } else {
                $enParts[] = $part;
            }
        }

        if ($isRussian && !empty($ruParts)) {
            return implode("\n\n", $ruParts);
        }

        if (!empty($enParts)) {
            return implode("\n\n", $enParts);
        }

        return trim($description);
    }

    public function getShortDescription(string $description, ?string $lang = null, int $maxLength = 120): string
    {
        $localized = $this->getLocalizedDescription($description, $lang);
        $localized = preg_replace('/\*\*[^*]+\*\*/', '', $localized);
        $localized = preg_replace('/[#*_`~\[\]()>-]/', '', $localized);
        $localized = preg_replace('/https?:\/\/\S+/', '', $localized);
        $localized = preg_replace('/\s+/', ' ', $localized);
        $localized = trim($localized);

        if (mb_strlen($localized) > $maxLength) {
            $localized = mb_substr($localized, 0, $maxLength);
            $lastSpace = mb_strrpos($localized, ' ');
            if ($lastSpace !== false && $lastSpace > ( $maxLength * 0.7 )) {
                $localized = mb_substr($localized, 0, $lastSpace);
            }
            $localized .= '...';
        }

        return $localized;
    }
}
