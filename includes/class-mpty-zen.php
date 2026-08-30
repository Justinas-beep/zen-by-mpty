<?php
/**
 * Main plugin controller.
 *
 * @package MPTYZen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates Zen's settings page and conservative admin classifier.
 */
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
	public static function activate(): void {
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
	public static function maybe_migrate_legacy_settings(): void {
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
	public function register_settings(): void {
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
	public function register_settings_page(): void {
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
	public function enqueue_admin_assets( $hook_suffix ): void {
		$settings         = $this->get_settings();
		$is_settings_page = 'settings_page_zen-by-mpty' === $hook_suffix;

		if ( empty( $settings['enabled'] ) && ! $is_settings_page ) {
			return;
		}

		if ( $is_settings_page ) {
			wp_enqueue_style( 'common' );
			wp_add_inline_style(
				'common',
				'.mpty-zen-products{display:grid;gap:8px;max-width:760px;margin:12px 0 0}' .
				'.mpty-zen-product{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0;padding:12px 14px;border:1px solid #c3c4c7;border-radius:2px;background:#fff}' .
				'.mpty-zen-product-copy{min-width:0}' .
				'.mpty-zen-product-name{display:block;font-size:14px;line-height:1.4}' .
				'.mpty-zen-product-description{margin:2px 0 0;color:#50575e}' .
				'.mpty-zen-product-status{flex:0 0 auto;padding:1px 7px;border:1px solid #c3c4c7;border-radius:10px;background:#f6f7f7;color:#50575e;font-size:12px;line-height:1.5}' .
				'.mpty-zen-product-status.is-active{border-color:#8cba92;background:#f0f6f1;color:#005a24}' .
				'@media screen and (max-width:600px){.mpty-zen-product{align-items:flex-start;flex-direction:column;gap:8px}}'
			);
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
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		?>
		<div class="wrap mpty-zen-wrap">
			<h1><?php echo esc_html__( 'Zen by MPTY', 'zen-by-mpty' ); ?></h1>
			<p><?php echo esc_html__( 'A quieter WordPress admin, without changing how WordPress works.', 'zen-by-mpty' ); ?></p>
			<p><?php echo esc_html__( 'Zen conservatively hides promotional clutter. If it is unsure, it leaves the item visible.', 'zen-by-mpty' ); ?></p>

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
			<p id="mpty-zen-reveal-help" aria-live="polite"><?php echo esc_html__( 'Pause Zen to see everything plugins are displaying.', 'zen-by-mpty' ); ?></p>
			<p>
				<button type="button" class="button" id="mpty-zen-toggle-reveal" aria-pressed="false"><?php echo esc_html__( 'Pause Zen', 'zen-by-mpty' ); ?></button>
			</p>
			<p><small><?php echo esc_html( sprintf( /* translators: %s: plugin version. */ __( 'Version %s', 'zen-by-mpty' ), MPTY_ZEN_VERSION ) ); ?></small></p>

			<?php $this->render_mpty_products(); ?>
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
	private function render_checkbox_row( $key, $label, $description, $settings ): void {
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

	/**
	 * Render a restrained, local-only list of other MPTY products.
	 */
	private function render_mpty_products(): void {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();
		$products  = array(
			array(
				'name'        => 'Guard by MPTY',
				'plugin_file' => 'guard-by-mpty/guard-by-mpty.php',
				'description' => __( 'Local WordPress security and recovery tools.', 'zen-by-mpty' ),
			),
			array(
				'name'        => 'Sign by MPTY',
				'plugin_file' => 'sign-by-mpty/sign-by-mpty.php',
				'description' => __( 'Visitor registration, consent and electronic signature records.', 'zen-by-mpty' ),
			),
		);
		?>
		<hr>
		<h2 id="mpty-zen-products-title"><?php echo esc_html__( 'Other MPTY tools', 'zen-by-mpty' ); ?></h2>
		<p><?php echo esc_html__( 'A small selection of other tools from MPTY Projects.', 'zen-by-mpty' ); ?></p>
		<ul class="mpty-zen-products" aria-labelledby="mpty-zen-products-title">
			<?php foreach ( $products as $product ) : ?>
				<?php
				$product_status = $this->get_mpty_product_status( $product, $installed );
				$status_labels  = array(
					'active'        => __( 'Active', 'zen-by-mpty' ),
					'installed'     => __( 'Installed', 'zen-by-mpty' ),
					'not-installed' => __( 'Not installed', 'zen-by-mpty' ),
				);
				?>
				<li class="mpty-zen-product">
					<div class="mpty-zen-product-copy">
						<strong class="mpty-zen-product-name"><?php echo esc_html( $product['name'] ); ?></strong>
						<p class="mpty-zen-product-description"><?php echo esc_html( $product['description'] ); ?></p>
					</div>
					<span class="<?php echo esc_attr( 'mpty-zen-product-status is-' . $product_status ); ?>"><?php echo esc_html( $status_labels[ $product_status ] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Resolve an MPTY product's local installation and activation state.
	 *
	 * @param array{name:string,plugin_file:string,description:string} $product    Product definition.
	 * @param array<string,array<string,string>>                       $installed Installed plugin headers keyed by plugin file.
	 * @return string One of active, installed, or not-installed.
	 */
	private function get_mpty_product_status( array $product, array $installed ): string {
		$plugin_file = isset( $installed[ $product['plugin_file'] ] ) ? $product['plugin_file'] : '';

		if ( '' === $plugin_file ) {
			foreach ( $installed as $installed_file => $plugin_data ) {
				if ( isset( $plugin_data['Name'] ) && $product['name'] === $plugin_data['Name'] ) {
					$plugin_file = $installed_file;
					break;
				}
			}
		}

		if ( '' === $plugin_file ) {
			return 'not-installed';
		}

		return is_plugin_active( $plugin_file ) ? 'active' : 'installed';
	}
}
