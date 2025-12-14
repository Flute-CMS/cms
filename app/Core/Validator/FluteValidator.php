<?php

namespace Flute\Core\Validator;

use Generator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use MadeSimple\Arrays\Arr;
use MadeSimple\Arrays\ArrDots;

class FluteValidator
{
    public const WILD = '*';

    /**
     * @var array Associative array of rules (rule name => callable)
     */
    protected array $rules = [];

    /**
     * @var array Associative array of messages (rules and attributes)
     */
    protected array $messages = [];

    /**
     * @var array Custom messages for fields
     */
    protected array $customMessages = [];

    /**
     * @var MessageBag Object to store errors
     */
    protected MessageBag $errors;

    /**
     * @var string Prefix for attributes
     */
    protected string $prefix = '';

    /**
     * Constructor initializes the validator.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Adds a new rule to the validator.
     *
     * @param string   $name     The name of the rule
     * @param callable $callable The handler function for the rule
     */
    public function addRule(string $name, callable $callable): self
    {
        $this->rules[$name] = $callable;

        return $this;
    }

    /**
     * Resets the validator to its initial state.
     */
    public function reset(): self
    {
        $this->rules = [];
        $this->clearErrors();

        FluteValidate::addRuleSet($this);

        return $this;
    }

    /**
     * Clears all errors from the validator.
     */
    public function clearErrors(): self
    {
        $this->errors = new MessageBag();

        return $this;
    }

    /**
     * Checks if there are any validation errors.
     */
    public function hasErrors(): bool
    {
        return !$this->errors->isEmpty();
    }

    /**
     * Retrieves the MessageBag containing errors.
     */
    public function getErrors(): MessageBag
    {
        return $this->errors;
    }

    /**
     * Validates the given data against the provided rule set.
     *
     * @param array|object      $values    The data to validate
     * @param array             $ruleSet   The set of validation rules
     * @param array             $messages  Custom messages for validation
     * @param string|null       $prefix    Optional prefix for attribute names
     */
    public function validate($values, array $ruleSet, array $messages = [], ?string $prefix = null): bool
    {
        $this->customMessages = $messages;

        if (empty($ruleSet)) {
            return true;
        }

        $originalPrefix = $this->prefix;
        if ($prefix !== null) {
            $this->prefix .= $prefix . '.';
        }

        foreach ($ruleSet as $pattern => $rules) {
            $rules = is_string($rules) ? explode('|', $rules) : $rules;

            foreach ($rules as $rule) {
                [$ruleName, $parameters] = array_pad(explode(':', $rule, 2), 2, '');
                $parameters = array_map('trim', explode(',', $parameters));

                if (Arr::exists($this->rules, $ruleName)) {
                    call_user_func($this->rules[$ruleName], $this, $values, $pattern, $ruleName, $parameters);
                }
            }
        }

        $this->prefix = $originalPrefix;
        $this->customMessages = [];

        $this->flashErrors();

        return !$this->hasErrors();
    }

    /**
     * Adds an error message to the validator.
     *
     * @param string $attribute    The attribute name
     * @param string $rule         The validation rule that failed
     * @param array  $replacements Replacement values for the message
     */
    public function addError(string $attribute, string $rule, array $replacements = []): void
    {
        $attributeKey = $this->prefix . $attribute;
        $customMessage = $this->getCustomMessage($attributeKey, $rule);

        $message = $customMessage ?? __("validator." . ($rule ?: $attribute));

        $replacements = array_merge([
            ':attribute' => $this->prettyAttribute($attribute),
        ], $replacements);

        foreach ($replacements as $search => $replace) {
            if (strpos($search, ':') === 0) {
                $message = str_replace($search, $replace, $message);
            } elseif (strpos($search, '!') === 0 && $replace) {
                $pattern = substr($search, 1);
                $replacement = (substr($replace, -1) !== self::WILD) ? '$1' : '$2';
                $message = preg_replace("/{$pattern}/", $replacement, $message);
            } else {
                $message = str_replace($search, $replace, $message);
            }
        }

        $this->errors->add($attributeKey, $message);
    }

    /**
     * Retrieves values from the array based on the given pattern.
     *
     * @param array  $array   The data array
     * @param string $pattern The pattern to match
     * @return Generator
     */
    public static function getValues(&$array, $pattern)
    {
        foreach (ArrDots::collate($array, $pattern, static::WILD) as $attribute => $value) {
            yield $attribute => $value;
        }
    }

    /**
     * Retrieves the first value that matches the given pattern.
     *
     * @param array  $array   The data array
     * @param string $pattern The pattern to match
     * @return mixed|null The first matching value or null
     */
    public static function getValue(array $array, string $pattern)
    {
        $imploded = ArrDots::implode($array);
        $regexPattern = '/^' . str_replace(self::WILD, '\d+', preg_quote($pattern, '/')) . '$/';

        foreach ($imploded as $attribute => $value) {
            if (preg_match($regexPattern, $attribute)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Optionally flashes errors to a view or session.
     * This method can be customized or removed based on usage requirements.
     */
    public function flashErrors(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', $this->errors);

        template()->addGlobal('errors', $viewErrorBag);
    }

    /**
     * Retrieves a custom message for a specific attribute and rule.
     *
     * @param string $attribute The attribute name
     * @param string $rule      The validation rule
     */
    protected function getCustomMessage(string $attribute, string $rule): ?string
    {
        $keys = [
            "{$attribute}.{$rule}",
            $attribute,
            $rule,
        ];

        foreach ($keys as $key) {
            if (isset($this->customMessages[$key])) {
                return $this->customMessages[$key];
            }
        }

        return null;
    }

    /**
     * Formats the attribute name to a more readable form.
     *
     * @param string $attribute The attribute name
     */
    protected function prettyAttribute(string $attribute): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $attribute));
    }
}
