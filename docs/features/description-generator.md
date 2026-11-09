# Meta Description Generator

The Meta Description Generator uses AI to write meta descriptions for posts. Editors can generate a description in the block editor and review it before they save it. Developers can fill in missing descriptions in bulk with WP-CLI.

| | |
| --- | --- |
| Handle | `description_generator` |
| Activation filter | `wp_seo_enable_description_generator` |
| Default | Off |

## Before you start

The Meta Description Generator sends prompts through WordPress's AI connectors. Make sure the site meets the [requirements for AI providers](../ai-providers.md#requirements).

The feature writes the description to the `search_engine_description` post meta, which the **Search Engine** panel in the block editor edits. WP SEO registers this meta for posts and pages. To use the feature with other post types, see [Choose post types](#choose-post-types).

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

The instructions ask for a description shorter than the maximum length, since models often write more characters than they are asked for. With the default maximum of 160 characters, the instructions ask for 136.

The feature never sends a password-protected post, since a description is shown to everyone who views the post. It also returns an error for a post with no title and no content.

The feature removes quotation marks from the start and end of the description, in case the AI provider wraps it in quotes.

## Generate a description in the block editor

1. Open a post in the block editor and save it.
2. Open the **WP SEO** sidebar.
3. In the **Search Engine** panel, select **Generate description** under the **Description** field.
4. Review the description that fills the field, and edit it if needed.
5. Save the post.

The feature doesn't save the description for you. It stays in the field until you save the post.

The description comes from the last saved version of the post, so save your changes before you generate a description. On a new post, the button stays disabled until you save the post once.

A generated description can be longer than the maximum length. The editor still shows it, so that you can shorten it rather than generate another. Use the character count under the field to check it.

The button appears only when all of these are true:

- The feature is turned on.
- An AI provider is chosen, and the site meets the [requirements for AI providers](../ai-providers.md#requirements).
- The post type is one the feature works on. See [Choose post types](#choose-post-types).

Each user can generate 30 descriptions an hour in the block editor. See [Rate limit](#rate-limit).

## Generate descriptions in bulk with WP-CLI

The `wp wp-seo generate-descriptions` command generates a description for each post that doesn't have one, and saves it. The command is available only while the feature is turned on.

Nobody reviews these descriptions, so the command is stricter than the block editor:

- It never replaces an existing description. If an editor saves a description while the command is generating one, the command keeps the editor's description.
- It doesn't save a description that is longer than the maximum length. It logs the description and counts the post as failed.

Every post takes one prompt to the AI provider. Before a large run, use `--dry-run` to see which posts the command will send, and a small `--limit` to check the descriptions it writes.

```bash
wp wp-seo generate-descriptions --dry-run
```

```bash
wp wp-seo generate-descriptions --limit=10
```

### Options

| Option | Description | Default |
| --- | --- | --- |
| `--query=<json>` | WP_Query arguments for the posts to describe, as a JSON object. They replace the matching defaults. | Published posts without a password, of the post types the feature works on |
| `--limit=<number>` | The most posts to send to the AI provider in this run. | 100 |
| `--fault-tolerance=<percent>` | The percentage of the limit that may fail before the run stops. | 5 |
| `--dry-run` | List the posts the run would describe, without sending prompts or saving anything. | Off |
| `--rewind` | Start from the first post the next time the command runs with this `--query`. | Off |

The command always leaves out posts that already have a description, and always works through posts in order of ID, whatever `--query` says.

### Examples

Describe up to 10 pages by one author:

```bash
wp wp-seo generate-descriptions --query='{"post_type":"page","author":3}' --limit=10
```

Describe up to 500 posts, and stop if more than 50 of them fail:

```bash
wp wp-seo generate-descriptions --limit=500 --fault-tolerance=10
```

### How runs continue

The command saves its place after each post. The next run with the same `--query` value continues from there, so you can run the command again and again to work through a large site. Each `--query` value keeps its own place, so a run with one query doesn't skip posts for another.

A run passes over posts that failed. To try them again, rewind, and then run the command again:

```bash
wp wp-seo generate-descriptions --rewind
```

```bash
wp wp-seo generate-descriptions
```

The `--rewind` run only resets the place. It doesn't describe any posts. Use the same `--query` value for both runs.

A dry run starts from the saved place, but doesn't move it.

### When a run stops

A run stops when one of these happens:

- **It reaches the limit.** The next run continues where it stopped.
- **More posts fail than the fault tolerance allows.** The command rounds the allowed number down. With the defaults, 5 of 100 posts may fail. With a limit under 20 and the default fault tolerance, the first failure stops the run.
- **The AI provider is limiting requests or is out of quota.** Every prompt after this one would fail too. The command doesn't save its place, so the next run tries the same post again.

When an editor's description is kept, the command logs it, but doesn't count it as a failure.

## Rate limit

Every request costs a prompt to the AI provider, so the REST route that the block editor uses has a limit for each user: 30 requests an hour by default. The hour starts with the user's first request. The feature counts each request before it sends the prompt, so requests that fail still count.

When a user reaches the limit, the route responds with a 429 status and a `Retry-After` header that gives the number of seconds until the user can try again.

The limit applies only to the REST route. The WP-CLI command and the PHP methods are not limited.

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

By default, the feature works on every public post type shown in the admin, except attachments. It then leaves out any post type that doesn't register the `search_engine_description` post meta. WP SEO registers that meta for posts and pages.

To use the feature with another post type, register the meta for it. The post type must also support `custom-fields`, so that the block editor can read and save the meta. Register the meta on `init` before priority 99, which is when the feature adds the block editor button to its post types.

```php
add_action(
	'init',
	function () {
		register_post_meta(
			'book',
			'search_engine_description',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);
	}
);
```

To change the list of post types, use the `wp_seo_description_generator_post_types` filter. The feature removes any post type without the meta after the filter runs, so adding a post type here isn't enough on its own.

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

The default instructions include the length to aim for. If you replace them, include a length yourself. You can base it on `Description_Generator::get_max_length()`.

```php
use Alley\WP\WP_SEO\Features\Description_Generator;

add_filter(
	'wp_seo_description_generator_prompt',
	fn ( string $instructions, WP_Post $post ): string => sprintf(
		'Write one meta description for this news article in AP style. Use no more than %d characters. Reply with only the meta description.',
		(int) floor( Description_Generator::get_max_length( $post ) * 0.85 )
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
| `wp_seo_description_generator_cli_limit` | Filter | Change the default `--limit` of the WP-CLI command. |
| `wp_seo_description_generator_cli_fault_tolerance` | Filter | Change the default `--fault-tolerance` of the WP-CLI command. |

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
| `generate_and_save_description( WP_Post $post )` | Generates a description and saves it, as the WP-CLI command does. Returns the saved description, or a `WP_Error`. It never replaces an existing description, and doesn't save one that is too long. |
| `get_post_types()` | Returns the names of the post types the feature works on. |
| `get_max_length( WP_Post $post )` | Returns the maximum length of a description for the post, in characters. |

Neither method that generates a description counts toward the rate limit.

```php
use Alley\WP\WP_SEO\Features\Description_Generator;

$description = Description_Generator::generate_description( get_post( 123 ) );

if ( ! is_wp_error( $description ) ) {
	// Show or save the description.
}
```

To find posts without a description, set the `wp_seo_missing_description` query variable on a `WP_Query`. It works only while the feature is turned on. It relies on the `posts_where` filter, so with `get_posts()`, which turns off query filters, you must also set `'suppress_filters' => false`.

```php
$query = new WP_Query(
	[
		'post_type'                  => 'post',
		'wp_seo_missing_description' => true,
	]
);
```

## Errors

These errors come from the REST route, the WP-CLI command, and the PHP methods. The REST route also returns the errors listed under [AI providers](../ai-providers.md#errors) when prompts can't be sent.

| Error code | Status | Cause |
| --- | --- | --- |
| `rest_post_invalid_id` | 404 | The post doesn't exist. REST route only. |
| `rest_forbidden` | 401 or 403 | The user can't edit the post, or the feature doesn't work on its post type. REST route only. |
| `wp_seo_description_rate_limited` | 429 | The user reached the [rate limit](#rate-limit). REST route only. |
| `wp_seo_description_password_protected` | 400 | The post is password protected. |
| `wp_seo_description_no_content` | 400 | The post has no title and no content. |
| `wp_seo_description_empty` | 502 | The AI provider returned an empty description. |
| `wp_seo_description_provider_limited` | 429 | The AI provider is limiting requests or is out of quota. Available as `Description_Generator::ERROR_PROVIDER_LIMITED`. |
| `wp_seo_description_too_long` | | The description is longer than the maximum length, so it wasn't saved. The error data includes the description. `generate_and_save_description()` only. Available as `Description_Generator::ERROR_DESCRIPTION_TOO_LONG`. |
| `wp_seo_description_exists` | | An editor saved a description while one was being generated, and theirs was kept. `generate_and_save_description()` only. Available as `Description_Generator::ERROR_DESCRIPTION_EXISTS`. |

Other errors from sending the prompt, such as a missing API key, are the ones WordPress returns.

## Troubleshooting

**The Generate description button doesn't appear.** Check that the feature is turned on, that an AI provider is chosen on **WP SEO > AI**, and that the site runs WordPress 7.0 or later with AI turned on. If the post type isn't a post or page, check that it [registers the description meta](#choose-post-types) before `init` priority 99.

**The button is disabled.** Save the post. The button needs a saved version of the post to describe.

**"No AI provider is selected on the WP SEO AI settings page."** Choose a provider on **WP SEO > AI**, or [set one in code](../ai-providers.md#in-code).

**An error mentions an API key or credentials.** Check the provider under **Settings > Connectors**. WP SEO doesn't check credentials until it sends a prompt.

**"Too many descriptions have been generated."** The user reached the [rate limit](#rate-limit). Wait for the time in the `Retry-After` header, or raise the limit.

**The WP-CLI command says there are no post types to generate descriptions for.** No post type registers the description meta, or the `wp_seo_description_generator_post_types` filter removed them all. See [Choose post types](#choose-post-types).

**The WP-CLI command skips posts that failed before.** Run it with `--rewind`, and then run it again. See [How runs continue](#how-runs-continue).

**The WP-CLI command stopped because the AI provider is limiting requests.** Wait, or check the quota on your account with the AI provider. Then run the command again; it continues with the post that stopped it.
