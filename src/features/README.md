# Features

Features should be PHP classes that implement the [Alley\WP\Types\Feature interface](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/types/interface-feature.php).

Features should be located in the `src/features` directory of the plugin and have namespace `Create_WordPress_Plugin\Features;`

The following variable is passed to the `Class_Hello` feature in each of the following examples. This shows how we can remove any business logic from the feature and pass it in when the feature is added.

```
$lyrics = "Hello, Dolly
    Well, hello, Dolly
    It's so nice to have you back where you belong
    You're lookin' swell, Dolly
    I can tell, Dolly
    You're still glowin', you're still crowin'
    You're still goin' strong
    I feel the room swayin'
    While the band's playin'
    One of our old favorite songs from way back when
    So, take her wrap, fellas
    Dolly, never go away again";
```

## Adding a feature

Files in the features directory will be autoloaded, but features will not be automatically instantiated. Features are typically instantiated in the plugin's `main()` function.

```
function main(): void {
    // Add features here.
    $plugin = new Group(
        new Features\Hello(
            title: 'Hello, Dolly',
            artist: 'Jerry Herman',
            lyrics: $lyrics,
        ),
    );
    $plugin->boot();
}
```

## Example feature class

This is a port of the infamous WordPress `hello.php` plugin to a feature. The lyrics would be passed in when the feature was called, as shown above.

```
<?php
/**
 * Feature implementation of hello.php
 *
 * @package Create_WordPress_Plugin
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;

/**
 * Hello class file
 */
final class Hello implements Feature {
    /**
     * Set up.
     *
     * @param string $title  The song title.
     * @param string $artist The song artist.
     * @param string $lyrics The song lyrics.
     */
    public function __construct(
        private readonly string $title,
        private readonly string $artist,
        private readonly string $lyrics,
    ) {}

    /**
     * Boot the feature.
     */
    public function boot(): void {
        add_action( 'admin_notices', [ $this, 'hello_dolly' ] );
        add_action( 'admin_head', [ $this, 'dolly_css' ] );
    }

    /**
     * Echos the chosen line.
     */
    public function hello_dolly(): void {
        $chosen = $this->get_lyric();
        $lang   = '';
        if ( 'en_' !== substr( get_user_locale(), 0, 3 ) ) {
            $lang = ' lang="en"';
        }

        printf(
            '<p id="dolly"><span class="screen-reader-text">%s </span><span dir="ltr"%s>%s</span></p>',
            esc_html(
                /* translators: 1: song title, 2: song artist */
				sprintf( __( 'Quote from %1$s song, by %2$s:' ), $this->title, $this->artist ),
			),
            esc_attr( $lang ),
            esc_html( $chosen )
        );
    }

    /**
     * Output css to position the paragraph.
     */
    public function dolly_css(): void {
        echo "
        <style type='text/css'>
        #dolly {
            float: right;
            padding: 5px 10px;
            margin: 0;
            font-size: 12px;
            line-height: 1.6666;
        }
        .rtl #dolly {
            float: left;
        }
        .block-editor-page #dolly {
            display: none;
        }
        @media screen and (max-width: 782px) {
            #dolly,
            .rtl #dolly {
                float: none;
                padding-left: 0;
                padding-right: 0;
            }
        }
        </style>
        ";
    }

    /**
     * Gets a random lyric from the lyric string.
     *
     * @return string
     */
    private function get_lyric(): string {
        // Here we split the lyrics into lines.
        $lyrics = explode( "\n", $this->lyrics );

        // And then randomly choose a line.
        return wptexturize( $lyrics[ wp_rand( 0, count( $lyrics ) - 1 ) ] );
    }
}
```