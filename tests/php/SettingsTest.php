<?php
/**
 * Settings regression coverage.
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
}
