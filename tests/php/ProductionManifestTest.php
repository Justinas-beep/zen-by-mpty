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
}
