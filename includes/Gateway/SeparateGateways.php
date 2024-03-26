<?php

declare(strict_types=1);

namespace Coinsnap\WC\Gateway;

use Coinsnap\WC\Helper\CoinsnapApiHelper;
use Coinsnap\WC\Helper\Logger;

//  Handles and initializes separate gateways.

class SeparateGateways {

	const GENERATED_PATH = COINSNAP_PLUGIN_FILE_PATH . 'generated';
	const PM_GENERATED_CACHE_KEY = 'coinsnap_payment_methods_generated';

	public static function generateClasses() {
		// Load payment methods from Coinsnap Server as separate gateways.
		if (get_option('coinsnap_separate_gateways') === 'yes') {
			if ( $separateGateways = \Coinsnap\WC\Helper\CoinsnapApiHelper::supportedPaymentMethods() ) {
				// Check if generated classes match cache.
				$generatedGateways = get_transient(self::PM_GENERATED_CACHE_KEY);
				if ($generatedGateways !== $separateGateways || self::generatedFilesExist() === false) {
					Logger::debug('Generating and writing separate gateway classes to filesystem.');
					self::initSeparatePaymentGateways( $separateGateways );
				} else {
					// Enable line below if you need to ensure payment classes are not generated on each request.
					// This was commented because it cluttered the debug log quite a lot as it fires multiple times per
					// request.
					Logger::debug('Using cache, skipping to generate separate gateway classes.');
				}
			}
		}
	}

	public static function initSeparatePaymentGateways(array $gateways) {
		$writtenFiles = 0;
		foreach ( $gateways as $gw ) {
			$className = $gw['className'];
			$fileName = $className . '.php';
			$symbol = $gw['symbol'];
			$id = 'coinsnap_' . strtolower($symbol);

			// Build the class structure.
			$classCode = "use Coinsnap\WC\Gateway\AbstractGateway;
			                class {$className} extends AbstractGateway {
			                    public function __construct() {
				                  \$this->id = '{$id}';
				                  parent::__construct();
				                  \$this->method_title = 'Coinsnap Gateway: {$symbol}';
				                  \$this->method_description = 'This is separate payment gateway managed by Coinsnap.';
				                  \$this->tokenType = \$this->getTokenType();
				                  \$this->primaryPaymentMethod = '{$symbol}';
			                    }

								public function getPaymentMethods(): array {
									return ['{$symbol}']; // todo: add feature to add other pm
		                        }

		                        public function getTitle(): string {
									return \$this->get_option('title', '{$symbol}');
								}

								public function init_form_fields() {
									parent::init_form_fields();
									\$this->form_fields += [
										'token_type' => [
											'title' => __( 'Token type', 'coinsnap-for-woocommerce' ),
											'type' => 'select',
											'options' => [
												'payment' => 'Payment',
												'promotion' => 'Promotion'
											],
											'default' => 'payment',
											'description' => __( 'Tokens of type promotion will not have a FIAT (USD, EUR, ..) exchange rate but counted as 1 per item quantity.', 'coinsnap-for-woocommerce' ),
											'desc_tip' => false,
										],
									];
								}
							}
						";

			// Write it to filesystem.
			if (!self::writeFile($fileName, $classCode)) {
				Logger::debug('Error writing generated separate payment gateway to filesystem.');
				Logger::debug('File: ' . $fileName);
			} else {
				$writtenFiles++;
			}
		}
		// Set cache for written files to avoid doing it every request, no expiration (will be cleared elsewhere)
		if ($writtenFiles > 0) {
			set_transient( self::PM_GENERATED_CACHE_KEY, $gateways,0 );
			Logger::debug("Successfully wrote ${writtenFiles} to filesystem.");
		}
	}

	public static function writeFile(string $fileName, string $fileContents): bool {
		$filePath = self::GENERATED_PATH . DIRECTORY_SEPARATOR . $fileName;
		// Create directory if it not exists.
		$directory = dirname( $filePath );
		if ( ! is_dir( $directory ) ) {
			Logger::debug('Directory "/generated" does not exist, creating it: ' . $directory);
                        $WP_Filesystem_ftpsockets = new WP_Filesystem_ftpsockets();
			if ( ! $WP_Filesystem_ftpsockets->mkdir( $directory,0750 )) {
				Logger::debug('Error creating directory, aborting.');
				return false;
			}
		}

		// Prepare file contents with php tags.
		$fileContents = '<?php' . PHP_EOL . $fileContents . PHP_EOL;
                $WP_Filesystem_SSH2 = new WP_Filesystem_SSH2();
		if ($WP_Filesystem_SSH2->put_contents($filePath, $fileContents) !== false) {
			return true;
		}

		return false;
	}

	public static function cleanUpGeneratedFilesAndCache(): bool {
		Logger::debug('Cleaning up generated separate payment gateway classes.');

		$hasErrors = false;

		// Delete cache.
		delete_transient(self::PM_GENERATED_CACHE_KEY);

		if (!is_dir(self::GENERATED_PATH)) {
			return false;
		}

		// Find generated classes.
		$files = glob(self::GENERATED_PATH . DIRECTORY_SEPARATOR . CoinsnapApiHelper::PM_CLASS_NAME_PREFIX . '*.php');

		foreach ($files as $file) {
			if (is_file($file)) {
				if (!wp_delete_file($file)) {
					Logger::debug('Could not delete file: ' . $file);
					$hasErrors = true;
				}
			}
		}

		if ($hasErrors === false) {
                    $WP_Filesystem_Direct = new WP_Filesystem_Direct();	
                    $WP_Filesystem_Direct->rmdir(self::GENERATED_PATH);
			Logger::debug('Successfully deleted generated classes files.');
			return true;
		}

		return false;
	}

	public static function generatedFilesExist(): bool {
		// Abort if dir does not exist.
		if (!is_dir(self::GENERATED_PATH)) {
			return false;
		}

		// Check if any generated files are present.
		if (count(scandir(self::GENERATED_PATH)) > 0) {
			return true;
		}

		return false;
	}
}
