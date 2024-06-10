<?php

namespace Flute\Core\Support;

use Flute\Core\Database\Entities\User;

class ContentParser
{
    public static function replaceContent(string $content, $eventInstance, User $user): string
    {
        $content = self::replaceUserContent($content, $user);
        $content = self::handleConditionalStatements($content, $eventInstance);

        return preg_replace_callback('/\{(.*?)\}/', function ($matches) use ($eventInstance) {
            return self::evaluateExpression($matches[1], $eventInstance);
        }, $content);
    }

    private static function handleConditionalStatements(string $content, $eventInstance): string
    {
        $pattern = '/@if\s*\(([^()]*(?:\((?:[^()]*(?:\([^()]*\))*[^()]*)*\))*[^()]*)\)(.*?)@endif/s';
        return preg_replace_callback($pattern, function ($matches) use ($eventInstance) {
            $condition = trim($matches[1]);
            $ifContent = $matches[2];

            if (strpos($ifContent, '@else') !== false) {
                list($ifContent, $elseContent) = explode('@else', $ifContent);
            } else {
                $elseContent = '';
            }

            // Evaluate the condition
            $result = self::evaluateExpression($condition, $eventInstance);

            return $result ? $ifContent : $elseContent;
        }, $content);
    }

    private static function evaluateExpression($expression, $eventInstance = null)
    {
        if (preg_match('/\s*\?\?\s*/', $expression)) {
            list($condition, $fallback) = explode('??', $expression);
            $condition = trim($condition);
            $fallback = trim($fallback, " '\"");
            $result = self::getNestedProperty(null, $condition);
            return $result !== null ? $result : $fallback;
        }

        if (preg_match('/^(\w+)\((.*?)\)$/', $expression, $matches)) {
            $func = $matches[1];
            $args = self::parseArguments($matches[2]);
            return self::handleFunction($func, $args);
        }

        return self::getNestedProperty($eventInstance, $expression);
    }

    private static function getNestedProperty($object, $expression)
    {
        $current = $object;
        $parts = explode('.', $expression);

        foreach ($parts as $part) {
            if (preg_match('/(\w+)\((.*?)\)/', $part, $matches)) {
                $func = $matches[1];
                $args = self::parseArguments($matches[2]);
                if ($current === null && function_exists($func)) {
                    $current = call_user_func_array($func, $args);
                } elseif (is_object($current) && method_exists($current, $func)) {
                    $current = call_user_func_array([$current, $func], $args);
                } elseif(function_exists($func)) {
                    $current = call_user_func_array($func, $args);
                } else {
                    return null;
                }
            } elseif (is_object($current)) {
                if (property_exists($current, $part)) {
                    $current = $current->{$part};
                } elseif (method_exists($current, $part)) {
                    $current = $current->{$part}();
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        return $current;
    }

    private static function handleFunction($func, $args)
    {
        if (function_exists($func)) {
            return call_user_func_array($func, $args);
        }

        return null;
    }

    private static function parseArguments($argsString)
    {
        $args = [];
        if (!empty($argsString)) {
            preg_match_all('/\'([^\']*)\'|\"([^\"]*)\"|([^,]+)/', $argsString, $matches);
            foreach ($matches[0] as $part) {
                $args[] = trim($part, " \t\n\r\0\x0B'\"");
            }
        }
        return $args;
    }

    private static function replaceUserContent(string $content, User $user)
    {
        return str_replace(['{name}', '{login}', '{email}', '{balance}'], [
            $user->name,
            $user->login,
            $user->email,
            $user->balance
        ], $content);
    }
}
