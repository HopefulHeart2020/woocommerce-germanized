<?php
/**
 * Welcome Page Class
 *
 * Feature Overview
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WP.
 *
 * @author 		Vendidero
 * @version     1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Add Welcome Screen and Feature Overview
 *
 * @class 		WC_GZD_Admin_Welcome
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Admin_Welcome {

	private $plugin;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
		$this->plugin  = 'woocommerce-germanized/woocommerce-germanized.php';

		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome' ) );

	}

	/**
	 * Add admin menus/screens
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menus() {
		if ( empty( $_GET['page'] ) ) {
			return;
		}

		$welcome_page_name  = __( 'About WooCommerce Germanized', 'woocommerce-germanized' );
		$welcome_page_title = __( 'Welcome to WooCommerce Germanized', 'woocommerce-germanized' );

		switch ( $_GET['page'] ) {
			case 'wc-gzd-about' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-gzd-about', array( $this, 'about_screen' ) );
				add_action( 'admin_print_styles-'. $page, array( $this, 'admin_css' ) );
			break;
		}
	}

	/**
	 * admin_css function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_css() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'fontawesome', plugins_url(  '/assets/css/font-awesome.min.css', WC_GERMANIZED_PLUGIN_FILE ), array(), '4.2.0' );
		wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ), array(), WC_VERSION );
		wp_enqueue_style( 'woocommerce-gzd-activation', plugins_url(  '/assets/css/woocommerce-gzd-activation' . $suffix . '.css', WC_GERMANIZED_PLUGIN_FILE ), array(), WC_GERMANIZED_VERSION );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {

		remove_submenu_page( 'index.php', 'wc-gzd-about' );

	}

	/**
	 * Into text/links shown on all about pages.
	 *
	 * @access private
	 * @return void
	 */
	private function intro() {

		// Flush after upgrades
		if ( ! empty( $_GET['wc-gzd-updated'] ) || ! empty( $_GET['wc-gzd-installed'] ) )
			flush_rewrite_rules();

		// Drop minor version if 0
		$major_version = substr( WC_germanized()->version, 0, 3 );
		?>
        <style>
            .wc-gzd-admin-welcome-hide-pro .wc-germanized-welcome-pro {
                display: none;
            }
        </style>
		<div class="wc-gzd-news <?php echo ( WC_germanized()->is_pro() ? 'wc-gzd-admin-welcome-hide-pro' : '' ); ?>">
		
			<h1>Willkommen bei WooCommerce Germanized</h1>
			<a class="wc-gzd-logo" href="https://vendidero.de/woocommerce-germanized" target="_blank" style="margin-right: 1em"></a>
			<div class="about-text woocommerce-about-text">
				<?php
					if ( ! empty( $_GET['wc-gzd-installed'] ) )
						$message = 'Super, alles erledigt!';
					elseif ( ! empty( $_GET['wc-gzd-updated'] ) )
						$message = 'Danke, dass du auf die neueste Version aktualisiert hast!';
					else
						$message = 'Danke für die Installation!';
					echo $message . '<br/>';
				?>
				WooCommerce Germanized <?php echo $major_version; ?> erweitert deine WooCommerce Installation um wichtige Funktionen für den deutschen Markt.</p>
			</div>
			<p class="woocommerce-actions wc-gzd-actions">
				<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=germanized'); ?>" class="button button-primary">Einstellungen</a>
                <a href="https://vendidero.de/woocommerce-germanized#buy" target="_blank" class="button button-primary wc-germanized-welcome-pro">Upgrade zur Pro Version</a>
			</p>
			<div class="changelog vendipro new-feature">
                <h3>Rechtliche Checkboxen UI</h3>
                <div class="left">
                    <p>
                        Mit Version 2.0 von Germanized kannst du deine rechtlichen Checkboxen über ein übersichtliches User Interface verwalten.
                        Du kannst z.B. die Sortierung der verschiedenen Checkboxen via Drag & Drop anpassen und Attribute bearbeiten. Nutzer der Pro-Version können darüberhinaus eigene Checkboxen anlegen, bearbeiten und löschen.
                        Beim Bearbeiten der Checkbox kannst du einfach Attribute der Checkbox festlegen, darunter die Beschriftung, Fehlermeldung, ob es sich um ein Pflichtfeld handelt uvm.
                        Damit werden die Anpassungen an deinen Checkboxen nicht nur deutlich übersichtlicher sondern auch mit neuen Features versehen.
                        Die neuen rechtlichen Checkboxen kannst du <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized&section=checkboxes' ); ?>">hier</a> anpassen.
                    </p>
                    <p>
                        Weitere Informationen, auch zu den technischen Details der rechtl. Checkboxen findest du in unserer <a href="https://vendidero.de/dokument/woocommerce-germanized-rechtliche-checkboxen" target="_blank">Doku</a>.
                    </p>
                    <div class="vendipro-buttons wc-germanized-welcome-pro">
                        <a href="https://vendidero.de/woocommerce-germanized#pro" target="_blank" class="button button-primary wc-gzd-button">Pro Version entdecken</a>
                        <p class="price smaller">ab 69,95 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
                    </div>
                </div>
                <div class="right">
                    <img src="<?php echo WC_germanized()->plugin_url();?>/assets/images/checkboxes.png" />
                </div>
            </div>
			<div class="changelog vendipro new-feature wc-germanized-welcome-pro">
				<h3>Neu: USt.-ID in der Registrierung prüfen <span class="wc-gzd-pro">pro</span></h3>
				<div class="left">
					<a href="https://vendidero.de/woocommerce-germanized#vat" target="_blank"><img src="<?php echo WC_germanized()->plugin_url();?>/assets/images/vat-check.png" style="border: none" /></a>
				</div>
				<div class="right">
					<p>
                        Viele Shop-Betreiber verkaufen an Firmenkunden (teilweise ausschließlich) und möchten sicherstellen, dass Firmenkunden eine gültige USt.-ID besitzen.
                        Deshalb binden wir nun unseren USt.-ID-Check nun auf Wunsch auch in das Registrierungsformular ein. Bei Bedarf auch als Pflichtfeld.
					</p>
					<div class="wc-feature wc-vendipro-features feature-section col two-col">
						<div>
							<h4><i class="fa fa-magic"></i> USt.-ID-Check</h4>
							<p>USt.-ID bei der Registrierung abfragen, validieren und speichern. Die MwSt. wird anschließend u.U. abgezogen.</p>
						</div>
						<div class="last-feature">
							<h4><i class="fa fa-flag"></i> Basisland</h4>
							<p>Bei Bedarf kannst du selbst USt.-IDs deines Basislands abfragen und validieren lassen. Insbesondere für B2B Shops interessant.</p>
						</div>
					</div>
					<div class="vendipro-buttons">
						<a href="https://vendidero.de/woocommerce-germanized#pro" target="_blank" class="button button-primary wc-gzd-button">Pro Version entdecken</a>
						<p class="price smaller">ab 69,95 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
					</div>
				</div>
			</div>
			<div class="changelog vendipro new-feature wc-germanized-welcome-pro">
				<h3>Mehrstufige Kasse mit Datenüberprüfung <span class="wc-gzd-pro">pro</span></h3>
				<div class="left">
					<a href="https://vendidero.de/woocommerce-germanized#multistep-checkout" target="_blank"><img src="<?php echo WC_germanized()->plugin_url();?>/assets/images/multistep-checkout.png" /></a>
				</div>
				<div class="right">
					<p>
						Du möchtest deinen Checkout in mehrere Stufen aufteilen? Mit diesem neuen Feature ist das kein Problem mehr.
						Nutze ähnlich wie andere große deutsche Shops die Schritte Persönliche Daten, Zahlungsart und Bestätigen. Im Bestätigungs-Schritt
						werden dem Kunden alle Eingaben noch einmal aufgeführt.
					</p>
					<div class="wc-feature wc-vendipro-features feature-section col two-col">
						<div>
							<h4><i class="fa fa-paint-brush"></i> Läuft mit deinem Theme</h4>
							<p>Die mehrstufige Kasse kommt ganz ohne Überschreiben von WooCommerce Templates aus.</p>
						</div>
						<div class="last-feature">
							<h4><i class="fa fa-adjust"></i> Farben & Optionen</h4>
							<p>Passe sowohl Farben als auch Beschriftungen einfach in den Einstellungen an.</p>
						</div>
						<div>
							<h4><i class="fa fa-check"></i> Daten Prüfen</h4>
							<p>Lasse deine Kunden im letzten Schritt ihre Daten vor Bestellabschluss prüfen und u.U. korrigieren.</p>
						</div>
						<div class="last-feature">
							<h4><i class="fa fa-refresh"></i> Kein Neuladen</h4>
							<p>Die Mehrstufige Kasse funktioniert komplett per Javascript. Inhalte werden asynchron nachgeladen.</p>
						</div>
					</div>
					<div class="vendipro-buttons">
						<a href="https://vendidero.de/woocommerce-germanized#pro" target="_blank" class="button button-primary wc-gzd-button">Pro Version entdecken</a>
						<p class="price smaller">ab 69,95 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
					</div>
				</div>
			</div>
			<div class="changelog vendipro wc-germanized-welcome-pro">
				<h3>VendiPro - Das WooCommerce Theme für den deutschen Markt</h3>
				<div class="left">
					<a href="https://vendidero.de/vendipro" target="_blank"><img src="<?php echo WC_germanized()->plugin_url();?>/assets/images/vendidero.jpg" /></a>
				</div>
				<div class="right">
					<p>Endlich ist es soweit - Ein WooCommerce Theme, perfekt auf den deutschen Markt abgestimmt.
					Mit <a href="https://vendidero.de/vendipro" target="_blank">VendiPro</a> wirken alle WooCommerce & WooCommerce Germanized Inhalte einfach noch professioneller.</p>
					<div class="wc-feature wc-vendipro-features feature-section col two-col">
						<div>
							<h4><i class="fa fa-mobile"></i> Responsive Layout</h4>
							<p>VendiPro hinterlässt sowohl auf Desktop- als auch auf Mobilgeräten einen klasse Eindruck!</p>
						</div>
						<div class="last-feature">
							<h4><i class="fa fa-pencil"></i> Individualität</h4>
							<p>Passe VendiPro einfach per WordPress Theme Customizer an deine Bedürfnisse an.</p>
						</div>
						<div>
							<h4><i class="fa fa-font"></i> Typisch deutsch</h4>
							<p>Gemacht für den deutschen Markt - und das merkt man sofort.</p>
						</div>
						<div class="last-feature">
							<h4><i class="fa fa-play-circle"></i> Slideshow</h4>
							<p>Einfach per Shortcode Slideshows und Produkt Carousels erstellen.</p>
						</div>
					</div>
					<div class="vendipro-buttons">
						<a href="https://vendidero.de/vendipro" target="_blank" class="button button-primary wc-gzd-button">mehr erfahren</a>
						<p class="price smaller">ab 49,95 € inkl. MwSt. - inkl. 1 Jahr Updates & Premium Support!</p>
					</div>
				</div>
			</div>
			<div class="changelog">
				<h3>Neu in WooCommerce Germanized 2.0</h3>
				<div class="wc-feature feature-section col three-col" style="margin-bottom: -30px">
					<div>
						<h4><i class="fa fa-check-square"></i> Rechtliche Checkboxen</h4>
						<p>
                            Germanized bietet nun ein übersichtliches UI zur Verwaltung deiner rechtl. Checkboxen an. Du kannst z.B. selbst festlegen, an welchen Orten die Checkbox angezeigt werden soll. Nutzer der Pro-Version können eigene Checkboxen hinzufügen.
                        </p>
					</div>
					<div>
						<h4><i class="fa fa-star"></i> Bewertungserinnerung Opt-Out</h4>
						<p>
							Kunden von Trusted Shops können mit Hilfe von Germanized eine Bewertungserinnerung per E-Mail versenden - dafür gibt es nun eine separate Checkbox.
                            Zudem wird auf Wunsch in der Bestellbestätigung ein Abmelde-Link platziert.
						</p>
					</div>
					<div class="last-feature">

					</div>
				</div>
				<div class="return-to-dashboard">
					<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=germanized' ); ?>">zu den Einstellungen</a>
				</div>
			</div>
			<div class="changelog">
				<h3>WooCommerce Germanized - Funktionsübersicht</h3>
				<div class="wc-feature feature-section col three-col">
					<div>
						<h4><i class="fa fa-child"></i> Kleinunternehmerregelung</h4>
						<p>Mit nur einem Klick wird Dein Online-Shop §19 UStG - kompatibel! Einfach die Häkchen innerhalb der WooCommerce Germanized Einstellungen setzen und schon geht es los.</p>
					</div>
					<div>
						<h4><i class="fa fa-truck"></i> Lieferzeiten</h4>
						<p>Erstelle einfach neue Lieferzeiten für deine Produkte. Die Lieferzeiten werden dann sowohl auf der Produktseite als auch im Bestellvorgang dargestellt.
						Die Bearbeitung der Lieferzeiten erfolgt ganz bequem per WordPress Taxonomy.</p>
					</div>
					<div class="last-feature">
						<h4><i class="fa fa-laptop"></i> Darstellungsoptionen</h4>
						<p>Wir haben die Darstellung des Warenkorbs und des Bezahlvorgangs für Dich an deutsche Rechtsgrundlagen angepasst. Zusätzlich kannst Du selbst entscheiden, welche rechtlich relevanten Seiten Du wo und wie verlinken willst.</p>
					</div>
					<div>
						<h4><i class="fa fa-legal"></i> Rechtlich relevante Seiten</h4>
						<p>Erstelle ganz einfach alle rechtlich relevanten Seiten (z.B. Datenschutz, Widerrufsbelehrung).
						Wir setzen den Inhalt automatisch in die von Dir ausgewählten E-Mail-Templates ein und fügen auf Wunsch auch Checkboxen zum Bezahlvorgang hinzu.</p>
					</div>
					<div>
						<h4><i class="fa fa-certificate"></i> Trusted Shops</h4>
						<p>Du möchtest deine Trusted Shops Mitgliedschaft in WooCommerce nutzen? Kein Problem. WooCommerce Germanized hat die Schnittstelle zu Trusted Shops bereits implementiert.
						Klicke <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-settings&tab=germanized&section=trusted_shops' ), 'admin.php' ) ) ); ?>">hier</a> um die nötigen Einstellungen vorzunehmen.</p>
					</div>
					<div class="last-feature">
						<h4>Und noch vieles mehr</h4>
						<p>Natürlich gibt es auch noch viele weitere Optionen, die wir für Dich implementiert haben. Du kannst z.B. den Button-Text im Bestellabschluss ganz bequem anpassen oder entscheiden ob du den "zum Warenkorb" - Button wirklich auch in der Produktübersicht haben möchtest.</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the about screen.
	 */
	public function about_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<!--<div class="changelog point-releases"></div>-->

			

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-settings&tab=germanized' ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to WooCommerce Germanized Settings', 'woocommerce-germanized' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Sends user to the welcome page on first activation
	 */
	public function welcome() {
		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_wc_gzd_activation_redirect' ) ) {
			return;
	    }

		// Delete the redirect transient
		delete_transient( '_wc_gzd_activation_redirect' );

		// Bail if we are waiting to install or update via the interface update/install links
		if ( get_option( '_wc_gzd_needs_update' ) == 1 || get_option( '_wc_gzd_needs_pages' ) == 1 ) {
			return;
		}

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce-germanized.php' ) ) ) {
			return;
		}

		wp_redirect( admin_url( 'index.php?page=wc-gzd-about' ) );
		exit;
	}
}

new WC_GZD_Admin_Welcome();

?>