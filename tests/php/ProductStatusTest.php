<?php
/**
 * MPTY product-state regression coverage.
 *
 * @package MPTYZen
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers MPTY product state detection.
 */
final class ProductStatusTest extends TestCase {

	/** Reset active-plugin state between tests. */
	protected function setUp(): void {
		$GLOBALS['mpty_zen_test_active_plugins'] = array();
	}

	/** Standard plugin paths retain WordPress active-state detection. */
	public function test_standard_installation_is_reported_active_when_active(): void {
		$GLOBALS['mpty_zen_test_active_plugins'] = array( 'guard-by-mpty/guard-by-mpty.php' );

		$this->assertSame(
			'active',
			$this->get_status(
				array( 'guard-by-mpty/guard-by-mpty.php' => array( 'Name' => 'Guard by MPTY' ) )
			)
		);
	}

	/** Product names resolve active plugins in nonstandard directories. */
	public function test_nonstandard_installation_is_resolved_by_product_name(): void {
		$GLOBALS['mpty_zen_test_active_plugins'] = array( 'custom-guard/plugin.php' );

		$this->assertSame(
			'active',
			$this->get_status(
				array( 'custom-guard/plugin.php' => array( 'Name' => 'Guard by MPTY' ) )
			)
		);
	}

	/** Present but inactive plugins are reported as installed. */
	public function test_inactive_installation_is_reported_installed(): void {
		$this->assertSame(
			'installed',
			$this->get_status(
				array( 'custom-guard/plugin.php' => array( 'Name' => 'Guard by MPTY' ) )
			)
		);
	}

	/** Missing products are reported as not installed. */
	public function test_absent_product_is_reported_not_installed(): void {
		$this->assertSame( 'not-installed', $this->get_status( array() ) );
	}

	/**
	 * Invoke the private status resolver without widening its production API.
	 *
	 * @param array<string,array<string,string>> $installed Installed plugin data.
	 */
	private function get_status( array $installed ): string {
		$method = new ReflectionMethod( MPTY_Zen::class, 'get_mpty_product_status' );
		$method->setAccessible( true );

		return $method->invoke(
			MPTY_Zen::instance(),
			array(
				'name'        => 'Guard by MPTY',
				'plugin_file' => 'guard-by-mpty/guard-by-mpty.php',
				'description' => 'Description',
			),
			$installed
		);
	}
}
