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

/**
 * WordPress metadata class, extended by the WpssoPost, WpssoTerm, and WpssoUser classes.
 */
if ( ! class_exists( 'WpssoWpMeta' ) ) {

	abstract class WpssoWpMeta {

		protected $p;		// Wpsso class object.
		protected $form;	// SucomForm class object.

		protected $md_cache_disabled = false;	// Disable local caches when saving options.

		/**
		 * The WpssoPost, WpssoTerm, and WpssoUser->load_meta_page() methods define the $head_tags and $head_info static
		 * variables.
		 */
		protected static $head_tags = false;	// Must be false by default.
		protected static $head_info = array();

		protected static $rename_md_options_keys = array(
			'wpsso' => array(
				499 => array(
					'link_desc' => 'seo_desc',
					'meta_desc' => 'seo_desc',
				),
				503 => array(
					'schema_recipe_calories' => 'schema_recipe_nutri_cal',
				),
				514 => array(
					'rp_img_id'     => 'p_img_id',
					'rp_img_id_pre' => 'p_img_id_pre',
					'rp_img_width'  => '',
					'rp_img_height' => '',
					'rp_img_crop'   => '',
					'rp_img_crop_x' => '',
					'rp_img_crop_y' => '',
					'rp_img_url'    => 'p_img_url',
				),
				537 => array(
					'schema_add_type_url' => 'schema_addl_type_url_0',
				),
				569 => array(
					'schema_add_type_url' => 'schema_addl_type_url',	// Option modifiers are preserved.
				),
				615 => array(
					'org_type' => 'org_schema_type',
				),
				628 => array(
					'product_gender' => 'product_target_gender',
				),
				649 => array(
					'product_ean' => 'product_gtin13',
				),

				/**
				 * The custom width, height, and crop options were removed in preference for attachment specific
				 * options (ie. 'attach_img_crop_x' and 'attach_img_crop_y').
				 */
				660 => array(
					'og_img_width'              => '',
					'og_img_height'             => '',
					'og_img_crop'               => '',
					'og_img_crop_x'             => '',
					'og_img_crop_y'             => '',
					'schema_article_img_width'  => '',
					'schema_article_img_height' => '',
					'schema_article_img_crop'   => '',
					'schema_article_img_crop_x' => '',
					'schema_article_img_crop_y' => '',
					'schema_img_width'          => '',
					'schema_img_height'         => '',
					'schema_img_crop'           => '',
					'schema_img_crop_x'         => '',
					'schema_img_crop_y'         => '',
					'tc_sum_img_width'          => '',
					'tc_sum_img_height'         => '',
					'tc_sum_img_crop'           => '',
					'tc_sum_img_crop_x'         => '',
					'tc_sum_img_crop_y'         => '',
					'tc_lrg_img_width'          => '',
					'tc_lrg_img_height'         => '',
					'tc_lrg_img_crop'           => '',
					'tc_lrg_img_crop_x'         => '',
					'tc_lrg_img_crop_y'         => '',
					'thumb_img_width'           => '',
					'thumb_img_height'          => '',
					'thumb_img_crop'            => '',
					'thumb_img_crop_x'          => '',
					'thumb_img_crop_y'          => '',
				),
				692 => array(
					'product_mpn' => 'product_mfr_part_no',
					'product_sku' => 'product_retailer_part_no',
				),
				696 => array(
					'og_art_section' => 'article_section',
				),
				701 => array(
					'article_topic' => 'article_section',
				),
				725 => array(
					'product_volume_value' => 'product_fluid_volume_value',
				),
			),
		);

		public static $mod_defaults = array(

			/**
			 * Common elements.
			 */
			'id'          => 0,		// Post, term, or user ID.
			'name'        => false,		// Module name ('post', 'term', or 'user').
			'name_transl' => false,		// Module name translated.
			'obj'         => false,		// Module object.
			'is_public'   => true,		// Module object is public.

			/**
			 * Post elements.
			 */
			'use_post'             => false,
			'is_post'              => false,	// Is post module.
			'is_post_type_archive' => false,	// Post is an archive.
			'is_home'              => false,	// Home page (index or static)
			'is_home_page'         => false,	// Static front page.
			'is_home_posts'        => false,	// Static posts page or latest posts.
			'post_slug'            => false,	// Post name (aka slug).
			'post_type'            => false,	// Post type name.
			'post_type_label'      => false,	// Post type singular name.
			'post_mime'            => false,	// Post mime type (ie. image/jpg).
			'post_status'          => false,	// Post status name.
			'post_author'          => false,	// Post author id.
			'post_coauthors'       => array(),

			/**
			 * Term elements.
			 */
			'is_term'   => false,	// Is term module.
			'tax_slug'  => '',	// Empty string by default.
			'tax_label' => false,	// Taxonomy singular name.

			/**
			 * User elements.
			 */
			'is_user' => false,	// Is user module.
		);

		public function __construct( &$plugin ) {

			return self::must_be_extended();
		}

		/**
		 * Add WordPress action and filters hooks.
		 */
		public function add_wp_hooks() {

			return self::must_be_extended();
		}

		/**
		 * Get the $mod object for a post, term, or user ID.
		 */
		public function get_mod( $mod_id ) {

			return self::must_be_extended( self::$mod_defaults );
		}

		/**
		 * Get the $mod object for the home page.
		 */
		public static function get_mod_home() {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$mod = self::$mod_defaults;

			$post_id = 0;

			if ( 'page' === get_option( 'show_on_front' ) ) {

				if ( ! $post_id = (int) get_option( 'page_on_front', $default = 0 ) ) {

					$post_id = (int) get_option( 'page_for_posts', $default = 0 );
				}

				$mod = $wpsso->post->get_mod( $post_id );
			}

			/**
			 * Post elements.
			 */
			$mod[ 'is_home' ] = true;

			return $mod;
		}

		/**
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_defaults( $mod_id, $md_key = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'mod_id' => $mod_id,
					'md_key' => $md_key,
				) );
			}

			static $local_cache = array();

			if ( ! isset( $local_cache[ $mod_id ] ) ) {

				$local_cache[ $mod_id ] = array();

			} elseif ( $this->md_cache_disabled ) {

				$local_cache[ $mod_id ] = array();
			}

			$md_defs =& $local_cache[ $mod_id ];	// Shortcut variable name.

			if ( ! WpssoOptions::can_cache() || empty( $md_defs[ 'options_filtered' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'get_md_defaults filter allowed' );
				}

				$mod = $this->get_mod( $mod_id );

				$opts =& $this->p->options;		// Shortcut variable name.

				$def_og_type = $this->p->og->get_mod_og_type( $mod, $get_ns = false, $use_mod_opts = false );

				$def_schema_type = $this->p->schema->get_mod_schema_type( $mod, $get_id = true, $use_mod_opts = false );

				$md_defs = array(
					'options_filtered'  => 0,
					'options_version'   => '',
					'attach_img_crop_x' => 'none',
					'attach_img_crop_y' => 'none',

					/**
					 * Customize Tab.
					 */
					'og_type'          => $def_og_type,
					'og_title'         => '',
					'og_desc'          => '',
					'seo_desc'         => '',
					'tc_desc'          => '',
					'sharing_url'      => '',
					'canonical_url'    => '',
					'article_section'  => isset( $opts[ 'og_def_article_section' ] ) ? $opts[ 'og_def_article_section' ] : 'none',
					'product_category' => isset( $opts[ 'og_def_product_category' ] ) ? $opts[ 'og_def_product_category' ] : 'none',
					'schema_type'      => $def_schema_type,

					/**
					 * Open Graph - Product Information.
					 */
					'product_avail'            => 'none',
					'product_brand'            => '',
					'product_color'            => '',
					'product_condition'        => 'none',
					'product_currency'         => empty( $opts[ 'plugin_def_currency' ] ) ? 'USD' : $opts[ 'plugin_def_currency' ],
					'product_isbn'             => '',
					'product_material'         => '',
					'product_mfr_part_no'      => '',	// Product MPN.
					'product_price'            => '0.00',
					'product_retailer_part_no' => '',	// Product SKU.
					'product_size'             => '',
					'product_target_gender'    => 'none',

					/**
					 * Open Graph - Priority Image.
					 */
					'og_img_max'    => isset( $opts[ 'og_img_max' ] ) ? (int) $opts[ 'og_img_max' ] : 1,	// 1 by default.
					'og_img_id'     => '',
					'og_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'og_img_url'    => '',

					/**
					 * Open Graph - Priority Video.
					 */
					'og_vid_max'      => isset( $opts[ 'og_vid_max' ] ) ? (int) $opts[ 'og_vid_max' ] : 1,	// 1 by default.
					'og_vid_autoplay' => empty( $opts[ 'og_vid_autoplay' ] ) ? 0 : 1,	// Enabled by default.
					'og_vid_prev_img' => empty( $opts[ 'og_vid_prev_img' ] ) ? 0 : 1,	// Enabled by default.
					'og_vid_width'    => '',	// Custom value for first video.
					'og_vid_height'   => '',	// Custom value for first video.
					'og_vid_embed'    => '',
					'og_vid_url'      => '',
					'og_vid_title'    => '',	// Custom value for first video.
					'og_vid_desc'     => '',	// Custom value for first video.

					/**
					 * Pinterest.
					 */
					'p_img_id'     => '',
					'p_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'p_img_url'    => '',

					/**
					 * Twitter Card.
					 */
					'tc_lrg_img_id'     => '',
					'tc_lrg_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'tc_lrg_img_url'    => '',

					'tc_sum_img_id'     => '',
					'tc_sum_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'tc_sum_img_url'    => '',

					/**
					 * Schema JSON-LD Markup / Google Rich Results.
					 */
					'schema_img_max'    => isset( $opts[ 'schema_img_max' ] ) ? (int) $opts[ 'schema_img_max' ] : 1,	// 1 by default.
					'schema_img_id'     => '',
					'schema_img_id_pre' => empty( $opts[ 'og_def_img_id_pre' ] ) ? '' : $opts[ 'og_def_img_id_pre' ],	// Default library prefix.
					'schema_img_url'    => '',

					/**
					 * Gravity View (Side Metabox).
					 */
					'gv_id_title' => 0,	// Title Field ID
					'gv_id_desc'  => 0,	// Description Field ID
					'gv_id_img'   => 0,	// Post Image Field ID
				);

				/**
				 * See https://developers.google.com/search/reference/robots_meta_tag.
				 */
				$default_directives = SucomUtil::get_robots_default_directives();

				foreach ( $default_directives as $directive => $value ) {

					$opt_key = SucomUtil::sanitize_hookname( 'robots_' . $directive );

					/**
					 * Use a default value from the plugin settings, if one exists.
					 */
					if ( isset( $this->p->options[ $opt_key ] ) ) {

						$md_defs[ $opt_key ] = $this->p->options[ $opt_key ];

					/**
					 * Save as a checkbox:
					 *
					 *	'robots_noarchive'
					 *	'robots_nofollow'
					 *	'robots_noimageindex'
					 *	'robots_noindex'
					 *	'robots_nosnippet'
					 *	'robots_notranslate'
					 */
					} elseif ( 0 === strpos( $directive, 'no' ) && is_bool( $value ) ) {

						$md_defs[ $opt_key ] = $value ? 1 : 0;

					/**
					 * Save the directive value:
					 *
					 *	'robots_max_snippet'
					 *	'robots_max_image_preview'
					 *	'robots_max_video_preview'
					 */
					} elseif ( 0 === strpos( $directive, 'max' ) ) {

						$md_defs[ $opt_key ] = $value;
					}
				}

				if ( WpssoOptions::can_cache() ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting options_filtered to 1' );
					}

					$md_defs[ 'options_filtered' ] = 1;	// Set before calling filter to prevent recursion.

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'options_filtered value unchanged' );
				}

				/**
				 * Since WPSSO Core v3.28.0.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying get_md_defaults filters' );
				}

				$md_defs = apply_filters( $this->p->lca . '_get_md_defaults', $md_defs, $mod );

				/**
				 * Since WPSSO Core v8.2.0.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying sanitize_md_defaults filters' );
				}

				$md_defs = apply_filters( $this->p->lca . '_sanitize_md_defaults', $md_defs, $mod );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'get_md_defaults filter skipped' );
			}

			if ( false !== $md_key ) {

				if ( isset( $md_defs[ $md_key ] ) ) {

					return $md_defs[ $md_key ];
				}

				return null;
			}

			return $md_defs;
		}

		public function get_options( $mod_id, $md_key = false, $filter_opts = true, $pad_opts = false ) {

			$ret_val = false === $md_key ? array() : null;	// Allow for $md_key = 0.

			return self::must_be_extended( $ret_val );
		}

		/**
		 * Check if options need to be upgraded.
		 *
		 * Returns true or false.
		 *
		 * $md_opts is passed by reference so the array can be modified.
		 */
		protected function upgrade_options( array &$md_opts, $mod_id ) {

			if ( empty( $md_opts ) ) {

				return false;
			}

			if ( isset( $md_opts[ 'options_version' ] ) && $md_opts[ 'options_version' ] === $this->p->cf[ 'opt' ][ 'version' ] ) {

				return false;
			}

			/**
			 * Save / create the current options version number for version checks to follow.
			 */
			$version_key = 'plugin_' . $this->p->lca . '_opt_version';

			$prev_version = empty( $md_opts[ $version_key ] ) ? 0 : $md_opts[ $version_key ];

			$rename_filter_name = $this->p->lca . '_rename_md_options_keys';
			$upgraded_filter_name = $this->p->lca . '_upgraded_md_options';

			$rename_options_keys = apply_filters( $rename_filter_name, self::$rename_md_options_keys );

			$this->p->util->rename_opts_by_ext( $md_opts, $rename_options_keys );

			/**
			 * Check for schema type IDs that need to be renamed.
			 */
			$schema_type_keys_preg = 'schema_type|plm_place_schema_type';

			foreach ( SucomUtil::preg_grep_keys( '/^(' . $schema_type_keys_preg . ')(_[0-9]+)?$/', $md_opts ) as $md_key => $md_val ) {

				if ( ! empty( $this->p->cf[ 'head' ][ 'schema_renamed' ][ $md_val ] ) ) {

					$md_opts[ $md_key ] = $this->p->cf[ 'head' ][ 'schema_renamed' ][ $md_val ];
				}
			}

			/**
			 * Import and delete deprecated robots post metadata.
			 */
			if ( $prev_version > 0 && $prev_version <= 759 ) {

				foreach ( array(
					'noarchive',
					'nofollow',
					'noimageindex',
					'noindex',
					'nosnippet',
					'notranslate',
				) as $directive ) {

					$opt_key = SucomUtil::sanitize_hookname( 'robots_' . $directive );

					$meta_key = SucomUtil::sanitize_hookname( '_' . $this->p->lca . '_' . $directive );

					$value = static::get_meta( $mod_id, $meta_key, $single = true );	// Use static method from child.

					if ( '' !== $value ) {

						$md_opts[ $opt_key ] = (int) $value;

						static::delete_meta( $mod_id, $meta_key );	// Use static method from child.
					}
				}
			}

			$md_opts = apply_filters( $upgraded_filter_name, $md_opts );

			/**
			 * Mark options as current.
			 */
			$md_opts[ 'options_version' ] = $this->p->cf[ 'opt' ][ 'version' ];

			return true;
		}

		/**
		 * Do not pass $md_opts by reference as the options array may get padded with default values.
		 */
		protected function return_options( $mod_id, array $md_opts, $md_key = false, $pad_opts = false ) {

			if ( $pad_opts ) {

				if ( empty( $md_opts[ 'options_padded' ] ) ) {

					$md_defs = $this->get_defaults( $mod_id );

					if ( is_array( $md_defs ) ) {	// Just in case.

						foreach ( $md_defs as $md_defs_key => $md_defs_val ) {

							if ( ! isset( $md_opts[ $md_defs_key ] ) && $md_defs_val !== '' ) {

								$md_opts[ $md_defs_key ] = $md_defs[ $md_defs_key ];
							}
						}
					}

					$md_opts[ 'options_padded' ] = true;
				}
			}

			if ( false !== $md_key ) {

				if ( ! isset( $md_opts[ $md_key ] ) || $md_opts[ $md_key ] === '' ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returning null value: ' . $md_key . ' not set or empty string' );
					}

					return null;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning meta value: ' . $md_key . ' = ' . $md_opts[ $md_key ] );
				}

				return $md_opts[ $md_key ];
			}

			return $md_opts;
		}

		public function save_options( $mod_id, $rel_id = false ) {

			return self::must_be_extended( $ret_val = false );
		}

		public function delete_options( $mod_id, $rel_id = false ) {

			return self::must_be_extended( $ret_val = false );
		}

		/**
		 * Get all publicly accessible post, term, or user IDs.
		 */
		public static function get_public_ids() {

			return self::must_be_extended( $ret_val = array() );
		}

		public function get_posts_ids( array $mod, $ppp = null, $paged = null, array $posts_args = array() ) {

			return self::must_be_extended( $ret_val = array() );	// Return an empty array.
		}

		public function get_posts_mods( array $mod, $ppp = null, $paged = null, array $posts_args = array() ) {

			$posts_mods = array();

			foreach ( $this->get_posts_ids( $mod, $ppp, $paged, $posts_args ) as $post_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting mod for post object ID ' . $post_id );
				}

				/**
				 * Get the post module array.
				 */
				$posts_mods[] = $this->p->post->get_mod( $post_id );
			}

			return $posts_mods;
		}

		public function add_meta_boxes() {

			return self::must_be_extended();
		}

		public function ajax_get_metabox_document_meta() {

			self::must_be_extended();

			die( -1 );	// Nothing to do.
		}

		public function show_metabox_document_meta( $obj ) {

			echo $this->get_metabox_document_meta( $obj );
		}

		public function get_metabox_document_meta( $obj ) {

			return self::must_be_extended( $ret_val = '' );	 // Empty html.
		}

		public function get_metabox_javascript( $container_id ) {

			$container_id = empty( $container_id ) ? '' : '#' . $container_id;

			$metabox_html = '';

			if ( SucomUtil::get_const( 'DOING_AJAX' ) ) {

				$metabox_html .= '<!-- metabox javascript for ajax call -->' . "\n";

				$metabox_html .= '<script type="text/javascript">sucomInitMetabox( "' . $container_id . '", true );</script>' . "\n";
			}

			return $metabox_html;
		}

		/**
		 * Does this page have a Document SSO metabox?
		 *
		 * If this is a post/term/user editing page, then the self::$head_tags variable will be an array.
		 */
		public static function is_meta_page() {

			if ( is_array( self::$head_tags ) ) {

				return true;
			}

			return false;
		}

		public static function get_head_tags() {

			return self::$head_tags;
		}

		protected function get_document_meta_tabs( $metabox_id, array $mod ) {

			$tabs = array();

			switch ( $metabox_id ) {

				case $this->p->cf[ 'meta' ][ 'id' ]:	// 'sso' metabox ID.

					$tabs[ 'edit' ]  = _x( 'Customize', 'metabox tab', 'wpsso' );
					$tabs[ 'media' ] = _x( 'Priority Media', 'metabox tab', 'wpsso' );

					if ( $mod[ 'is_public' ] ) {	// Since WPSSO Core v7.0.0.

						if ( ! empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

							$tabs[ 'robots' ] = _x( 'Robots Meta', 'metabox tab', 'wpsso' );	// Since WPSSO Core v8.4.0.
						}

						$tabs[ 'preview' ]  = _x( 'Preview', 'metabox tab', 'wpsso' );
						$tabs[ 'oembed' ]   = _x( 'oEmbed', 'metabox tab', 'wpsso' );
						$tabs[ 'head' ]     = _x( 'Head Markup', 'metabox tab', 'wpsso' );
						$tabs[ 'validate' ] = _x( 'Validate', 'metabox tab', 'wpsso' );
					}

					break;
			}

			/**
			 * Exclude the 'Priority Media' tab from attachment editing pages.
			 */
			if ( $mod[ 'post_type' ] === 'attachment' ) {

				unset( $tabs[ 'media' ] );
			}

			/**
			 * Exclude the 'oEmbed' tab from non-post editing pages.
			 */
			if ( ! function_exists( 'get_oembed_response_data' ) ||	! $mod[ 'is_post' ] || ! $mod[ 'id' ] ) {

				unset( $tabs[ 'oembed' ] );
			}

			return apply_filters( $this->p->lca . '_' . $mod[ 'name' ] . '_document_meta_tabs', $tabs, $mod, $metabox_id );
		}

		public function get_table_rows( $metabox_id, $tab_key, array $head_info, array $mod ) {

			$table_rows  = array();

			/**
			 * Call the following methods:
			 *
			 *	get_table_rows_sso_edit_tab()
			 *	get_table_rows_sso_media_tab()
			 *	get_table_rows_sso_preview_tab()
			 *	get_table_rows_sso_oembed_tab()
			 *	get_table_rows_sso_head_tab()
			 *	get_table_rows_sso_validate_tab()
			 */
			$method_name = 'get_table_rows_' . $metabox_id . '_' . $tab_key . '_tab';

			if ( method_exists( $this->edit, $method_name ) ) {

				$table_rows = call_user_func( array( $this->edit, $method_name ), $this->form, $head_info, $mod );
			}

			return $table_rows;
		}

		public function get_head_info( $mod_id, $read_cache = true ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $mod_id ] ) ) {

				return $local_cache[ $mod_id ];
			}

			$mod = $this->get_mod( $mod_id );

			$head_tags = $this->p->head->get_head_array( $use_post = false, $mod, $read_cache );

			$head_info = $this->p->head->extract_head_info( $mod, $head_tags );

			return $local_cache[ $mod_id ] = $head_info;
		}

		/**
		 * Return a specific option from the custom social settings meta with fallback for multiple option keys. If $md_key
		 * is an array, then get the first non-empty option from the options array. This is an easy way to provide a
		 * fallback value for the first array key. Use 'none' as a key name to skip this fallback behavior.
		 *
		 * Example: get_options_multi( $mod_id, array( 'seo_desc', 'og_desc' ) );
		 */
		public function get_options_multi( $mod_id, $md_key = false, $filter_opts = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'mod_id'      => $mod_id,
					'md_key'      => $md_key,
					'filter_opts' => $filter_opts,
				) );
			}

			if ( empty( $mod_id ) ) {

				return null;
			}

			if ( false === $md_key ) {					// Return the whole options array.

				$md_val = $this->get_options( $mod_id, $md_key, $filter_opts );

			} elseif ( true === $md_key ) {					// True is not valid for a custom meta key.

				$md_val = null;

			} else {							// Return the first matching index value.

				if ( is_array( $md_key ) ) {

					$check_md_keys = array_unique( $md_key );	// Prevent duplicate key values.

				} else {

					$check_md_keys = array( $md_key );		// Convert a string to an array.
				}

				foreach ( $check_md_keys as $md_key ) {

					if ( 'none' === $md_key ) {			// Special index keyword - stop here.

						return null;

					} elseif ( empty( $md_key ) ) {			// Skip empty array keys.

						continue;

					} elseif ( is_array( $md_key ) ) {		// An array of arrays is not valid.

						continue;

					} else {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'getting id ' . $mod_id . ' option ' . $md_key . ' value' );
						}

						if ( ( $md_val = $this->get_options( $mod_id, $md_key, $filter_opts ) ) !== null ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'option ' . $md_key . ' value found (not null)' );
							}

							break;	// Stop after first match.
						}
					}
				}
			}

			if ( $md_val !== null ) {

				if ( $this->p->debug->enabled ) {

					$mod = $this->get_mod( $mod_id );

					$this->p->debug->log( 'custom ' . $mod[ 'name' ] . ' ' . ( false === $md_key ? 'options' : 
						( is_array( $md_key ) ? implode( ', ', $md_key ) : $md_key ) ) . ' = ' . 
						( is_array( $md_val ) ? print_r( $md_val, true ) : '"' . $md_val . '"' ) );
				}
			}

			return $md_val;
		}

		public function user_can_edit( $mod_id, $rel_id = false ) {

			return self::must_be_extended( $ret_val = false );	// Return false by default.
		}

		public function user_can_save( $mod_id, $rel_id = false ) {

			return self::must_be_extended( $ret_val = false );	// Return false by default.
		}

		public function clear_cache( $mod_id, $rel_id = false ) {

			return self::must_be_extended();
		}

		protected function clear_mod_cache( array $mod, array $cache_types = array(), $sharing_url = false ) {

			if ( false === $sharing_url ) {

				$sharing_url = $this->p->util->get_sharing_url( $mod );
			}

			$mod_salt = SucomUtil::get_mod_salt( $mod, $sharing_url );

			$cache_types[ 'transient' ][] = array(
				'id'   => $this->p->lca . '_h_' . md5( 'WpssoHead::get_head_array(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_h_',
				'salt' => 'WpssoHead::get_head_array(' . $mod_salt . ')',
			);

			$cache_types[ 'wp_cache' ][] = array(
				'id'   => $this->p->lca . '_c_' . md5( 'WpssoPage::get_the_content(' . $mod_salt . ')' ),
				'pre'  => $this->p->lca . '_c_',
				'salt' => 'WpssoPage::get_the_content(' . $mod_salt . ')',
			);

			/**
			 * WordPress stores data using a post, term, or user ID, along with a group string.
			 *
			 * Example: wp_cache_get( 1, 'user_meta' );
			 */
			$cleared_count = wp_cache_delete( $mod[ 'id' ], $mod[ 'name' ] . '_meta' );

			$cleared_ids = array();

			foreach ( $cache_types as $type_name => $type_keys ) {

				/**
				 * $filter_name example: 'wpsso_post_cache_transient_keys'.
				 */
				$filter_name = $this->p->lca . '_' . $mod[ 'name' ] . '_cache_' . $type_name . '_keys';

				$type_keys = (array) apply_filters( $filter_name, $type_keys, $mod, $sharing_url, $mod_salt );

				foreach ( $type_keys as $mixed ) {

					if ( is_array( $mixed ) && isset( $mixed[ 'id' ] ) ) {

						$cache_id = $mixed[ 'id' ];

						$cache_key = '';
						$cache_key .= isset( $mixed[ 'pre' ] ) ? $mixed[ 'pre' ] : '';
						$cache_key .= isset( $mixed[ 'salt' ] ) ? $mixed[ 'salt' ] : '';
						$cache_key = trim( $cache_key );

						if ( empty( $cache_key ) ) {

							$cache_key = $cache_id;
						}

					} else {

						$cache_id = $cache_key = $mixed;
					}

					if ( isset( $cleared_ids[ $type_name ][ $cache_id ] ) ) {	// skip duplicate cache ids

						continue;
					}

					switch ( $type_name ) {

						case 'transient':

							$ret = delete_transient( $cache_id );

							break;

						case 'wp_cache':

							$ret = wp_cache_delete( $cache_id );

							break;

						default:

							$ret = false;

							break;
					}

					if ( $ret ) {

						$cleared_count++;
					}

					$cleared_ids[ $type_name ][ $cache_key ] = $ret;
				}
			}

			return $cleared_count;
		}

		protected function verify_submit_nonce() {

			if ( empty( $_POST ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'empty POST for submit' );
				}

				return false;

			}

			if ( empty( $_POST[ WPSSO_NONCE_NAME ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'submit POST missing nonce token' );
				}

				return false;

			}

			if ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'submit nonce token validation failed' );
				}

				if ( is_admin() ) {

					$this->p->notice->err( __( 'Nonce token validation failed for the submitted form (update ignored).',
						'wpsso' ) );
				}

				return false;

			}

			return true;
		}

		protected function get_submit_opts( $mod_id ) {

			$mod     = $this->get_mod( $mod_id );
			$md_defs = $this->get_defaults( $mod[ 'id' ] );
			$md_prev = $this->get_options( $mod[ 'id' ] );

			/**
			 * Remove plugin version strings.
			 */
			$md_unset_keys = array( 'options_filtered', 'options_version' );

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( isset( $info[ 'opt_version' ] ) ) {

					$md_unset_keys[] = 'plugin_' . $ext . '_opt_version';
				}
			}

			foreach ( $md_unset_keys as $md_key ) {

				unset( $md_defs[ $md_key ], $md_prev[ $md_key ] );
			}

			/**
			 * Merge and sanitize the new options.
			 */
			$md_opts = empty( $_POST[ WPSSO_META_NAME ] ) ? array() : $_POST[ WPSSO_META_NAME ];
			$md_opts = SucomUtil::restore_checkboxes( $md_opts );
			$md_opts = array_merge( $md_prev, $md_opts );	// Update the previous options array.
			$md_opts = $this->p->opt->sanitize( $md_opts, $md_defs, $network = false, $mod );

			/**
			 * Check image size options (id, prefix, width, height, crop, etc.).
			 */
			foreach ( array( 'og', 'p', 'schema', 'tc_lrg', 'tc_sum' ) as $md_pre ) {

				/**
				 * If there's no image id, then remove the image id library prefix.
				 *
				 * If an image id is being used, then remove the image url (only one can be defined).
				 */
				if ( empty( $md_opts[ $md_pre . '_img_id' ] ) ) {

					unset( $md_opts[ $md_pre . '_img_id_pre' ] );

				} else {

					unset( $md_opts[ $md_pre . '_img_url' ] );
				}
			}

			/**
			 * Remove empty strings, "use plugin settings", or "same as default" option values.
			 *
			 * Use strict comparison to manage conversion (don't allow string to integer conversion, for example).
			 */
			foreach ( $md_opts as $md_key => $md_val ) {

				if ( '' === $md_val ) {

					unset( $md_opts[ $md_key ] );

				} elseif ( isset( $md_defs[ $md_key ] ) && ( $md_val === $md_defs[ $md_key ] || $md_val === (string) $md_defs[ $md_key ] ) ) {

					unset( $md_opts[ $md_key ] );

				} elseif ( $md_val === WPSSO_UNDEF || $md_val === (string) WPSSO_UNDEF ) {

					switch ( $md_key ) {

						case 'robots_max_snippet':
						case 'robots_max_video_preview':

							break;

						default:

							unset( $md_opts[ $md_key ] );
					}
				}
			}

			/**
			 * Re-number multi options (example: schema type url, recipe ingredient, recipe instruction, etc.).
			 */
			foreach ( $this->p->cf[ 'opt' ][ 'cf_md_multi' ] as $md_multi => $is_multi ) {

				if ( empty( $is_multi ) ) {	// True, false, or array.

					continue;
				}

				/**
				 * Get multi option values indexed only by their number.
				 */
				$md_multi_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_([0-9]+)$/', $md_opts, $invert = false, $replace = '$1' );

				$md_renum_opts = array();	// Start with a fresh array.

				$renum = 0;	// Start a new index at 0.

				foreach ( $md_multi_opts as $md_num => $md_val ) {

					if ( $md_val !== '' ) {	// Only save non-empty values.

						$md_renum_opts[ $md_multi . '_' . $renum ] = $md_val;
					}

					/**
					 * Check if there are linked options, and if so, re-number those options as well.
					 */
					if ( is_array( $is_multi ) ) {

						foreach ( $is_multi as $md_multi_linked ) {

							if ( isset( $md_opts[ $md_multi_linked . '_' . $md_num ] ) ) {	// Just in case.

								$md_renum_opts[ $md_multi_linked . '_' . $renum ] = $md_opts[ $md_multi_linked . '_' . $md_num ];
							}
						}
					}

					$renum++;	// Increment the new index number.
				}

				/**
				 * Remove any existing multi options, including any linked options.
				 */
				$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi . '_([0-9]+)$/', $md_opts, $invert = true );

				if ( is_array( $is_multi ) ) {

					foreach ( $is_multi as $md_multi_linked ) {

						$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_multi_linked . '_([0-9]+)$/', $md_opts, $invert = true );
					}
				}

				/**
				 * Save the re-numbered options.
				 */
				foreach ( $md_renum_opts as $md_key => $md_val ) {

					$md_opts[ $md_key ] = $md_val;
				}

				unset( $md_renum_opts );
			}

			/**
			 * Mark the new options as current.
			 */
			if ( ! empty( $md_opts ) ) {

				$md_opts[ 'options_version' ] = $this->p->cf[ 'opt' ][ 'version' ];

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( isset( $info[ 'opt_version' ] ) ) {

						$md_opts[ 'plugin_' . $ext . '_opt_version' ] = $info[ 'opt_version' ];
					}
				}
			}

			return $md_opts;
		}

		/**
		 * Return translated column headers.
		 *
		 * Called by add_column_headings(), WpssoStdAdminAdvanced->filter_edit_table_columns_rows(), and WpssoProAdminAdvanced->filter_edit_table_columns_rows().
		 */
		public static function get_column_headers() {

			$headers = array();

			$sortable_cols = self::get_sortable_columns();

			foreach ( $sortable_cols as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'header' ] ) ) {

					$headers[ $col_key ] = _x( $col_info[ 'header' ], 'column header', 'wpsso' );
				}
			}

			return $headers;
		}

		/**
		 * Return sortable column keys and their query sort info.
		 */
		public static function get_sortable_columns( $col_key = false ) {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$wpsso =& Wpsso::get_instance();

				$local_cache = (array) apply_filters( $wpsso->lca . '_get_sortable_columns', $wpsso->cf[ 'edit' ][ 'columns' ] );

				if ( $wpsso->debug->enabled ) {

					$wpsso->debug->log_arr( 'sortable columns', $local_cache );
				}
			}

			if ( false !== $col_key ) {

				if ( isset( $local_cache[ $col_key ] ) ) {

					return $local_cache[ $col_key ];
				}

				return null;

			}

			return $local_cache;
		}

		public static function get_column_meta_keys() {

			$meta_keys = array();

			$sortable_cols = self::get_sortable_columns();

			foreach ( $sortable_cols as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'meta_key' ] ) ) {

					$meta_keys[ $col_key ] = $col_info[ 'meta_key' ];
				}
			}

			return $meta_keys;
		}

		public function get_column_wp_cache( array $mod, $column_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$column_val = '';

			if ( ! empty( $mod[ 'id' ] ) && strpos( $column_name, $this->p->lca . '_' ) === 0 ) {	// Just in case.

				$col_key = str_replace( $this->p->lca . '_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {

					if ( isset( $col_info[ 'meta_key' ] ) ) {	// Just in case.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'getting meta cache for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
						}

						/**
						 * WordPress stores data using a post, term, or user ID, along with a group string.
						 *
						 * Example: wp_cache_get( 1, 'user_meta' );
						 *
						 * Returns (bool|mixed) false on failure to retrieve contents or the cache contents on success.
						 *
						 * $found (bool) (Optional) whether the key was found in the cache (passed by reference). Disambiguates a
						 * return of false, a storable value. Default null.
						 */
						$meta_cache = wp_cache_get( $mod[ 'id' ], $mod[ 'name' ] . '_meta', $force = false, $found );

						if ( ! $found ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'updating meta cache for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] );
							}

							$meta_cache = update_meta_cache( $mod[ 'name' ], array( $mod[ 'id' ] ) );

							$meta_cache = $meta_cache[ $mod[ 'id' ] ];
						}

						if ( isset( $meta_cache[ $col_info[ 'meta_key' ] ] ) ) {

							$column_val = (string) maybe_unserialize( $meta_cache[ $col_info[ 'meta_key' ] ][ 0 ] );
						}
					}
				}
			}

			return $column_val;
		}

		public function get_column_content( $value, $column_name, $mod_id ) {

			if ( ! empty( $mod_id ) && 0 === strpos( $column_name, $this->p->lca . '_' ) ) {	// Just in case.

				$col_key = str_replace( $this->p->lca . '_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {

					if ( isset( $col_info[ 'meta_key' ] ) ) {	// just in case

						$value = $this->get_meta_cache_value( $mod_id, $col_info[ 'meta_key' ] );
					}
				}
			}

			return $value;
		}

		public function get_meta_cache_value( $mod_id, $meta_key, $none = '' ) {

			return self::must_be_extended( $none );
		}

		protected function check_sortable_metadata( $value, $mod_id, $meta_key, $single ) {

			/**
			 * Example $meta_key value:
			 *
			 *	'_wpsso_head_info_og_img_thumb'.
			 */
			if ( 0 !== strpos( $meta_key, '_' . $this->p->lca . '_head_info_' ) ) {

				return $value;	// Return null.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_no_recurs = array();

			if ( isset( $local_no_recurs[ $mod_id ][ $meta_key ] ) ) {

				return $value;	// Return null.
			}

			$local_no_recurs[ $mod_id ][ $meta_key ] = true;	// Prevent recursion.

			if ( '' === static::get_meta( $mod_id, $meta_key, $single = true ) ) {	// Use static method from child.

				$this->get_head_info( $mod_id, $read_cache = true );
			}

			unset( $local_no_recurs[ $mod_id ][ $meta_key ] );

			return $value;	// Return null.
		}

		public function update_sortable_meta( $mod_id, $col_key, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $mod_id ) ) {	// Just in case.

				if ( ( $sortable_cols = self::get_sortable_columns( $col_key ) ) !== null ) {

					if ( isset( $sortable_cols[ 'meta_key' ] ) ) {	// Just in case.

						static::update_meta( $mod_id, $sortable_cols[ 'meta_key' ], $content );	// Use static method from child.
					}
				}
			}
		}

		public function add_sortable_columns( $columns ) {

			foreach ( self::get_sortable_columns() as $col_key => $col_info ) {

				if ( ! empty( $col_info[ 'orderby' ] ) ) {

					$columns[ $this->p->lca . '_' . $col_key ] = $this->p->lca . '_' . $col_key;
				}
			}

			return $columns;
		}

		public function set_column_orderby( $query ) {

			$col_name = $query->get( 'orderby' );

			if ( is_string( $col_name ) && strpos( $col_name, $this->p->lca . '_' ) === 0 ) {

				$col_key = str_replace( $this->p->lca . '_', '', $col_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {

					foreach ( array( 'meta_key', 'orderby' ) as $set_name ) {

						if ( ! empty( $col_info[ $set_name ] ) ) {

							$query->set( $set_name, $col_info[ $set_name ] );
						}
					}
				}
			}
		}

		/**
		 * Called by WpssoPost->add_post_column_headings(), WpssoPost->add_media_column_headings(),
		 * WpssoTerm->add_term_column_headings(), and WpssoUser->add_user_column_headings().
		 */
		protected function add_column_headings( $columns, $list_type = '' ) {

			if ( ! empty( $list_type ) ) {

				foreach ( self::get_column_headers() as $col_key => $col_header ) {

					/**
					 * Check if the column is enabled globally for the post, media, term, or user edit list.
					 */
					if ( ! empty( $this->p->options[ 'plugin_' . $col_key . '_col_' . $list_type ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding ' . $this->p->lca . '_' . $col_key . ' column' );
						}

						$columns[ $this->p->lca . '_' . $col_key ] = $col_header;
					}
				}
			}

			return $columns;
		}

		/**
		 * Returns an array of single image associative arrays.
		 *
		 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
		 *
		 * $md_pre can be a text string or array of prefixes.
		 */
		public function get_md_images( $num = 0, $size_names, array $mod, $check_dupes = true, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'num'         => $num,
					'size_names'  => $size_names,
					'mod'         => $mod,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
					'mt_pre'      => $mt_pre,
				), get_class( $this ) );
			}

			if ( empty( $mod[ 'id' ] ) ) {	// Just in case.

				return array();

			} elseif ( $num < 1 ) {	// Just in case.

				return array();
			}

			$size_names = $this->p->util->get_image_size_names( $size_names );	// Always returns an array.

			$md_pre = is_array( $md_pre ) ? array_merge( $md_pre, array( 'og' ) ) : array( $md_pre, 'og' );

			$mt_images  = array();

			foreach( array_unique( $md_pre ) as $opt_pre ) {

				if ( $opt_pre === 'none' ) {		// Special index keyword.

					break;

				} elseif ( empty( $opt_pre ) ) {	// Skip empty md_pre values.

					continue;
				}

				$pid = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_id' );
				$pre = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_id_pre' );
				$url = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url' );

				if ( $pid > 0 ) {

					$pid = $pre === 'ngg' ? 'ngg-' . $pid : $pid;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using custom ' . $opt_pre . ' image id = "' . $pid . '"', get_class( $this ) );
					}

					$mt_images = $this->p->media->get_mt_pid_images( $pid, $size_names, $check_dupes, $mt_pre );

				}

				if ( empty( $mt_images ) && $url ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'using custom ' . $opt_pre . ' image url = "' . $url . '"', get_class( $this ) );
					}

					$img_width  = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url:width' );
					$img_height = $this->get_options( $mod[ 'id' ], $opt_pre . '_img_url:height' );

					$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

					$mt_single_image[ $mt_pre . ':image:url' ]    = $url;
					$mt_single_image[ $mt_pre . ':image:width' ]  = $img_width > 0 ? $img_width : WPSSO_UNDEF;
					$mt_single_image[ $mt_pre . ':image:height' ] = $img_height > 0 ? $img_height : WPSSO_UNDEF;

					if ( $this->p->util->push_max( $mt_images, $mt_single_image, $num ) ) {

						return $mt_images;
					}
				}

				if ( $pid || $url ) {	// Stop after first $md_pre image found.

					break;
				}
			}

			if ( $this->p->util->is_maxed( $mt_images, $num ) ) {

				return $mt_images;
			}

			$filter_name = $this->p->lca . '_' . $mod[ 'name' ] . '_image_ids';

			$image_ids = apply_filters( $filter_name, array(), $size_names, $mod[ 'id' ], $mod );

			foreach ( $image_ids as $pid ) {

				if ( $pid > 0 ) {	// Quick sanity check.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding image pid: ' . $pid );
					}

					$mt_pid_images = $this->p->media->get_mt_pid_images( $pid, $size_names, $check_dupes, $mt_pre );

					if ( $this->p->util->merge_max( $mt_images, $mt_pid_images, $num ) ) {

						return $mt_images;
					}
				}
			}

			$filter_name = $this->p->lca . '_' . $mod[ 'name' ] . '_image_urls';

			$image_urls = apply_filters( $filter_name, array(), $size_names, $mod[ 'id' ], $mod );

			foreach ( $image_urls as $url ) {

				if ( false !== strpos( $url, '://' ) ) {	// Quick sanity check.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding image url: ' . $url );
					}

					$mt_single_image = SucomUtil::get_mt_image_seed( $mt_pre );

					$mt_single_image[ $mt_pre . ':image:url' ] = $url;

					$this->p->util->add_image_url_size( $mt_single_image, $mt_pre . ':image' );

					if ( $this->p->util->push_max( $mt_images, $mt_single_image, $num ) ) {

						return $mt_images;
					}
				}
			}

			return $mt_images;
		}

		/**
		 * Extended by the WpssoUser class to support non-WordPress user images.
		 *
		 * Returns an array of single image associative arrays.
		 *
		 * $md_pre can be a text string or array of prefixes.
		 */
		public function get_og_images( $num = 0, $size_names, $mod_id, $check_dupes = true, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$mod = $this->get_mod( $mod_id );

			return $this->get_md_images( $num, $size_names, $mod, $check_dupes, $md_pre, $mt_pre );
		}

		/**
		 * Returns an array of single video associative arrays.
		 *
		 * $md_pre can be a text string or array of prefixes.
		 */
		public function get_og_videos( $num = 0, $mod_id, $check_dupes = false, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'num'         => $num,
					'mod_id'      => $mod_id,
					'check_dupes' => $check_dupes,
					'md_pre'      => $md_pre,
					'mt_pre'      => $mt_pre,
				), get_class( $this ) );
			}

			if ( empty( $mod_id ) ) {	// Just in case.

				return array();

			} elseif ( $num < 1 ) {	// Just in case.

				return array();
			}

			$mod = $this->get_mod( $mod_id );	// Required for get_content_videos().

			$md_pre = is_array( $md_pre ) ? array_merge( $md_pre, array( 'og' ) ) : array( $md_pre, 'og' );

			$mt_videos = array();

			foreach( array_unique( $md_pre ) as $opt_pre ) {

				if ( $opt_pre === 'none' ) {		// Special index keyword.

					break;

				} elseif ( empty( $opt_pre ) ) {	// Skip empty md_pre values.

					continue;
				}

				$html = $this->get_options( $mod_id, $opt_pre . '_vid_embed' );
				$url  = $this->get_options( $mod_id, $opt_pre . '_vid_url' );

				if ( $html ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'fetching video(s) from custom ' . $opt_pre . ' embed code', get_class( $this ) );
					}

					/**
					 * Returns an array of single video associative arrays.
					 */
					$mt_videos = $this->p->media->get_content_videos( $num, $mod, $check_dupes, $html );
				}

				if ( empty( $mt_videos ) && $url && ( ! $check_dupes || $this->p->util->is_uniq_url( $url ) ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'fetching video from custom ' . $opt_pre . ' url ' . $url, get_class( $this ) );
					}

					$args = array(
						'url'      => $url,
						'width'    => WPSSO_UNDEF,
						'height'   => WPSSO_UNDEF,
						'type'     => '',
						'prev_url' => '',
						'post_id'  => null,
						'api'      => '',
					);

					/**
					 * Returns a single video associative array.
					 */
					$mt_single_video = $this->p->media->get_video_details( $args, $check_dupes, true );

					if ( ! empty( $mt_single_video ) ) {

						if ( $this->p->util->push_max( $mt_videos, $mt_single_video, $num ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'returning ' . count( $mt_videos ) . ' videos' );
							}

							return $mt_videos;
						}
					}
				}

				if ( $html || $url ) {	// Stop after first $md_pre video found.

					break;
				}
			}

			return $mt_videos;
		}

		public function get_head_info_thumb_bg_img( $head_info, $mod, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting thumbnail image' );	// Begin timer.
			}

			$media_html = '';

			if ( empty( $head_info[ $mt_pre . ':image:id' ] ) ) {

				$media_html .= '<!-- checking head info array for image url -->';

			} else {

				$pid = $head_info[ $mt_pre . ':image:id' ];

				$size_name = 'thumbnail';

				/**
				 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
				 *
				 * Example:
				 *
				 * 	array(
				 *		$mt_pre . ':image:url'        => '',
				 *		$mt_pre . ':image:width'      => '',
				 *		$mt_pre . ':image:height'     => '',
				 *		$mt_pre . ':image:cropped'    => '',	// Non-standard / internal meta tag.
				 *		$mt_pre . ':image:id'         => '',	// Non-standard / internal meta tag.
				 *		$mt_pre . ':image:alt'        => '',
				 *		$mt_pre . ':image:size_name'  => '',	// Non-standard / internal meta tag.
				 * 	);
				 */
				$mt_single_image = $this->p->media->get_mt_single_image_src( $pid, $size_name, $check_dupes = false, $mt_pre );

				$media_html .= '<!-- getting ' . $size_name . ' size for image id ' . $pid . ' = ';

				if ( empty( $mt_single_image[ $mt_pre . ':image:url' ] ) ) {	// Just in case.

					$media_html .= 'failed';

				} else {

					$media_html .= 'success';

					$head_info =& $mt_single_image;	// Use the updated image information.
				}

				$media_html .= ' -->';
			}

			$image_url = SucomUtil::get_first_mt_media_url( $head_info );

			if ( empty( $image_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no thumbnail image url' );
				}

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'thumbnail image url = ' . $image_url );
				}

				$media_html .= '<div class="wp-thumb-bg-img" style="background-image:url(' . $image_url . ');"></div><!-- .wp-thumb-bg-img -->';
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( 'getting thumbnail image' );	// End timer.
			}

			return $media_html;
		}

		/**
		 * Methods that return an associative array of Open Graph meta tags.
		 */
		public function get_og_type_reviews( $mod_id, $og_type = 'product', $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			return self::must_be_extended( $ret_val = array() );	// Return an empty array.
		}

		public function get_og_comment_review( $comment_obj, $og_type = 'product', $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			return self::must_be_extended( $ret_val = array() );	// Return an empty array.
		}

		/**
		 * WpssoPost class specific methods.
		 */
		public function get_sharing_shortlink( $shortlink = false, $mod_id = 0, $context = 'post', $allow_slugs = true ) {

			return self::must_be_extended( $ret_val = '' );
		}

		public function maybe_restore_shortlink( $shortlink = false, $mod_id = 0, $context = 'post', $allow_slugs = true ) {

			return self::must_be_extended( $ret_val = '' );
		}

		/**
		 * WpssoUser class specific methods.
		 *
		 * Called by WpssoOpenGraph->get_array() for a single post author and (possibly) several coauthors.
		 */
		public function get_authors_websites( $user_ids, $field_id = 'url' ) {

			return self::must_be_extended( $ret_val = array() );
		}

		public function get_author_website( $user_id, $field_id = 'url' ) {

			return self::must_be_extended( $ret_val = '' );
		}

		/**
		 * Since WPSSO Core v7.6.0.
		 */
		public static function get_attached( $mod_id, $attach_type ) {

			$opts = static::get_meta( $mod_id, WPSSO_META_ATTACHED_NAME, $single = true );	// Use static method from child.

			if ( isset( $opts[ $attach_type ] ) ) {

				if ( is_array( $opts[ $attach_type ] ) ) {	// Just in case.

					return $opts[ $attach_type ];
				}
			}

			return array();	// No values.
		}

		/**
		 * Since WPSSO Core v7.6.0.
		 *
		 * Used by WpssoFaqShortcodeQuestion->do_shortcode().
		 */
		public static function add_attached( $mod_id, $attach_type, $attachment_id ) {

			$opts = static::get_meta( $mod_id, WPSSO_META_ATTACHED_NAME, $single = true );	// Use static method from child.

			if ( ! isset( $opts[ $attach_type ][ $attachment_id ] ) ) {

				if ( ! is_array( $opts ) ) {

					$opts = array();
				}

				$opts[ $attach_type ][ $attachment_id ] = true;

				return static::update_meta( $mod_id, WPSSO_META_ATTACHED_NAME, $opts );	// Use static method from child.
			}

			return false;	// No addition.
		}

		/**
		 * Since WPSSO Core v7.6.0.
		 */
		public static function delete_attached( $mod_id, $attach_type, $attachment_id ) {

			$opts = static::get_meta( $mod_id, WPSSO_META_ATTACHED_NAME, $single = true );	// Use static method from child.

			if ( isset( $opts[ $attach_type ][ $attachment_id ] ) ) {

				unset( $opts[ $attach_type ][ $attachment_id ] );

				if ( empty( $opts ) ) {	// Cleanup.

					return static::delete_meta( $mod_id, WPSSO_META_ATTACHED_NAME );	// // Use static method from child.
				}

				return static::update_meta( $mod_id, WPSSO_META_ATTACHED_NAME, $opts );	// // Use static method from child.
			}

			return false;	// No delete.
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function get_meta( $mod_id, $meta_key, $single = false ) {

			$ret_val = false === $single ? array() : '';

			return self::must_be_extended( $ret_val );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function update_meta( $mod_id, $meta_key, $value ) {

			return self::must_be_extended( $ret_val = false ); // No update.
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function delete_meta( $mod_id, $meta_key ) {

			return self::must_be_extended( $ret_val = false ); // No delete.
		}

		protected static function must_be_extended( $method, $ret_val = null ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( 'method must be extended', $class_seq = 2, $func_seq = 2 );
			}

			return $ret_val;
		}
	}
}
