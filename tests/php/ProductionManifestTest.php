<?php
/**
 * Production manifest regression coverage.
 *
 * @package MPTYZen
 */

use PHPUnit\Framework\TestCase;

final class ProductionManifestTest extends TestCase {

	public function test_manifest_is_the_exact_reviewed_runtime_allowlist(): void {
		$manifest = require dirname( __DIR__, 2 ) . '/scripts/production-manifest.php';

		$this->assertSame(
			array(
				'LICENSE',
				'assets/css/admin.css',
				'assets/images/zen.svg',
				'assets/js/admin.js',
				'assets/js/classifier.js',
				'includes/class-mpty-zen.php',
				'readme.txt',
				'uninstall.php',
				'zen-by-mpty.php',
			),
			$manifest
		);
	}

	public function test_product_logo_matches_the_approved_portfolio_asset(): void {
		$logo = dirname( __DIR__, 2 ) . '/assets/images/zen.svg';

		$this->assertFileExists( $logo );
		$this->assertSame( 'ed863baebc11a26d17340d230e4e521cb089ee995d6658d58ab9a91fcc0ee84d', hash_file( 'sha256', $logo ) );
	}
}
