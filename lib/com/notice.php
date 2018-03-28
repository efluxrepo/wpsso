<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomNotice' ) ) {

	class SucomNotice {

		protected $p;
		protected $lca = 'sucom';
		protected $text_domain = 'sucom';
		protected $label_transl = '';
		protected $opt_name = 'sucom_notices';
		protected $dis_name = 'sucom_dismissed';
		protected $hide_err = false;
		protected $hide_warn = false;
		protected $has_shown = false;
		protected $all_types = array( 'nag', 'err', 'warn', 'upd', 'inf' );
		protected $ref_cache = array();
		protected $notice_cache = array();

		private $ajax_notice = null;

		public $enabled = true;

		public function __construct( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

			static $do_once = null;	// just in case

			if ( null === $do_once ) {

				$do_once = true;

				$this->set_config( $plugin, $lca, $text_domain, $label_transl );

				$this->add_actions();
			}
		}

		/**
		 * Set property values for text domain, notice label, etc.
		 */
		private function set_config( $plugin = null, $lca = null, $text_domain = null, $label_transl = null ) {

			if ( $plugin !== null ) {
				$this->p =& $plugin;
				if ( ! empty( $this->p->debug->enabled ) ) {
					$this->p->debug->mark();
				}
			}

			if ( $lca !== null ) {
				$this->lca = $lca;
			} elseif ( ! empty( $this->p->lca ) ) {
				$this->lca = $this->p->lca;
			}

			if ( $text_domain !== null ) {
				$this->text_domain = $text_domain;
			} elseif ( ! empty( $this->p->cf['plugin'][$this->lca]['text_domain'] ) ) {
				$this->text_domain = $this->p->cf['plugin'][$this->lca]['text_domain'];
			}

			if ( $label_transl !== null ) {
				$this->label_transl = $label_transl;	// argument is already translated
			} elseif ( ! empty( $this->p->cf['menu']['title'] ) ) {
				$this->label_transl = sprintf( __( '%s Notice', $this->text_domain ),
					_x( $this->p->cf['menu']['title'], 'menu title', $this->text_domain ) );
			} else {
				$this->label_transl = __( 'Notice', $this->text_domain );
			}

			$uca = strtoupper( $this->lca );

			$this->opt_name  = defined( $uca . '_NOTICE_NAME' ) ? constant( $uca . '_NOTICE_NAME' ) : $this->lca . '_notices';
			$this->dis_name  = defined( $uca . '_DISMISS_NAME' ) ? constant( $uca . '_DISMISS_NAME' ) : $this->lca . '_dismissed';
			$this->hide_err  = defined( $uca . '_HIDE_ALL_ERRORS' ) ? constant( $uca . '_HIDE_ALL_ERRORS' ) : false;
			$this->hide_warn = defined( $uca . '_HIDE_ALL_WARNINGS' ) ? constant( $uca . '_HIDE_ALL_WARNINGS' ) : false;
		}

		protected function add_actions() {
			if ( is_admin() ) {
				add_action( 'in_admin_header', array( &$this, 'hook_admin_notices' ), PHP_INT_MAX );
				add_action( 'admin_footer', array( &$this, 'admin_footer_script' ) );
				add_action( 'wp_ajax_' . $this->lca . '_dismiss_notice', array( &$this, 'ajax_dismiss_notice' ) );
			}
			add_action( 'shutdown', array( &$this, 'save_user_notices' ), PHP_INT_MAX );
		}

		public function nag( $msg_txt, $user_id = true, $dismiss_key = false ) {
			$this->log( 'nag', $msg_txt, $user_id, $dismiss_key, false );	// $dismiss_time = false
		}

		public function err( $msg_txt, $user_id = true, $dismiss_key = false, $dismiss_time = false ) {
			$this->log( 'err', $msg_txt, $user_id, $dismiss_key, $dismiss_time );
		}

		public function warn( $msg_txt, $user_id = true, $dismiss_key = false, $dismiss_time = false, $silent = false ) {
			$payload = array( 'silent' => $silent ? true : false );
			$this->log( 'warn', $msg_txt, $user_id, $dismiss_key, $dismiss_time, $payload );
		}

		public function upd( $msg_txt, $user_id = true, $dismiss_key = false, $dismiss_time = false ) {
			$this->log( 'upd', $msg_txt, $user_id, $dismiss_key, $dismiss_time );
		}

		public function inf( $msg_txt, $user_id = true, $dismiss_key = false, $dismiss_time = false ) {
			$this->log( 'inf', $msg_txt, $user_id, $dismiss_key, $dismiss_time );
		}

		public function log( $msg_type, $msg_txt, $user_id = true, $dismiss_key = false, $dismiss_time = false, $payload = array() ) {

			if ( empty( $msg_type ) || empty( $msg_txt ) ) {
				return;
			}

			/**
			 * 'dis_key' is independant of the $msg_type, so all can be hidden with one 'dis_key'.
			 */
			$payload['dis_key'] = empty( $dismiss_key ) ? false : $dismiss_key;

			/**
			 * 'dis_key' and 'dis_time' (true or seconds) are both required to dismiss a notice.
			 */
			if ( ! empty( $dismiss_key ) && ! empty( $dismiss_time ) && $this->can_dismiss() ) {

				$payload['dis_time'] = $dismiss_time;

				if ( is_numeric( $payload['dis_time'] ) ) {

					/**
					 * If the message ends with a paragraph tag, add text as a paragraph
					 */
					$is_p    = substr( $msg_txt, -4 ) === '</p>' ? true : false;
					$msg_txt .= $is_p ? '<p>' : ' ';
					$msg_txt .= sprintf( __( 'This notice can be dismissed temporarily for a period of %s.', $this->text_domain ), 
						human_time_diff( 0, $payload['dis_time'] ) );
					$msg_txt .= $is_p ? '</p>' : '';
				}
			} else {
				$payload['dis_time'] = false;
			}

			$msg_txt .= $this->get_ref_url_html();

			if ( true === $user_id ) {
				$user_id = (int) get_current_user_id();
			} else {
				$user_id = (int) $user_id;	// false = 0
			}

			/**
			 * Returns a reference to the cache array.
			 */
			$user_notices =& $this->get_user_notices( $user_id );

			/**
			 * User notices are saved on shutdown.
			 */
			if ( ! isset( $user_notices[$msg_type][$msg_txt] ) ) {
				$user_notices[$msg_type][$msg_txt] = $payload;
			}
		}

		public function trunc_key( $dismiss_key, $user_id = true ) {
			$this->trunc( '', '', $dismiss_key, $user_id );
		}

		public function trunc_all() {
			$this->trunc( '', '', false, 'all' );
		}

		public function trunc( $msg_type = '', $msg_txt = '', $dismiss_key = false, $user_id = true ) {

			if ( true === $user_id ) {
				$user_ids = array( get_current_user_id() );
			} elseif ( $user_id === 'all' ) {
				$user_ids = $this->get_all_user_ids();
			} elseif ( is_array( $user_id ) ) {
				$user_ids = $user_id;
			} else {
				$user_ids = array( $user_id );
			}

			$trunc_types = empty( $msg_type ) ?
				$this->all_types : array( (string) $msg_type );

			foreach ( $user_ids as $user_id ) {

				/**
				 * Returns a reference to the cache array.
				 */
				$user_notices =& $this->get_user_notices( $user_id );

				foreach ( $trunc_types as $msg_type ) {
					if ( isset( $user_notices[$msg_type] ) ) {

						// clear notice for a specific dis_key
						if ( ! empty( $dismiss_key ) && is_array( $user_notices[$msg_type] ) ) {

							// use a reference for the payload
							foreach ( $user_notices[$msg_type] as $msg_txt => &$payload ) {
								if ( ! empty( $payload['dis_key'] ) && $payload['dis_key'] === $dismiss_key ) {
									unset( $payload );	// unset by reference
								}
							}

						// clear all notices for that type
						} elseif ( empty( $msg_txt ) ) {
							$user_notices[$msg_type] = array();

						// clear a specific message string
						} elseif ( isset( $user_notices[$msg_type][$msg_txt] ) ) {
							unset( $user_notices[$msg_type][$msg_txt] );
						}
					}
				}
			}
		}

		/**
		 * Set reference values for admin notices
		 */
		public function set_ref( $url = null, $mod = null, $context_transl = null ) {
			$this->ref_cache[] = array( 'url' => $url, 'mod' => $mod, 'context_transl' => $context_transl );
		}

		/**
		 * Restore previous reference values for admin notices.
		 */
		public function unset_ref( $url = null ) {
			if ( null === $url || $this->is_ref_url( $url ) ) {
				array_pop( $this->ref_cache );
				return true;
			} else {
				return false;
			}
		}

		public function get_ref( $idx = false, $prefix = '', $suffix = '' ) {
			$refs = end( $this->ref_cache );	// get the last reference added
			if ( $idx === 'edit' ) {
				if ( isset( $refs['mod'] ) ) {
					if ( $refs['mod']['is_post'] && $refs['mod']['id'] ) {
						return $prefix.get_edit_post_link( $refs['mod']['id'], false ) . $suffix;	// $display = false
					} else {
						return '';
					}
				} else {
					return '';
				}
			} elseif ( $idx !== false ) {
				if ( isset( $refs[$idx] ) ) {
					return $prefix . $refs[$idx] . $suffix;
				} else {
					null;
				}
			} else {
				return $refs;
			}
		}

		public function get_ref_url_html() {
			$ref_html = '';
			if ( $url = $this->get_ref( 'url' ) ) {
				$context_transl = $this->get_ref( 'context_transl', '', ' ' );
				$url_link = '<a href="' . $url . '">' . strtolower( $url ) . '</a>';
				$edit_link = $this->get_ref( 'edit', ' (<a href="', '">' . __( 'edit', $this->text_domain ) . '</a>)' );
				$ref_html .= '<p class="reference-message">' . sprintf( __( 'Reference: %s', $this->text_domain ),
					$context_transl . $url_link . $edit_link ) . '</p>';
			}
			return $ref_html;
		}

		public function is_ref_url( $url = null ) {
			if ( null === $url || $url === $this->get_ref( 'url' ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function is_admin_pre_notices( $dismiss_key = false, $user_id = true ) {
			if ( is_admin() ) {
				if ( ! empty( $dismiss_key ) ) {
					// if notice is dismissed, say we've already shown the notices
					if ( $this->is_dismissed( $dismiss_key, $user_id ) ) {
						if ( ! empty( $this->p->debug->enabled ) ) {
							$this->p->debug->log( 'returning false: '.$dismiss_key.' is dismissed' );
						}
						return false;
					}
				}
				if ( $this->has_shown ) {
					if ( ! empty( $this->p->debug->enabled ) ) {
						$this->p->debug->log( 'returning false: notices have been shown' );
					}
					return false;
				}
			} else {
				if ( ! empty( $this->p->debug->enabled ) ) {
					$this->p->debug->log( 'returning false: is not admin' );
				}
				return false;
			}
			return true;
		}

		public function is_dismissed( $dismiss_key = false, $user_id = true ) {

			if ( empty( $dismiss_key ) || ! $this->can_dismiss() ) {	// Just in case.
				return false;
			}

			if ( true === $user_id ) {
				$user_id = (int) get_current_user_id();
			} else {
				$user_id = (int) $user_id;	// false = 0
			}

			if ( empty( $user_id ) ) {
				return false;
			}

			$user_dismissed = get_user_option( $this->dis_name, $user_id );

			if ( ! is_array( $user_dismissed ) ) {
				return false;
			}

			// notice has been dismissed
			if ( isset( $user_dismissed[$dismiss_key] ) ) {

				$now_time = time();
				$dismiss_time = $user_dismissed[$dismiss_key];

				if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {
					return true;

				// dismiss time has expired
				} else {
					unset( $user_dismissed[$dismiss_key] );
					if ( empty( $user_dismissed ) ) {
						delete_user_option( $user_id, $this->dis_name );
					} else {
						update_user_option( $user_id, $this->dis_name, $user_dismissed );
					}
				}
			}

			return false;
		}

		public function can_dismiss() {
			global $wp_version;
			if ( version_compare( $wp_version, 4.2, '>=' ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function reload_user_notices( $user_id = true ) {

			/**
			 * Returns a reference to the cache array.
			 */
			$user_notices =& $this->get_user_notices( $user_id, false );	// $use_cache = false
		}

		public function hook_admin_notices() {
			add_action( 'all_admin_notices', array( &$this, 'show_admin_notices' ), -10 );
		}

		public function show_admin_notices() {

			$hidden = array();
			$msg_html = '';
			$nag_msgs = '';
			$seen_msgs = array();	// duplicate check
			$dismissed_updated = false;
			$user_id = (int) get_current_user_id();
			$user_notices =& $this->get_user_notices( $user_id );
			$user_dismissed = empty( $user_id ) ? false : get_user_option( $this->dis_name, $user_id );	// get dismissed message ids
			$this->has_shown = true;

			if ( isset( $this->p->cf['plugin'] ) && class_exists( 'SucomUpdate' ) ) {
				foreach ( array_keys( $this->p->cf['plugin'] ) as $ext ) {
					if ( ! empty( $this->p->options['plugin_' . $ext . '_tid'] ) ) {
						$uerr = SucomUpdate::get_umsg( $ext );
						if ( ! empty( $uerr ) ) {
							$user_notices['err'][$uerr] = array();
						}
					}
				}
			}

			// loop through all the msg types and show them all
			foreach ( $this->all_types as $msg_type ) {

				if ( ! isset( $user_notices[$msg_type] ) ) {	// Just in case.
					continue;
				}

				foreach ( $user_notices[$msg_type] as $msg_txt => $payload ) {

					if ( empty( $msg_txt ) || isset( $seen_msgs[$msg_txt] ) ) {	// skip duplicates
						continue;
					}

					$seen_msgs[$msg_txt] = true;	// avoid duplicates

					switch ( $msg_type ) {
						case 'nag':
							$nag_msgs .= $msg_txt;	// append to echo a single msg block
							continue;
						default:
							// dis_time will be false if there's no dis_key
							if ( ! empty( $payload['dis_time'] ) ) {

								// initialize the count
								if ( ! isset( $hidden[$msg_type] ) ) {
									$hidden[$msg_type] = 0;
								}

								// check for automatically hidden errors and/or warnings
								if ( ( $msg_type === 'err' && $this->hide_err ) ||
									( $msg_type === 'warn' && $this->hide_warn ) ) {

									$payload['hidden'] = true;
									if ( empty( $payload['silent'] ) ) {
										$hidden[$msg_type]++;
									}

								// dis_key has been dismissed
								} elseif ( ! empty( $payload['dis_key'] ) &&
									isset( $user_dismissed[$payload['dis_key']] ) ) {

									$now_time = time();
									$dismiss_time = $user_dismissed[$payload['dis_key']];

									if ( empty( $dismiss_time ) || $dismiss_time > $now_time ) {
										$payload['hidden'] = true;
										if ( empty( $payload['silent'] ) ) {
											$hidden[$msg_type]++;
										}
									} else {	// dismiss has expired
										$dismissed_updated = true;	// update the array when done
										unset( $user_dismissed[$payload['dis_key']] );
									}
								}
							}
							$msg_html .= $this->get_notice_html( $msg_type, $msg_txt, $payload );
							break;
					}
				}
			}

			/**
			 * Delete all notices for the current user id.
			 */
			$this->trunc();

			/**
			 * Don't save unless we've changed something.
			 */
			if ( true === $dismissed_updated && ! empty( $user_id ) ) {
				if ( empty( $user_dismissed ) ) {
					delete_user_option( $user_id, $this->dis_name );
				} else {
					update_user_option( $user_id, $this->dis_name, $user_dismissed );
				}
			}

			echo "\n";
			echo '<!-- ' . $this->lca . ' admin notices begin -->' . "\n";
			echo '<div id="' . sanitize_html_class( $this->lca . '-admin-notices-begin' ) . '"></div>' . "\n";
			echo $this->get_notice_style();

			if ( ! empty( $nag_msgs ) ) {
				echo $this->get_nag_style();
				echo $this->get_notice_html( 'nag', $nag_msgs );
			}

			/**
			 * Remind the user that there are hidden error messages.
			 */
			foreach ( array(
				'err' => _x( 'error', 'notification type', $this->text_domain ),
				'warn' => _x( 'warning', 'notification type', $this->text_domain ),
			) as $msg_type => $log_name ) {
				if ( empty( $hidden[$msg_type] ) ) {
					continue;
				}
				
				$msg_text = _n(
					'%1$d important %2$s notice has been hidden and/or dismissed &mdash; <a id="%3$s">unhide and view the %2$s message</a>.',
					'%1$d important %2$s notices have been hidden and/or dismissed &mdash; <a id="%3$s">unhide and view the %2$s messages</a>.',
					$hidden[$msg_type], $this->text_domain
				);

				$msg_text = sprintf( $msg_text, $hidden[$msg_type], $log_name, $this->lca . '-unhide-notice-' . $msg_type );

				echo $this->get_notice_html( $msg_type, $msg_text );
			}

			echo $msg_html;
			echo '<!-- ' . $this->lca . ' admin notices end -->' . "\n";
		}

		/**
		 * Called by the WordPress 'shutdown' action.
		 */
		public function save_user_notices() {

			$user_id = (int) get_current_user_id();

			$have_notices = false;

			if ( $user_id > 0 ) {
				if ( isset( $this->notice_cache[$user_id]['have_notices'] ) ) {
					$have_notices = $this->notice_cache[$user_id]['have_notices'];
					unset( $this->notice_cache[$user_id]['have_notices'] );
				}
				if ( empty( $this->notice_cache[$user_id] ) ) {
					if ( $have_notices ) {
						delete_user_option( $user_id, $this->opt_name );
					}
				} else {
					update_user_option( $user_id, $this->opt_name, $this->notice_cache[$user_id] );
				}
			}
		}

		protected function get_notice_html( $msg_type, $msg_txt, $payload = array() ) {

			$charset = get_bloginfo( 'charset' );

			if ( ! isset( $payload['label'] ) ) {
				$payload['label'] = $this->label_transl;
			}

			switch ( $msg_type ) {
				case 'nag':
					$payload['label'] = '';
					$msg_class = 'update-nag';
					break;
				case 'warn':
					$msg_class = 'notice notice-warning';
					break;
				case 'err':
					$msg_class = 'notice notice-error error';
					break;
				case 'upd':
					$msg_class = 'notice notice-success updated';
					break;
				case 'inf':
				default:
					$msg_type = 'inf';	// Just in case.
					$msg_class = 'notice notice-info';
					break;
			}

			// dis_key and dis_time must have values to create a dismissible notice
			$is_dismissible = empty( $payload['dis_key'] ) || empty( $payload['dis_time'] ) ? false : true;

			$css_id_attr = empty( $payload['dis_key'] ) ? '' : ' id="' . $msg_type . '_' . $payload['dis_key'] . '"';

			$data_attr = $is_dismissible ?
				' data-dismiss-nonce="' . wp_create_nonce( __FILE__ ) . '"' . 
				' data-dismiss-key="' . esc_attr( $payload['dis_key'] ) . '"' . 
				' data-dismiss-time="' . ( is_numeric( $payload['dis_time'] ) ? esc_attr( $payload['dis_time'] ) : 0 ) . '"' : '';

			// optionally hide notices if required
			$style_attr = ' style="' . 
				( empty( $payload['style'] ) ? '' : $payload['style'] ).
				( empty( $payload['hidden'] ) ? 'display:block !important; visibility:visible !important;' : 'display:none;' ) . '"';

			$msg_html = '<div class="' . $this->lca . '-notice ' . 
				( ! $is_dismissible ? '' : $this->lca . '-dismissible ' ).
					$msg_class . '"' . $css_id_attr . $style_attr . $data_attr . '>';	// display block or none

			if ( ! empty( $payload['dis_time'] ) ) {
				$msg_html .= '<div class="notice-dismiss"><div class="notice-dismiss-text">' . 
					__( 'Dismiss', $this->text_domain ) . '</div></div>';
			}

			if ( ! empty( $payload['label'] ) ) {
				$msg_html .= '<div class="notice-label">' . $payload['label'] . '</div>';
			}

			$msg_html .= '<div class="notice-message">' . $msg_txt . '</div>';
			$msg_html .= '</div>' . "\n";

			return $msg_html;
		}

		protected function get_notice_style() {

			$custom_style_css = '
				.' . $this->lca . '-notice.notice {
					padding:0;
				}
				.' . $this->lca . '-notice ul {
					margin:5px 0 5px 40px;
					list-style:disc outside none;
				}
				.' . $this->lca . '-notice.notice-success .notice-label::before,
				.' . $this->lca . '-notice.notice-info .notice-label::before,
				.' . $this->lca . '-notice.notice-warning .notice-label::before,
				.' . $this->lca . '-notice.notice-error .notice-label::before {
					vertical-align:bottom;
					font-family:dashicons;
					font-size:1.2em;
					margin-right:6px;
				}
				.' . $this->lca . '-notice.notice-success .notice-label::before {
					content:"\f147";	/* yes */
				}
				.' . $this->lca . '-notice.notice-info .notice-label::before {
					content:"\f537";	/* sticky */
				}
				.' . $this->lca . '-notice.notice-warning .notice-label::before {
					content:"\f227";	/* flag */
				}
				.' . $this->lca . '-notice.notice-error .notice-label::before {
					content:"\f488";	/* megaphone */
				}
				.' . $this->lca . '-notice .notice-label {
					display:table-cell;
					vertical-align:top;
					padding:10px;
					margin:0;
					white-space:nowrap;
					font-weight:bold;
					background:#fcfcfc;
					border-right:1px solid #ddd;
				}
				.' . $this->lca . '-notice .notice-message {
					display:table-cell;
					vertical-align:top;
					padding:10px 20px;
					margin:0;
					line-height:1.5em;
				}
				.' . $this->lca . '-notice .notice-message h2 {
					font-size:1.2em;
				}
				.' . $this->lca . '-notice .notice-message h3 {
					font-size:1.1em;
					margin-top:1.2em;
					margin-bottom:0.8em;
				}
				.' . $this->lca . '-notice .notice-message code {
					font-family:"Courier", monospace;
					font-size:0.95em;
					padding:0 2px;
					margin:0;
				}
				.' . $this->lca . '-notice .notice-message a {
					text-decoration:underline;
				}
				.' . $this->lca . '-notice .notice-message a code {
					vertical-align:middle;
					padding:0;
				}
				.' . $this->lca . '-notice .notice-message p {
					margin:1em 0;
				}
				.' . $this->lca . '-notice .notice-message p.reference-message {
					font-size:0.8em;
					margin:10px 0 0 0;
				}
				.' . $this->lca . '-notice .notice-message ul {
					margin-top:0.8em;
					margin-bottom:1.2em;
				}
				.' . $this->lca . '-notice .notice-message ul li {
					margin-top:3px;
					margin-bottom:3px;
				}
				.' . $this->lca . '-notice .notice-message .button-highlight {
					border-color:#0074a2;
					background-color:#daeefc;
				}
				.' . $this->lca . '-notice .notice-message .button-highlight:hover {
					background-color:#c8e6fb;
				}
				.' . $this->lca . '-dismissible div.notice-dismiss::before {
					display:inline-block;
					margin-right:2px;
				}
				.' . $this->lca . '-dismissible div.notice-dismiss {
					float:right;
					position:relative;
					padding:10px;
					margin:0;
					top:0;
					right:0;
				}
				.' . $this->lca . '-dismissible div.notice-dismiss-text {
					display:inline-block;
					font-size:12px;
					vertical-align:top;
				}
			';

			if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->lca );
			}

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}

		protected function get_nag_style() {

			$custom_style_css = '';
			$uca = strtoupper( $this->lca );

			if ( isset( $this->p->cf['nag_colors'] ) ) {
				foreach ( $this->p->cf['nag_colors'] as $css_class => $nag_colors ) {
					foreach ( $nag_colors as $prop_name => $prop_value ) {
						$custom_style_css .= '.' . $this->lca . '-notice.' . $css_class . '{' . $prop_name . ':' . $prop_value . ';}' . "\n";
					}
				}
			}

			$custom_style_css .= '
				.' . $this->lca . '-notice.update-nag {
					margin-top:0;
				}
				.' . $this->lca . '-notice.update-nag > div {
					display:block;
					margin:0 auto;
					max-width:800px;
				}
				.' . $this->lca . '-notice.update-nag p,
				.' . $this->lca . '-notice.update-nag ul,
				.' . $this->lca . '-notice.update-nag ol {
					font-size:1em;
					text-align:center;
					margin:15px auto 15px auto;
				}
				.' . $this->lca . '-notice.update-nag ul li {
					list-style-type:square;
				}
				.' . $this->lca . '-notice.update-nag ol li {
					list-style-type:decimal;
				}
				.' . $this->lca . '-notice.update-nag li {
					text-align:left;
					margin:5px 0 5px 60px;
				}
			';
			
			if ( method_exists( 'SucomUtil', 'minify_css' ) ) {
				$custom_style_css = SucomUtil::minify_css( $custom_style_css, $this->lca );
			}

			return '<style type="text/css">' . $custom_style_css . '</style>';
		}

		protected function get_all_user_ids() {

			$user_ids = array();

			foreach ( get_users() as $user ) {
				if ( ! empty( $user->ID ) ) {
					$user_ids[] = $user->ID;
				}
			}

			return $user_ids;
		}

		/**
		 * Returns a reference to the cache array.
		 */
		protected function &get_user_notices( $user_id = true, $use_cache = true ) {

			if ( true === $user_id ) {
				$user_id = (int) get_current_user_id();
			} else {
				$user_id = (int) $user_id;	// false = 0
			}

			if ( $use_cache && isset( $this->notice_cache[$user_id] ) ) {
				return $this->notice_cache[$user_id];
			}

			if ( $user_id > 0 ) {
				$this->notice_cache[$user_id] = get_user_option( $this->opt_name, $user_id );
				if ( is_array( $this->notice_cache[$user_id] ) ) {
					$this->notice_cache[$user_id]['have_notices'] = true;
				} else {
					$this->notice_cache[$user_id] = array( 'have_notices' => false );
				}
			}

			foreach ( $this->all_types as $msg_type ) {
				if ( ! isset( $this->notice_cache[$user_id][$msg_type] ) ) {
					$this->notice_cache[$user_id][$msg_type] = array();
				}
			}

			return $this->notice_cache[$user_id];
		}

		public function admin_footer_script() {
			echo '
<script type="text/javascript">
	jQuery( document ).ready( function() {

		jQuery("#' . $this->lca . '-unhide-notice-err").click( function() {
			var notice = jQuery( this ).parents(".' . $this->lca . '-notice.notice-error");
			jQuery(".' . $this->lca . '-notice.' . $this->lca . '-dismissible.notice-error").show();
			notice.hide();
		});

		jQuery("#' . $this->lca . '-unhide-notice-warn").click( function() {
			var notice = jQuery( this ).parents(".' . $this->lca . '-notice.notice-warning");
			jQuery(".' . $this->lca . '-notice.' . $this->lca . '-dismissible.notice-warning").show();
			notice.hide();
		});

		jQuery("div.' . $this->lca . '-dismissible > div.notice-dismiss, div.' . $this->lca . '-dismissible .dismiss-on-click").click( function() {

			var notice        = jQuery( this ).closest(".' . $this->lca . '-dismissible");
			var dismiss_msg   = jQuery( this ).data( "dismiss-msg" );
			var dismiss_nonce = notice.data( "dismiss-nonce" );
			var dismiss_key   = notice.data( "dismiss-key" );
			var dismiss_time  = notice.data( "dismiss-time" );

			jQuery.post(
				ajaxurl, {
					action: "' . $this->lca . '_dismiss_notice",
					_ajax_nonce: dismiss_nonce,
					key: dismiss_key,
					time: dismiss_time
				}
			);

			if ( dismiss_msg ) {
				notice.children("div.notice-dismiss").hide();
				jQuery( this ).closest("div.notice-message").html( dismiss_msg );
			} else {
				notice.hide();
			}
		});
	});
</script>
			';
		}

		public function ajax_dismiss_notice() {

			$dis_info = array();
			$user_id  = get_current_user_id();

			if ( empty( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
				die( '-1' );
			}

			check_ajax_referer( __FILE__, '_ajax_nonce', true );

			/**
			 * Read arguments.
			 */
			foreach ( array( 'key', 'time' ) as $key ) {
				$dis_info[$key] = sanitize_text_field( filter_input( INPUT_POST, $key ) );
			}

			if ( empty( $dis_info['key'] ) ) {
				die( '-1' );
			}

			/**
			 * Site specific user options.
			 */
			$user_dismissed = get_user_option( $this->dis_name, $user_id );

			if ( ! is_array( $user_dismissed ) ) {
				$user_dismissed = array();
			}

			/**
			 * Save the message id and expiration time (0 = never).
			 */
			$user_dismissed[$dis_info['key']] = empty( $dis_info['time'] ) || ! is_numeric( $dis_info['time'] ) ? 0 : time() + $dis_info['time'];

			update_user_option( $user_id, $this->dis_name, $user_dismissed );

			die( '1' );
		}
	}
}
