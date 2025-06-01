<?php

namespace Flute\Core\Logging\Renderer;

use Flute\Core\Database\Entities\UserActionLog;
use Flute\Core\Logging\Contracts\LogFormatterInterface;

class DefaultLogFormatter implements LogFormatterInterface
{
    public function supports(UserActionLog $log) : bool
    {
        return true;
    }

    public function render(UserActionLog $log) : string
    {
        $action = __($log->action);
        $createdAt = $log->createdAt->format('Y-m-d H:i:s');
        $userName = $log->user ? htmlspecialchars($log->user->name) : 'Guest';

        $levelClass = $this->mapLevelToCssClass($log->level);

        $message = $log->message ?: $this->renderData($log->data);


        return sprintf(
            '<div class="log-item %s">
                <span class="log-date">%s</span>
                <strong class="log-user">%s</strong>:
                <span class="log-action">%s</span>
                <span class="log-message">%s</span>
            </div>',
            $levelClass,
            $createdAt,
            $userName,
            $action,
            $message
        );
    }

    private function renderData(?array $data) : string
    {
        if (empty($data)) {
            return '';
        }
        return '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }

    private function mapLevelToCssClass(?string $level) : string
    {
        return match ($level) {
            'error' => 'text-danger',
            'warning' => 'text-warning',
            'success' => 'text-success',
            'info' => 'text-info',
            default => 'text-secondary',
        };
    }
}
