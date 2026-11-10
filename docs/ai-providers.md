# AI providers

WP SEO features that use AI, such as the [Meta Description Generator](features/description-generator.md), send their prompts through the AI connectors built into WordPress. WP SEO doesn't store API keys or contact AI services on its own. You connect an AI service to WordPress, and then tell WP SEO which one to use.

## Requirements

- WordPress 7.0 or later. Earlier versions have no AI connectors, so WP SEO can't send prompts.
- AI must not be turned off on the site. WP SEO checks `wp_supports_ai()` before it sends a prompt.
- At least one AI provider connected under **Settings > Connectors**, with its provider plugin active.

WP SEO lists every AI provider whose plugin is active, but it doesn't check the provider's credentials, since that would take a request to the provider. A missing or wrong API key shows up as an error the first time a feature sends a prompt.

## Choose a provider

WP SEO sends prompts to one AI provider at most. Until you choose one, WP SEO sends no prompts, and features that use AI report that no provider is selected.

You can choose the provider on the settings page or set it in code. A provider set in code takes the place of the one chosen on the settings page.

### On the settings page

The **WP SEO > AI** page appears only while a feature that uses AI is turned on. You need the `manage_options` capability to use it.

1. Go to **WP SEO > AI**.
2. Choose a provider from the **AI provider** list.
3. Select **Save Changes**.

If no providers are available, the page links to **Settings > Connectors** instead. To stop sending prompts, choose **None**.

If the provider you chose is later disconnected, the list still shows it, labeled "unavailable," so that you can see where prompts are going. Prompts sent to it fail until you choose another provider or reconnect it.

WP SEO saves the choice in the `wp_seo_ai_provider` option.

### In code

Set the provider in code when it should be the same in every environment, or when site administrators shouldn't change it. Return a connector ID from the `wp_seo_ai_provider` filter:

```php
add_filter( 'wp_seo_ai_provider', fn () => 'anthropic' );
```

The connector ID is the key WordPress gives the connector in `wp_get_connectors()`.

The filter accepts three kinds of values:

| Return value | Result |
| --- | --- |
| A connector ID | WP SEO sends prompts to that provider. |
| An empty string | WP SEO sends no prompts. |
| `null` (the default) | WP SEO uses the provider chosen on the settings page. |

When the filter returns a string, the settings page shows the choice as read-only, with a note that it is set in code.

## Send prompts from your own feature

A feature that sends prompts gets its provider from `AI_Providers::get_prompt_provider()`. The method returns the connector ID, or a `WP_Error` if prompts can't be sent:

```php
use Alley\WP\WP_SEO\AI_Providers;

$provider = AI_Providers::get_prompt_provider();

if ( ! is_wp_error( $provider ) ) {
	$text = wp_ai_client_prompt( 'Summarize this post.' )->using_provider( $provider )->generate_text();
}
```

The feature should also register the settings page in its `boot()` method, so that the page appears while the feature is turned on:

```php
( new \Alley\WP\WP_SEO\AI_Settings() )->register();
```

### Methods

All methods are static methods of `Alley\WP\WP_SEO\AI_Providers`.

| Method | Returns |
| --- | --- |
| `get_prompt_provider()` | The connector ID to send prompts to, or a `WP_Error` if prompts can't be sent. |
| `get_provider()` | The connector ID of the chosen provider, or an empty string for none. It doesn't check whether prompts can be sent. |
| `get_available_providers()` | Provider names keyed by connector ID. Empty before WordPress 7.0 or when AI is turned off. |

### Errors

`get_prompt_provider()` returns one of these errors when prompts can't be sent. It doesn't check that the provider is still available; an unavailable provider returns an error when the prompt is sent.

| Error code | Cause |
| --- | --- |
| `wp_seo_ai_unsupported` | The site runs a version of WordPress earlier than 7.0, or AI is turned off. |
| `wp_seo_ai_no_provider` | No provider is chosen. The error data includes a 503 status. |
