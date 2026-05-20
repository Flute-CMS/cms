<?php

namespace Flute\Core\Validator;

use Flute\Core\Validator\Rules\RulesArray;
use Flute\Core\Validator\Rules\RulesComparison;
use Flute\Core\Validator\Rules\RulesDateTime;
use Flute\Core\Validator\Rules\RulesDb;
use Flute\Core\Validator\Rules\RulesFile;
use Flute\Core\Validator\Rules\RulesMisc;
use Flute\Core\Validator\Rules\RulesNumeric;
use Flute\Core\Validator\Rules\RulesPattern;
use Flute\Core\Validator\Rules\RulesRequired;
use Flute\Core\Validator\Rules\RulesString;

class FluteValidate
{
    use RulesArray;
    use RulesComparison;
    use RulesDateTime;
    use RulesDb;
    use RulesFile;
    use RulesMisc;
    use RulesNumeric;
    use RulesPattern;
    use RulesRequired;
    use RulesString;

    public static function addRuleSet(FluteValidator $validator)
    {
        $validator
            ->addRule('present', [static::class, 'present'])
            ->addRule('required', [static::class, 'required'])
            ->addRule('required-if', [static::class, 'requiredIf'])
            ->addRule('required-with', [static::class, 'requiredWith'])
            ->addRule('required-with-all', [static::class, 'requiredWithAll'])
            ->addRule('required-with-any', [static::class, 'requiredWithAny'])
            ->addRule('required-without', [static::class, 'requiredWithout'])
            ->addRule('equals', [static::class, 'equals'])
            ->addRule('not-equals', [static::class, 'notEquals'])
            ->addRule('identical', [static::class, 'identical'])
            ->addRule('not-identical', [static::class, 'notIdentical'])
            ->addRule('in', [static::class, 'in'])
            ->addRule('not-in', [static::class, 'notIn'])
            ->addRule('contains', [static::class, 'contains'])
            ->addRule('contains-only', [static::class, 'containsOnly'])
            ->addRule('min-arr-count', [static::class, 'minArrCount'])
            ->addRule('max-arr-count', [static::class, 'maxArrCount'])
            ->addRule('min', [static::class, 'min'])
            ->addRule('max', [static::class, 'max'])
            ->addRule('greater-than', [static::class, 'greaterThan'])
            ->addRule('less-than', [static::class, 'lessThan'])
            ->addRule('alpha', [static::class, 'alpha'])
            ->addRule('alpha-numeric', [static::class, 'alphaNumeric'])
            ->addRule('min-str-len', [static::class, 'minStrLen'])
            ->addRule('max-str-len', [static::class, 'maxStrLen'])
            ->addRule('str-len', [static::class, 'strLen'])
            ->addRule('human-name', [static::class, 'humanName'])
            ->addRule('is', [static::class, 'is'])
            ->addRule('email', [static::class, 'email'])
            ->addRule('date', [static::class, 'date'])
            ->addRule('datetime', [static::class, 'datetime'])
            ->addRule('url', [static::class, 'url'])
            ->addRule('uuid', [static::class, 'uuid'])
            ->addRule('card-number', [static::class, 'cardNumber'])
            ->addRule('regex', [static::class, 'regex'])
            ->addRule('not-regex', [static::class, 'notRegex'])
            ->addRule('confirmed', [static::class, 'confirmed'])
            ->addRule('unique', [static::class, 'unique'])
            ->addRule('nullable', [static::class, 'nullable'])
            ->addRule('image', [static::class, 'image'])
            ->addRule('mimes', [static::class, 'mimes'])
            ->addRule('max-file-size', [static::class, 'maxFileSize'])
            ->addRule('boolean', [static::class, 'boolean'])
            ->addRule('integer', [static::class, 'integer'])
            ->addRule('string', [static::class, 'string'])
            ->addRule('array', [static::class, 'arrayRule'])
            ->addRule('timezone', [static::class, 'timezone'])
            ->addRule('exists', [static::class, 'exists'])
            ->addRule('numeric', [static::class, 'numeric'])
            ->addRule('after', [static::class, 'after']);
    }
}
