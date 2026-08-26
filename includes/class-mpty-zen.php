<?php
/**
 * Main plugin controller.
 *
 * @package MPTYZen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MPTY_Zen {

	/**
	 * Singleton instance.
	 *
	 * @var MPTY_Zen|null
	 */
	private static $instance = null;

	/**
	 * Settings option name.
	 */
	private const OPTION_NAME = 'mpty_zen_settings';

	/**
	 * Legacy development option name.
	 */
	private const LEGACY_ZEN_OPTION_NAME = 'qrooom_zen_settings';

	/**
	 * Earlier Clean development option name.
	 */
	private const LEGACY_CLEAN_OPTION_NAME = 'qrooom_clean_settings';

	/**
	 * Previous Zen development migration marker.
	 */
	private const LEGACY_ZEN_MIGRATION_OPTION = 'qrooom_zen_migration_040';

	/**
	 * One-time migration marker.
	 */
	private const MIGRATION_OPTION = 'mpty_zen_migration_050';

	/**
	 * Get the singleton instance.
	 *
	 * @return MPTY_Zen
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Run activation tasks.
	 */
	public static function activate() {
		self::maybe_migrate_legacy_settings();
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_migrate_legacy_settings' ), 1 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MPTY_ZEN_FILE ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Default settings.
	 *
	 * Important-notice protection is intentionally not configurable. It is a
	 * product safety boundary, not a preference.
	 *
	 * @return array<string,int>
	 */
	private static function default_settings() {
		return array(
			'enabled'                   => 1,
			'hide_promotional_notices' => 1,
			'hide_review_nags'          => 1,
			'hide_promotional_ui'       => 1,
		);
	}

	/**
	 * Migrate development-era settings to the canonical MPTY namespace once.
	 *
	 * The old frontend-credit and safety-toggle settings are deliberately not
	 * migrated because those capabilities are not part of Zen's product scope.
	 */
	public static function maybe_migrate_legacy_settings() {
		if ( get_option( self::MIGRATION_OPTION, false ) ) {
			return;
		}

		$current      = get_option( self::OPTION_NAME, null );
		$legacy_zen   = get_option( self::LEGACY_ZEN_OPTION_NAME, null );
		$legacy_clean = get_option( self::LEGACY_CLEAN_OPTION_NAME, null );

		if ( ! is_array( $current ) ) {
			$source = is_array( $legacy_zen ) ? $legacy_zen : $legacy_clean;

			if ( is_array( $source ) ) {
				$defaults = self::default_settings();
				$migrated = array();

				foreach ( $defaults as $key => $default ) {
					$migrated[ $key ] = array_key_exists( $key, $source ) ? ( empty( $source[ $key ] ) ? 0 : 1 ) : $default;
				}

				add_option( self::OPTION_NAME, $migrated, '', false );
				$current = get_option( self::OPTION_NAME, null );

				// Never retire a legacy source unless the canonical MPTY option exists.
				if ( ! is_array( $current ) ) {
					return;
				}
			}
		}

		// Zen has not had a public release, so once canonical settings are safely
		// present, retire development-era option keys instead of dual-reading forever.
		delete_option( self::LEGACY_ZEN_OPTION_NAME );
		delete_option( self::LEGACY_CLEAN_OPTION_NAME );
		delete_option( self::LEGACY_ZEN_MIGRATION_OPTION );
		add_option( self::MIGRATION_OPTION, MPTY_ZEN_VERSION, '', false );
	}

	/**
	 * Read settings with safe defaults.
	 *
	 * @return array<string,int>
	 */
	public function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::default_settings() );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'mpty_zen',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	/**
	 * Normalize checkbox settings to strict 0/1 values.
	 *
	 * @param mixed $input Raw settings payload.
	 * @return array<string,int>
	 */
	public function sanitize_settings( $input ) {
		$defaults = self::default_settings();
		$output   = array();
		$input    = is_array( $input ) ? $input : array();

		foreach ( $defaults as $key => $default ) {
			$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		return $output;
	}

	/**
	 * Register the settings screen under Settings.
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Zen by MPTY', 'zen-by-mpty' ),
			__( 'Zen by MPTY', 'zen-by-mpty' ),
			'manage_options',
			'zen-by-mpty',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add Settings link on Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=zen-by-mpty' ) ) . '">' . esc_html__( 'Settings', 'zen-by-mpty' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Load the classifier in wp-admin only.
	 *
	 * @param string $hook_suffix Current admin screen hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$settings         = $this->get_settings();
		$is_settings_page = 'settings_page_zen-by-mpty' === $hook_suffix;

		if ( empty( $settings['enabled'] ) && ! $is_settings_page ) {
			return;
		}

		wp_enqueue_script(
			'mpty-zen-classifier',
			MPTY_ZEN_URL . 'assets/js/classifier.js',
			array(),
			MPTY_ZEN_VERSION,
			array( 'in_footer' => false )
		);

		wp_enqueue_script(
			'mpty-zen-admin',
			MPTY_ZEN_URL . 'assets/js/admin.js',
			array( 'mpty-zen-classifier' ),
			MPTY_ZEN_VERSION,
			array( 'in_footer' => false )
		);

		wp_localize_script(
			'mpty-zen-admin',
			'MPTYZen',
			array(
				'settings'       => array(
					'enabled'                  => (bool) $settings['enabled'],
					'hidePromotionalNotices' => (bool) $settings['hide_promotional_notices'],
					'hideReviewNags'          => (bool) $settings['hide_review_nags'],
					'hidePromotionalUI'       => (bool) $settings['hide_promotional_ui'],
				),
				'isSettingsPage' => $is_settings_page,
				'revealKey'      => 'mpty_zen_reveal_v1',
				'debug'          => defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'MPTY_ZEN_DEBUG' ) && MPTY_ZEN_DEBUG,
				'strings'        => array(
					'pause'      => __( 'Pause Zen', 'zen-by-mpty' ),
					'resume'     => __( 'Resume Zen', 'zen-by-mpty' ),
					'activeHelp' => __( 'Pause Zen to see everything plugins are displaying.', 'zen-by-mpty' ),
					'pausedHelp' => __( 'Zen is paused. Promotional content is currently visible.', 'zen-by-mpty' ),
				),
			)
		);
	}

	/**
	 * Render plugin settings.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		?>
		<div class="wrap mpty-zen-wrap">
			<h1><?php echo esc_html__( 'Zen by MPTY', 'zen-by-mpty' ); ?></h1>
			<p><?php echo esc_html__( 'Keep WordPress admin focused by hiding promotional clutter.', 'zen-by-mpty' ); ?></p>
			<p><?php echo esc_html__( 'Important notices stay visible. If Zen is unsure, it leaves the item visible.', 'zen-by-mpty' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'mpty_zen' ); ?>
				<table class="form-table" role="presentation">
					<?php $this->render_checkbox_row( 'enabled', __( 'Zen', 'zen-by-mpty' ), __( 'Enabled', 'zen-by-mpty' ), $settings ); ?>
					<?php $this->render_checkbox_row( 'hide_promotional_notices', __( 'Promotional notices', 'zen-by-mpty' ), __( 'Hide offers, sales, upgrades and product promotions.', 'zen-by-mpty' ), $settings ); ?>
					<?php $this->render_checkbox_row( 'hide_review_nags', __( 'Review requests', 'zen-by-mpty' ), __( 'Hide requests to rate or review plugins and themes.', 'zen-by-mpty' ), $settings ); ?>
					<?php $this->render_checkbox_row( 'hide_promotional_ui', __( 'Other promotions', 'zen-by-mpty' ), __( 'Hide promotional banners, cards and upsell panels.', 'zen-by-mpty' ), $settings ); ?>
				</table>
				<?php submit_button( __( 'Save settings', 'zen-by-mpty' ) ); ?>
			</form>

			<hr>
			<h2><?php echo esc_html__( 'Temporarily show hidden items', 'zen-by-mpty' ); ?></h2>
			<p id="mpty-zen-reveal-help"><?php echo esc_html__( 'Pause Zen to see everything plugins are displaying.', 'zen-by-mpty' ); ?></p>
			<p>
				<button type="button" class="button" id="mpty-zen-toggle-reveal" aria-pressed="false"><?php echo esc_html__( 'Pause Zen', 'zen-by-mpty' ); ?></button>
			</p>
			<p><small><?php echo esc_html( sprintf( /* translators: %s: plugin version. */ __( 'Version %s', 'zen-by-mpty' ), MPTY_ZEN_VERSION ) ); ?></small></p>
		</div>
		<?php
	}

	/**
	 * Render one native WordPress checkbox setting.
	 *
	 * @param string            $key         Setting key.
	 * @param string            $label       Visible label.
	 * @param string            $description Visible description.
	 * @param array<string,int> $settings    Current settings.
	 */
	private function render_checkbox_row( $key, $label, $description, $settings ) {
		$field_id = 'mpty-zen-' . sanitize_html_class( $key );
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label for="<?php echo esc_attr( $field_id ); ?>">
					<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings[ $key ], 1 ); ?>>
					<?php echo esc_html( $description ); ?>
				</label>
			</td>
		</tr>
		<?php
	}
}
