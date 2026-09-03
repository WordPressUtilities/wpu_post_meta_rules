# WPU Post Meta Rules

[![PHP workflow](https://github.com/WordPressUtilities/wpu_post_meta_rules/actions/workflows/php.yml/badge.svg 'PHP workflow')](https://github.com/WordPressUtilities/wpu_post_meta_rules/actions)

Block publication if some post metas dont match the defined rules

## Usage

Define the rules via the `wpu_post_meta_rules__fields` filter. Each key is a post meta key, with `name` (label used in error messages), `min` / `max` (character length constraints) and an optional `post_types` array to restrict which post types the rule applies to.

```php
add_filter('wpu_post_meta_rules__fields', function ($fields) {
    $fields['wpuseo_post_title'] = array(
        'name' => 'SEO title',
        'min' => 10,
        'max' => 70
    );
    $fields['wpuseo_post_description'] = array(
        'name' => 'SEO description',
        'min' => 10,
        'max' => 160
    );
    return $fields;
});
```
