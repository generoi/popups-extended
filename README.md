# popups-extended

> A Wordpress plugin that extends [popups](https://wordpress.org/plugins/popups/) primarily with Timber support, but also minor feature additions.

## Features

- Foundation reveal HTML by default.
- Easily overridable php or twig templates.
- [`wp-genero-analytics`](https://github.com/generoi/wp-genero-analytics) integration for conversions.
- TinyMCE buttons for conversion buttons (tracked through `wp-genero-analytics`).
- Exit intent trigger using [Bounceback](https://github.com/AMKohn/bounceback).
- AJAX based Gravityforms get automatic integration while redirected forms have to be tracked through URL parameters.
- Popup background image option.

## Overriding the `popup.php` template

The default template can be overridden in your theme using any of the following template suggestions:

    popups/popup--<post_id>.twig
    popups/popup-<post_id>.php
    popups/popup.twig
    popups/popup.php

## API Filters

#### Templates

```php
// Filter the Popup content
add_filter('spu/popup/content', function ($content, $post) {
}, 10, 2);

// Filter the popup template suggestions.
add_filter('spu_template_hierarchy', function ($templates) {
  foreach ($templates as $idx => $template) {
    $templates[$idx] = str_replace('popups/', '', $template);
  }
  return $templates;
}, 10, 2);

// Filter the resolved template path.
add_filter('spu_template', function ($template) {
});

// Filter the template to be included if available
add_filter('spu_template_include', function ($template) {
});
```

#### Popup options

```php
// Filter the popup types
add_filter('popups-extended/types', function ($types) {
  $types['popup'] = __('Popup');
  $types['slidein'] = __('Slide-in');
  return $types;
});

// Filter the themes.
add_filter('popups-extended/themes', function ($themes) {
  $themes['primary'] = __('Primary');
  return $themes;
});

// Filter the popup sizes.
add_filter('popups-extended/sizes', function ($sizes) {
  unset($sizes['tiny']);
  return $sizes;
});

// Filter the popup positions.
add_filter('popups-extended/positions', function ($positions) {
  $positions['left'] = __('Left');
  return $positions;
});
```
