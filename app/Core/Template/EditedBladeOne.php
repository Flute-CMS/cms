<?php

namespace Flute\Core\Template;

use eftec\bladeone\BladeOne;
use Flute\Core\Services\CsrfTokenService;

/**
 * Class for extending blade for required cms functionality.
 */
class EditedBladeOne extends BladeOne
{
    /**
     * Function for adding a new item as first in the `stack` collection
     * 
     * @param string $section - Section name
     * @param string $content - Content of the new item
     * 
     * @return void
     */
    public function pushFirst(string $section, string $content)
    {
        $this->extendStartPush($section, $content);
    }

    /**
     * Function for adding a new item as last in the `stack` collection
     * 
     * @param string $section - Section name
     * @param string $content - Content of the new item
     * 
     * @return void
     */
    public function push(string $section, string $content)
    {
        $this->extendPush($section, $content);
    }

    /**
     * Function for adding a new item in the `section` collection
     * 
     * @param string $section - Section name
     * @param string $content - Content of the new item
     * 
     * @return void
     */
    public function pushSection(string $section, string $content)
    {
        $this->extendSection($section, $content);
    }

    public function ipClient()
    {
        return request()->getClientIp();
    }

    /**
     * Validates if the csrf token is valid or not.<br>
     * It requires an open session.
     *
     * @param bool   $alwaysRegenerate [optional] Default is false.<br>
     *                                 If **true** then it will generate a new token regardless
     *                                 of the method.<br>
     *                                 If **false**, then it will generate only if the method is POST.<br>
     *                                 Note: You must not use true if you want to use csrf with AJAX.
     *
     * @param string $tokenId          [optional] Name of the token.
     *
     * @return bool It returns true if the token is valid, or it is generated. Otherwise, false.
     */
    public function csrfIsValid($alwaysRegenerate = false, $tokenId = 'x-csrf-token'): bool
    {
        if (@$_SERVER['REQUEST_METHOD'] !== 'GET') {
            // Try to get the token from POST body first
            $this->csrf_token = (request()->headers->get($tokenId) ?? request()->input($tokenId)) ?? '';

            if( !$this->csrf_token ) {
                $this->csrf_token = (request()->headers->get("x_csrf_token") ?? request()->input("x_csrf_token")) ?? '';
            }

            // Check if the token matches the session token
            if ($this->csrf_token !== null) {
                return $this->csrf_token . '|' . $this->ipClient() === ($_SESSION[$tokenId] ?? null);
            }

            // If alwaysRegenerate is false and method is POST, and token is not present or doesn't match
            if ($alwaysRegenerate === false) {
                return false;
            }
        }

        // Regenerate token if needed
        if ($this->csrf_token == '' || $alwaysRegenerate) {
            $this->regenerateToken($tokenId);
        }

        return true;
    }

    public function getCsrfToken($fullToken = false, $tokenId = 'x-csrf-token'): string
    {
        return app(CsrfTokenService::class)->getToken();
    }

    public function regenerateToken($tokenId = 'x-csrf-token'): void
    {
        try {
            $this->csrf_token = \bin2hex(\random_bytes(10));
        } catch (\Throwable $e) {
            $this->csrf_token = '123456789012345678901234567890'; // unable to generates a random token.
        }
        @$_SESSION[$tokenId] = $this->csrf_token . '|' . $this->ipClient();
    }

    public function clearSections()
    {
        $this->pushes = [];
    }

    public function clearSection(string $section)
    {
        $this->pushes[$section] = [];
    }

    protected function compilecsrf($expression = null): string
    {
        $expression = $expression ?? "'x-csrf-token'";
        return "<input type='hidden' name='$this->phpTag echo $expression; ?>' value='{$this->phpTag}echo \$this->getCsrfToken(); " . "?>'/>";
    }

    public function addAssetDict($name, $url = ''): void
    {
        if (\is_array($name)) {
            if (!isset($this->assetDict) || (isset($this->assetDict) && empty($this->assetDict))) {
                $this->assetDict = $name;
            } else {
                $this->assetDict = \array_merge($this->assetDict, $name);
            }
        } else {
            $this->assetDict[$name] = $url;
        }
    }

    public static function getInstance($templatePath = null, $compiledPath = null, $mode = 0): BladeOne
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($templatePath, $compiledPath, $mode);
        }
        return self::$instance;
    }
}