<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoOpenGraphNS' ) ) {

	class WpssoOpenGraphNS {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Hook the first available filter name (example: 'language_attributes').
			 */
			foreach ( array( 'plugin_html_attr_filter', 'plugin_head_attr_filter' ) as $opt_pre ) {

				if ( ! empty( $this->p->options[ $opt_pre . '_name' ] ) && $this->p->options[ $opt_pre . '_name' ] !== 'none' ) {

					$wp_filter_name = $this->p->options[ $opt_pre . '_name' ];

					$wp_filter_prio = isset( $this->p->options[ $opt_pre . '_prio' ] ) ? (int) $this->p->options[ $opt_pre . '_prio' ] : 100;

					add_filter( $wp_filter_name, array( $this, 'add_og_ns_attributes' ), $wp_filter_prio, 1 );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'added add_og_ns_attributes filter for ' . $wp_filter_name );
					}

					break;	// Stop here.

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'skipping add_og_ns_attributes for ' . $opt_pre . ' - filter name is empty or disabled' );
				}
			}

			$this->p->util->add_plugin_filters( $this, array( 
				'og_data_https_ogp_me_ns_article' => 2,
				'og_data_https_ogp_me_ns_book'    => 2,
				'og_data_https_ogp_me_ns_product' => 2,
			) );
		}

		public function add_og_ns_attributes( $html_attr ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array (
					'html_attr' => $html_attr,
				) );
			}

			$use_post = apply_filters( $this->p->lca . '_use_post', false );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'required call to get_page_mod()' );
			}

			$mod = $this->p->util->get_page_mod( $use_post );

			$type_id = $this->p->og->get_mod_og_type( $mod );

			$og_ns = array(
				'og' => 'https://ogp.me/ns#',
				'fb' => 'https://ogp.me/ns/fb#',
			);

			/**
			 * Check that the og_type is known and add it's namespace value.
			 *
			 * Example: article, place, product, website, etc.
			 */
			if ( ! empty( $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ] ) ) {

				$og_ns[ $type_id ] = $this->p->cf[ 'head' ][ 'og_type_ns' ][ $type_id ];
			}

			$og_ns = apply_filters( $this->p->lca . '_og_ns', $og_ns, $mod );

			if ( SucomUtil::is_amp() ) {	// Returns null, true, or false.

				/**
				 * Nothing to do.
				 */

			} else {

				$html_attr = ' ' . $html_attr;	// Prepare the string for testing.

				/**
				 * Find and remove an existing prefix attribute value.
				 */
				if ( strpos( $html_attr, 'prefix=' ) ) {

					/**
				 	 * s = A dot metacharacter in the pattern matches all characters, including newlines.
					 *
					 * See https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php
					 */
					if ( preg_match( '/^(.*)\sprefix=["\']([^"\']*)["\'](.*)$/s', $html_attr, $match ) ) {

						$html_attr    = $match[1] . $match[3];	// Remove the prefix.
					}
				}

				$attr_val = '';

				foreach ( $og_ns as $name => $url ) {

					if ( false === strpos( $attr_val, ' ' . $name . ': ' . $url ) ) {

						$attr_val .= ' ' . $name . ': ' . $url;
					}
				}

				$html_attr .= ' prefix="' . trim( $attr_val ) . '"';
			}

			return trim( $html_attr );
		}

		/**
		 * The output from this method is provided to the WPSSO JSON add-on, so be careful when removing array elements. If
		 * you need to remove array elements after the Schema JSON-LD markup has been created, but before the meta tags
		 * have been generated, use the WpssoOpenGraph->sanitize_mt_array() method.
		 */
		public function filter_og_data_https_ogp_me_ns_article( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $mt_og;
		}

		/**
		 * The output from this method is provided to the WPSSO JSON add-on, so be careful when removing array elements. If
		 * you need to remove array elements after the Schema JSON-LD markup has been created, but before the meta tags
		 * have been generated, use the WpssoOpenGraph->sanitize_mt_array() method.
		 */
		public function filter_og_data_https_ogp_me_ns_book( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! isset( $mt_og[ 'book:author' ] ) ) {
			}

			return $mt_og;
		}

		/**
		 * The output from this method is provided to the WPSSO JSON add-on, so be careful when removing array elements. If
		 * you need to remove array elements after the Schema JSON-LD markup has been created, but before the meta tags
		 * have been generated, use the WpssoOpenGraph->sanitize_mt_array() method.
		 */
		public function filter_og_data_https_ogp_me_ns_product( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			WpssoOpenGraph::check_mt_value_gtin( $mt_og, $mt_pre = 'product' );

			WpssoOpenGraph::check_mt_value_price( $mt_og, $mt_pre = 'product' );

			/**
			 * Include variations (aka product offers) if available.
			 */
			if ( ! empty( $mt_og[ 'product:offers' ] ) && is_array( $mt_og[ 'product:offers' ] ) ) {

				foreach ( $mt_og[ 'product:offers' ] as $num => &$offer ) {	// Allow changes to the offer array.

					WpssoOpenGraph::check_mt_value_gtin( $offer, $mt_pre = 'product' );

					WpssoOpenGraph::check_mt_value_price( $offer, $mt_pre = 'product' );

					/**
					 * Allow only a single main product brand.
					 */
					if ( ! empty( $offer[ 'product:brand' ] ) ) {

						if ( empty( $mt_og[ 'product:brand' ] ) ) {

							$mt_og[ 'product:brand' ] = $mt_value;
						}

						unset ( $offer[ 'product:brand' ] );
					}
				}
			}

			return $mt_og;
		}
	}
}
