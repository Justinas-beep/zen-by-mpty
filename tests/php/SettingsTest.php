<?php
/**
 * Settings and migration regression coverage.
 *
 * @package MPTYZen
 */

use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['mpty_zen_test_options'] = array();
	}

	public function test_settings_are_allowlisted_and_normalized(): void {
		$result = MPTY_Zen::instance()->sanitize_settings(
			array(
				'enabled'                   => 'yes',
				'hide_promotional_notices' => 0,
				'hide_review_nags'          => 1,
				'unexpected'                => 'discard me',
			)
		);

		$this->assertSame(
			array(
				'enabled'                   => 1,
				'hide_promotional_notices' => 0,
				'hide_review_nags'          => 1,
				'hide_promotional_ui'       => 0,
			),
			$result
		);
	}

	public function test_defaults_are_used_for_missing_or_invalid_storage(): void {
		$GLOBALS['mpty_zen_test_options']['mpty_zen_settings'] = 'invalid';
		$this->assertSame(
			array(
				'enabled'                   => 1,
				'hide_promotional_notices' => 1,
				'hide_review_nags'          => 1,
				'hide_promotional_ui'       => 1,
			),
			MPTY_Zen::instance()->get_settings()
		);
	}

	public function test_legacy_settings_migrate_once_and_drop_unknown_keys(): void {
		$GLOBALS['mpty_zen_test_options']['qrooom_zen_settings'] = array(
			'enabled'                   => 0,
			'hide_promotional_notices' => 1,
			'hide_review_nags'          => 0,
			'legacy_frontend_credit'    => 1,
		);

		MPTY_Zen::maybe_migrate_legacy_settings();

		$this->assertSame( 0, $GLOBALS['mpty_zen_test_options']['mpty_zen_settings']['enabled'] );
		$this->assertSame( 1, $GLOBALS['mpty_zen_test_options']['mpty_zen_settings']['hide_promotional_ui'] );
		$this->assertArrayNotHasKey( 'legacy_frontend_credit', $GLOBALS['mpty_zen_test_options']['mpty_zen_settings'] );
		$this->assertArrayNotHasKey( 'qrooom_zen_settings', $GLOBALS['mpty_zen_test_options'] );
		$this->assertSame( '0.6.0', $GLOBALS['mpty_zen_test_options']['mpty_zen_migration_050'] );
	}

	public function test_existing_canonical_settings_are_not_overwritten(): void {
		$canonical = array( 'enabled' => 0 );
		$GLOBALS['mpty_zen_test_options']['mpty_zen_settings'] = $canonical;
		$GLOBALS['mpty_zen_test_options']['qrooom_zen_settings'] = array( 'enabled' => 1 );

		MPTY_Zen::maybe_migrate_legacy_settings();

		$this->assertSame( $canonical, $GLOBALS['mpty_zen_test_options']['mpty_zen_settings'] );
	}
}
