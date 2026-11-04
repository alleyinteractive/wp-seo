<?php
/**
 * FakeAiProvider class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Doubles;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * An AI provider for tests, registered with the AI client WordPress sends
 * prompts through.
 *
 * It replies to every text prompt with the text a test set, and records the
 * prompt it was sent, so that a test can send a real prompt through core
 * without making a request to a real provider.
 */
final class FakeAiProvider implements ProviderInterface {
	/**
	 * ID of the provider, to send prompts to.
	 *
	 * @var string
	 */
	public const ID = 'wp_seo_fake';

	/**
	 * Text the provider replies with.
	 *
	 * @var string
	 */
	public static string $response = '';

	/**
	 * Text of the last prompt sent, or null if none was.
	 *
	 * @var string|null
	 */
	public static ?string $prompt = null;

	/**
	 * System instruction of the last prompt sent.
	 *
	 * @var string|null
	 */
	public static ?string $system_instruction = null;

	/**
	 * Register the provider, replying with the given text, and forget any
	 * prompt sent before.
	 *
	 * @param string $response Text to reply with.
	 */
	public static function register( string $response = '' ): void {
		AiClient::defaultRegistry()->registerProvider( self::class );

		self::$response           = $response;
		self::$prompt             = null;
		self::$system_instruction = null;
	}

	/**
	 * Record a prompt and the instruction it was sent with.
	 *
	 * @param Message[]   $prompt             Messages of the prompt.
	 * @param string|null $system_instruction System instruction.
	 */
	public static function record( array $prompt, ?string $system_instruction ): void {
		$text = '';

		foreach ( $prompt as $message ) {
			foreach ( $message->getParts() as $part ) {
				$text .= (string) $part->getText();
			}
		}

		self::$prompt             = $text;
		self::$system_instruction = $system_instruction;
	}

	public static function metadata(): ProviderMetadata {
		return new ProviderMetadata( self::ID, 'Fake', ProviderTypeEnum::cloud() );
	}

	public static function model( string $modelId, ?ModelConfig $modelConfig = null ): ModelInterface {
		$model = new FakeAiModel( self::model_metadata(), self::metadata() );

		if ( $modelConfig ) {
			$model->setConfig( $modelConfig );
		}

		return $model;
	}

	public static function availability(): ProviderAvailabilityInterface {
		return new class() implements ProviderAvailabilityInterface {
			public function isConfigured(): bool {
				return true;
			}
		};
	}

	public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new class() implements ModelMetadataDirectoryInterface {
			public function listModelMetadata(): array {
				return [ FakeAiProvider::model_metadata() ];
			}

			public function hasModelMetadata( string $modelId ): bool {
				return 'fake' === $modelId;
			}

			public function getModelMetadata( string $modelId ): ModelMetadata {
				return FakeAiProvider::model_metadata();
			}
		};
	}

	/**
	 * The provider's one model: text generation, with a system instruction.
	 *
	 * @return ModelMetadata
	 */
	public static function model_metadata(): ModelMetadata {
		return new ModelMetadata(
			'fake',
			'Fake',
			[ CapabilityEnum::textGeneration() ],
			[
				new SupportedOption( OptionEnum::systemInstruction() ),
				new SupportedOption( OptionEnum::inputModalities() ),
				new SupportedOption( OptionEnum::outputModalities() ),
			]
		);
	}
}
