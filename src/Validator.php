<?php

namespace NikitaKiselev\Validator;

use Exception;

class Validator
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $rules;

    /**
     * @var
     */
    private $errors;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * Extended validation rules
     *
     * @var array
     */
    protected $extendedRules = [];

    /**
     * @var string
     */
    private $lang;

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     */
    public function __construct(array $data, array $rules, $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->errors = [];
        $this->lang = 'en';
        $this->messages = require_once __DIR__."/../resources/lang/{$this->lang}.php";
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * Check validation for fail
     *
     * @return bool
     */
    public function fails()
    {
        $this->validate();

        return count($this->errors) > 0;
    }

    /**
     * Check validation for pass
     *
     * @return bool
     */
    public function pass()
    {
        $this->validate();

        return count($this->errors) === 0;
    }

    /**
     * Validate data
     *
     * @throws Exception
     */
    private function validate()
    {
        foreach ($this->data as $key => $value) {
            if (! isset($this->rules[$key])) {
                continue;
            }

            $rules = explode('|', $this->rules[$key]);

            foreach ($rules as $rule) {
                $parts = explode(':', $rule);
                $rule = array_shift($parts);
                $params = [$value, $key, $rule];

                if ($parts) {
                    $params = array_merge($params, explode(',', array_shift($parts)));
                }

                $validated = $this->validateWithRule($rule, $params);

                if (! $validated) {
                    $this->setError($key, $rule);
                    break;
                }
            }
        }
    }

    /**
     * @param $value
     * @return bool
     */
    public function validateRequired($value)
    {
        return (bool) $value;
    }

    /**
     * @param $value
     * @param $fieldName
     * @return bool
     */
    public function validateConfirmed($value, $fieldName)
    {
        return isset($this->data[$fieldName.'_confirmation']) && $value === $this->data[$fieldName.'_confirmation'];
    }

    /**
     * Validate password field
     *
     * @param $value
     * @param $fieldName
     * @param $rule
     * @return bool
     */
    public function validatePassword($value, $fieldName, $rule)
    {
        $min = 5;
        $max = 50;

        $this->messages[$rule] = str_replace(['{min}', '{max}'], [$min, $max], $this->messages[$rule]);

        return $this->validateMin($value, $fieldName, $rule, $min)
        && $this->validateMax($value, $fieldName, $rule, $max);
    }

    /**
     * @param $value
     * @param $field
     * @param $rule
     * @param $min
     * @return bool
     */
    public function validateMin($value, $field, $rule, $min)
    {
        $this->messages[$rule] = str_replace(['{min}'], [$min], $this->messages[$rule]);

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return mb_strlen($value) >= $min;
    }

    /**
     * @param $value
     * @param $field
     * @param $rule
     * @param $max
     * @return bool
     */
    public function validateMax($value, $field, $rule, $max)
    {
        $this->messages[$rule] = str_replace(['{max}'], [$max], $this->messages[$rule]);

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return mb_strlen($value) <= $max;
    }

    /**
     * Return validation errors
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get method of the givven rule
     *
     * @param $rule
     * @return string
     */
    private function getRuleMethod($rule)
    {
        return "validate".ucfirst(strtolower($rule));
    }

    /**
     * Validate with rule
     *
     * @param $rule
     * @param $params
     * @return mixed
     * @throws Exception
     */
    private function validateWithRule($rule, $params)
    {
        if (isset($this->extendedRules[$rule]) && is_callable($this->extendedRules[$rule])) {
            return call_user_func_array($this->extendedRules[$rule], $params);
        }

        $method = $this->getRuleMethod($rule);

        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $params);
        }

        throw new Exception("Method \"{$method}\" for rule \"{$rule}\" not found.");
    }

    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateEmail($value, $attribute, $rule)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param $rule
     * @param callable $function
     */
    public function extend($rule, callable $function)
    {
        $this->extendedRules[$rule] = $function;
    }

    /**
     * Set language for validator messages
     *
     * @param string $lang
     */
    public function setLanguage($lang)
    {
        // set default lang
        $this->lang = 'en';

        if (file_exists(__DIR__."/../resources/lang/{$lang}.php")) {
            $this->lang = $lang;
        }

        $this->messages = require_once __DIR__."/../resources/lang/{$this->lang}.php";
    }

    /**
     * Set message for rule
     *
     * @param $field
     * @param $message
     */
    public function setMessage($field, $message)
    {
        $this->messages[$field] = $message;
    }

    /**
     * Set validation error for field
     *
     * @param $field
     * @param $rule
     */
    protected function setError($field, $rule)
    {
        $message = isset($this->messages[$rule])
            ? $this->messages[$rule]
            : $rule . ' validation fail';

        if (isset($this->messages[$field.'.'.$rule])) {
            $message = $this->messages[$field.'.'.$rule];
        }

        $this->errors[$field] = $message;
    }
}
