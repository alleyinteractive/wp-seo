# Meta Description Generator

The Meta Description Generator uses AI to write meta descriptions for posts.

| | |
| --- | --- |
| Handle | `description_generator` |
| Activation filter | `wp_seo_enable_description_generator` |
| Default | Off |

## Before you start

The Meta Description Generator sends prompts through WordPress's AI connectors. Make sure the site meets the [requirements for AI providers](../ai-providers.md#requirements).

## Turn on the feature

Like every WP SEO feature, the Meta Description Generator is off until you turn it on in code. Add the filter from a plugin or your theme's `functions.php`. WP SEO loads its features on the `after_setup_theme` hook, so the filter must be added before then.

```php
add_filter( 'wp_seo_enable_description_generator', __return_true(...) );
```

Then choose an AI provider on the **WP SEO > AI** page, or [set one in code](../ai-providers.md#in-code). To confirm that the feature is running, go to **WP SEO > Features**.

## What the AI provider receives

Every description takes one prompt to the AI provider. The prompt contains:

- The post's title, as stored.
- The post's content, rendered through `the_content` with the HTML removed. You can [change the content](#content) before and after it's rendered.
- Instructions to write a meta description optimized for search engines, summarizing the page in a neutral, factual tone, in the page's language, without promotional language, calls to action, questions, hashtags, or emoji.

The instructions ask for a description no longer than the maximum length, which is 160 characters by default.

The feature never sends a password-protected post, since a description is shown to everyone who views the post. It also returns an error for a post with no title and no content.

The feature removes quotation marks from the start and end of the description, in case the AI provider wraps it in quotes.

## Rate limit

Every request costs a prompt to the AI provider, so the REST route has a limit for each user: 30 requests an hour by default. The hour starts with the user's first request. The feature counts each request before it sends the prompt, so requests that fail still count.

When a user reaches the limit, the route responds with a 429 status and a `Retry-After` header that gives the number of seconds until the user can try again.

The limit applies only to the REST route. Calling `generate_description()` directly is not limited.

To change the limit, use the `wp_seo_description_generator_rate_limit` filter. Return 0 or less to turn the limit off.

```php
// Allow editors 100 descriptions an hour.
add_filter(
	'wp_seo_description_generator_rate_limit',
	fn ( int $limit, int $user_id ): int => user_can( $user_id, 'edit_others_posts' ) ? 100 : $limit,
	10,
	2
);
```

## Choose post types

By default, the feature works on every public post type shown in the admin, except attachments.

To change the list of post types, use the `wp_seo_description_generator_post_types` filter.

```php
// Don't generate descriptions for pages.
add_filter(
	'wp_seo_description_generator_post_types',
	fn ( array $post_types ): array => array_values( array_diff( $post_types, [ 'page' ] ) )
);
```

## Customize the prompt

### Content

To change the post's content before it's rendered, use the `wp_seo_description_generator_content_pre` filter. The filter receives the raw post content, so blocks and shortcodes are easier to remove before they expand. The feature then renders the result through `the_content`.

```php
// Leave shortcodes out of the prompt.
add_filter(
	'wp_seo_description_generator_content_pre',
	fn ( string $content, WP_Post $post ): string => strip_shortcodes( $content ),
	10,
	2
);
```

Other plugins can add text to every post through `the_content`, such as share buttons or related posts. To keep that text out of the prompt, remove it with the `wp_seo_description_generator_content` filter. The filter receives the rendered HTML, before the feature removes tags.

```php
add_filter(
	'wp_seo_description_generator_content',
	fn ( string $content, WP_Post $post ): string => preg_replace( '/<div class="share-buttons">.*?<\/div>/s', '', $content ),
	10,
	2
);
```

### Instructions

To change the instructions sent with the post, use the `wp_seo_description_generator_prompt` filter. The feature sends the post's title and content separately, so the instructions don't need to include them.

The default instructions include the maximum length. If you replace them, include a length yourself. You can get it from `Description_Generator::get_max_length()`.

```php
use Alley\WP\WP_SEO\Features\Description_Generator;

add_filter(
	'wp_seo_description_generator_prompt',
	fn ( string $instructions, WP_Post $post ): string => sprintf(
		'Write one meta description for this news article in AP style. Use no more than %d characters. Reply with only the meta description.',
		Description_Generator::get_max_length( $post )
	),
	10,
	2
);
```

### Maximum length

The maximum length is 160 characters by default. To change it, use the `wp_seo_description_generator_max_length` filter.

```php
add_filter( 'wp_seo_description_generator_max_length', fn (): int => 150 );
```

## Hooks

| Hook | Type | Use it to |
| --- | --- | --- |
| `wp_seo_enable_description_generator` | Filter | [Turn on the feature](#turn-on-the-feature). |
| `wp_seo_description_generator_post_types` | Filter | [Choose post types](#choose-post-types). |
| `wp_seo_description_generator_content_pre` | Filter | [Change the content before it's rendered](#content). |
| `wp_seo_description_generator_content` | Filter | [Change the content sent to the AI provider](#content). |
| `wp_seo_description_generator_prompt` | Filter | [Change the instructions](#instructions). |
| `wp_seo_description_generator_max_length` | Filter | [Change the maximum length](#maximum-length). |
| `wp_seo_description_generator_rate_limit` | Filter | [Change the rate limit](#rate-limit) for a user. |

## REST API

### `POST /wp-seo/v1/generate-description`

Generates a description for a post and returns it. It doesn't save the description. The route uses POST rather than GET because every request costs a prompt, so it must not be cached or prefetched.

The user must be able to edit the post, and the post type must be one the feature works on. Requests count toward the [rate limit](#rate-limit).

| Parameter | Type | Description |
| --- | --- | --- |
| `post_id` | integer | Required. ID of the post to describe. |

A successful response looks like this:

```json
{
	"description": "A guide to choosing and caring for houseplants in low light."
}
```

The description can be longer than the maximum length, since an editor reviews it before saving.

## PHP API

All methods are static methods of `Alley\WP\WP_SEO\Features\Description_Generator`.

| Method | Description |
| --- | --- |
| `generate_description( WP_Post $post )` | Returns a description for the post, or a `WP_Error`. It doesn't save the description, and the description can be longer than the maximum length. |
| `get_post_types()` | Returns the names of the post types the feature works on. |
| `get_max_length( WP_Post $post )` | Returns the maximum length of a description for the post, in characters. |

`generate_description()` doesn't count toward the rate limit.

```php
use Alley\WP\WP_SEO\Features\Description_Generator;

$description = Description_Generator::generate_description( get_post( 123 ) );

if ( ! is_wp_error( $description ) ) {
	// Show or save the description.
}
```

## Errors

These errors come from the REST route and the PHP methods. The REST route also returns the errors listed under [AI providers](../ai-providers.md#errors) when prompts can't be sent.

| Error code | Status | Cause |
| --- | --- | --- |
| `rest_post_invalid_id` | 404 | The post doesn't exist. REST route only. |
| `rest_forbidden` | 401 or 403 | The user can't edit the post, or the feature doesn't work on its post type. REST route only. |
| `wp_seo_description_rate_limited` | 429 | The user reached the [rate limit](#rate-limit). REST route only. |
| `wp_seo_description_password_protected` | 400 | The post is password protected. |
| `wp_seo_description_no_content` | 400 | The post has no title and no content. |
| `wp_seo_description_empty` | 502 | The AI provider returned an empty description. |

Other errors from sending the prompt, such as a missing API key, are the ones WordPress returns.

## Troubleshooting

**"No AI provider is selected on the WP SEO AI settings page."** Choose a provider on **WP SEO > AI**, or [set one in code](../ai-providers.md#in-code).

**An error mentions an API key or credentials.** Check the provider under **Settings > Connectors**. WP SEO doesn't check credentials until it sends a prompt.

**"Too many descriptions have been generated."** The user reached the [rate limit](#rate-limit). Wait for the time in the `Retry-After` header, or raise the limit.
