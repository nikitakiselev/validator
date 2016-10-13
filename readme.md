# Validator

THIS IS ALPHA VERSION.

This is a validation library, like Laravel illuminate\Validation, but has not a lot of dependencies, and more simpler.

## Installation

```
composer require nikitakiselev/validator dev-master
```

## Usage

```php
use NikitaKiselev\Validator\Validator;

$data = [
    'username' => 'required|max:50',
    'email' => 'required|email',
];

$v = new Validator($data, [
    'username' => 'required|max:50',
    'email' => 'required|email',
]);

$v->fails(); // return false
$v->pass(); // return true
$v->errors(); // return []
```

## Add custom rules

For adding your custom rule, you can call `extend` method

```php
$v->extend('max', function ($value, $field, $rule, $max) {
    return $value > $max;
});
```

## Change message language

```php
$v->setLanguage('ru');
```

## Set custom message for validation rule

```php
$v->setMessage('required', 'This is custom validation error for "required" rule');
$v->setMessage('username.required', 'This is custom validation error for "required" rule and "username" field');
```