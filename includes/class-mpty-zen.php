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
	 * Register hooks.
	 */
	private function __construct() {
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
			wp_enqueue_style(
				'mpty-zen-admin',
				MPTY_ZEN_URL . 'assets/css/admin.css',
				array(),
				MPTY_ZEN_VERSION
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
					'pause'        => __( 'Pause Zen', 'zen-by-mpty' ),
					'resume'       => __( 'Resume Zen', 'zen-by-mpty' ),
					'active'       => __( 'Active', 'zen-by-mpty' ),
					'paused'       => __( 'Paused', 'zen-by-mpty' ),
					'inactive'     => __( 'Inactive', 'zen-by-mpty' ),
					'activeHelp'   => __( 'Pause Zen to see everything plugins are displaying.', 'zen-by-mpty' ),
					'pausedHelp'   => __( 'Zen is paused. Promotional content is currently visible.', 'zen-by-mpty' ),
					'inactiveHelp' => __( 'Enable Zen and save your settings to begin hiding promotional clutter.', 'zen-by-mpty' ),
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
		<div class="wrap mpty-zen-wrap mpty-ui" data-mpty-ui="zen">
			<header class="mpty-ui__header">
				<div class="mpty-ui__product-lockup">
					<span class="mpty-ui__product-icon-box">
						<svg class="mpty-ui__product-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M3 6h18"></path>
							<path d="M5 12h14"></path>
							<path d="M8 18h8"></path>
						</svg>
					</span>
					<div>
						<h1><?php echo esc_html__( 'Zen by MPTY', 'zen-by-mpty' ); ?></h1>
						<p class="mpty-ui__product-purpose"><?php echo esc_html__( 'A quieter WordPress admin, without changing how WordPress works.', 'zen-by-mpty' ); ?></p>
					</div>
				</div>
				<div class="mpty-ui__header-state">
					<span id="mpty-zen-status" class="mpty-ui__badge <?php echo ! empty( $settings['enabled'] ) ? 'mpty-ui__badge--good' : ''; ?>" aria-live="polite"><?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Active', 'zen-by-mpty' ) : esc_html__( 'Inactive', 'zen-by-mpty' ); ?></span>
				</div>
			</header>

			<div class="mpty-ui__content">
				<div class="mpty-ui__columns">
					<section class="mpty-ui__panel" aria-labelledby="mpty-zen-settings-title">
						<div class="mpty-ui__panel-heading">
							<div>
								<h2 id="mpty-zen-settings-title"><?php echo esc_html__( 'Suppression settings', 'zen-by-mpty' ); ?></h2>
								<p><?php echo esc_html__( 'Choose which kinds of promotional clutter Zen should hide.', 'zen-by-mpty' ); ?></p>
							</div>
						</div>
						<form method="post" action="options.php">
							<?php settings_fields( 'mpty_zen' ); ?>
							<?php $this->render_checkbox_row( 'enabled', __( 'Zen', 'zen-by-mpty' ), __( 'Hide eligible promotional clutter across WordPress admin.', 'zen-by-mpty' ), $settings ); ?>
							<?php $this->render_checkbox_row( 'hide_promotional_notices', __( 'Promotional notices', 'zen-by-mpty' ), __( 'Hide offers, sales, upgrades and product promotions.', 'zen-by-mpty' ), $settings ); ?>
							<?php $this->render_checkbox_row( 'hide_review_nags', __( 'Review requests', 'zen-by-mpty' ), __( 'Hide requests to rate or review plugins and themes.', 'zen-by-mpty' ), $settings ); ?>
							<?php $this->render_checkbox_row( 'hide_promotional_ui', __( 'Other promotions', 'zen-by-mpty' ), __( 'Hide promotional banners, cards and upsell panels.', 'zen-by-mpty' ), $settings ); ?>
							<div class="mpty-ui__actions"><?php submit_button( __( 'Save settings', 'zen-by-mpty' ) ); ?></div>
						</form>
					</section>

					<div>
						<section id="mpty-zen-pause-panel" class="mpty-ui__panel <?php echo empty( $settings['enabled'] ) ? 'mpty-ui__panel--inactive' : ''; ?>" aria-labelledby="mpty-zen-pause-title">
							<div class="mpty-ui__panel-heading">
								<div>
									<h2 id="mpty-zen-pause-title"><?php echo esc_html__( 'Temporarily show hidden items', 'zen-by-mpty' ); ?></h2>
									<p><?php echo esc_html__( 'Pause only affects this browser session. Your saved settings stay unchanged.', 'zen-by-mpty' ); ?></p>
								</div>
							</div>
							<div class="mpty-ui__status-note">
								<strong><?php echo esc_html__( 'Current state', 'zen-by-mpty' ); ?></strong>
								<p id="mpty-zen-reveal-help" aria-live="polite"><?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Pause Zen to see everything plugins are displaying.', 'zen-by-mpty' ) : esc_html__( 'Enable Zen and save your settings to begin hiding promotional clutter.', 'zen-by-mpty' ); ?></p>
							</div>
							<div class="mpty-ui__actions">
								<button type="button" class="mpty-ui__button" id="mpty-zen-toggle-reveal" aria-pressed="false" <?php disabled( empty( $settings['enabled'] ) ); ?>><?php echo esc_html__( 'Pause Zen', 'zen-by-mpty' ); ?></button>
							</div>
						</section>
					</div>
				</div>

				<?php $this->render_mpty_products(); ?>
			</div>
			<footer class="mpty-ui__footer"><?php echo esc_html( sprintf( /* translators: %s: plugin version. */ __( 'Zen by MPTY · Version %s · Runs locally in your WordPress admin.', 'zen-by-mpty' ), MPTY_ZEN_VERSION ) ); ?></footer>
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
		<div class="mpty-ui__setting">
			<div>
				<h3><?php echo esc_html( $label ); ?></h3>
				<p id="<?php echo esc_attr( $field_id . '-description' ); ?>"><?php echo esc_html( $description ); ?></p>
			</div>
			<label class="mpty-ui__switch" for="<?php echo esc_attr( $field_id ); ?>">
				<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: setting name. */ __( 'Enable %s', 'zen-by-mpty' ), $label ) ); ?></span>
				<input id="<?php echo esc_attr( $field_id ); ?>" type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" aria-describedby="<?php echo esc_attr( $field_id . '-description' ); ?>" <?php checked( $settings[ $key ], 1 ); ?>>
				<span aria-hidden="true"></span>
			</label>
		</div>
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
		<section class="mpty-ui__panel" aria-labelledby="mpty-zen-products-title">
			<div class="mpty-ui__panel-heading">
				<div>
					<h2 id="mpty-zen-products-title"><?php echo esc_html__( 'Other MPTY tools', 'zen-by-mpty' ); ?></h2>
					<p><?php echo esc_html__( 'A small selection of other tools from MPTY Projects.', 'zen-by-mpty' ); ?></p>
				</div>
			</div>
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
					<span class="<?php echo esc_attr( 'mpty-ui__badge mpty-zen-product-status is-' . $product_status ); ?>"><?php echo esc_html( $status_labels[ $product_status ] ); ?></span>
				</li>
			<?php endforeach; ?>
			</ul>
		</section>
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
