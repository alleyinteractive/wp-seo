<?php
/**
 * FakeAiModel class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Doubles;

use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * The model of the fake provider, which replies with the provider's text.
 */
final class FakeAiModel implements ModelInterface, TextGenerationModelInterface {
	/**
	 * Configuration the prompt was sent with.
	 *
	 * @var ModelConfig
	 */
	private ModelConfig $config;

	/**
	 * Set up.
	 *
	 * @param ModelMetadata    $metadata Metadata of the model.
	 * @param ProviderMetadata $provider Metadata of the provider.
	 */
	public function __construct(
		private readonly ModelMetadata $metadata,
		private readonly ProviderMetadata $provider,
	) {
		$this->config = new ModelConfig();
	}

	public function metadata(): ModelMetadata {
		return $this->metadata;
	}

	public function providerMetadata(): ProviderMetadata {
		return $this->provider;
	}

	public function setConfig( ModelConfig $config ): void {
		$this->config = $config;
	}

	public function getConfig(): ModelConfig {
		return $this->config;
	}

	public function generateTextResult( array $prompt ): GenerativeAiResult {
		FakeAiProvider::record( $prompt, $this->config->getSystemInstruction() );

		if ( FakeAiProvider::$exception ) {
			throw FakeAiProvider::$exception;
		}

		return new GenerativeAiResult(
			'fake-result',
			[ new Candidate( new ModelMessage( [ new MessagePart( FakeAiProvider::$response ) ] ), FinishReasonEnum::stop() ) ],
			new TokenUsage( 0, 0, 0 ),
			$this->provider,
			$this->metadata
		);
	}
}
