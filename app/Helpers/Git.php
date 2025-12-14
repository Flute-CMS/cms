<?php

use Flute\Core\Git\GitHubUpdater;

if (!function_exists("git")) {
    function git($repoOwner, $repoName, $currentVersion = null, $downloadDir = null): GitHubUpdater
    {
        return new GitHubUpdater($repoOwner, $repoName, $currentVersion, $downloadDir);
    }
}