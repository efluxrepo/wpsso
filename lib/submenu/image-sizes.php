<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuImageSizes' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuImageSizes extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;

			$this->p->util->add_plugin_filters( $this, array(
				'form_button_rows' => 2,	// Filter form buttons for all settings pages.
			) );
		}

		public function filter_form_button_rows( $form_button_rows, $menu_id ) {

			$row_num = null;

			switch ( $menu_id ) {

				case 'image-sizes':

					$row_num = 0;

					break;

				case 'tools':

					$row_num = 2;

					break;
			}

			if ( null !== $row_num ) {	// Just in case.

				$form_button_rows[ $row_num ][ 'reload_default_image_sizes' ] = _x( 'Reload Default Image Sizes', 'submit button', 'wpsso' );
			}

			return $form_button_rows;
		}

		/**
		 * Called by the extended WpssoAdmin class.
		 */
		protected function add_meta_boxes() {

			$metabox_id      = 'image_dimensions';
			$metabox_title   = _x( 'Social and Search Image Sizes', 'metabox title', 'wpsso' );
			$metabox_screen  = $this->pagehook;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'page_id'    => SucomUtil::sanitize_hookname( $this->menu_id ),
				'metabox_id' => $metabox_id,
			);

			add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, 'show_metabox_table' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );
		}

		protected function get_table_rows( $page_id, $metabox_id ) {

			$table_rows = array();

			switch ( $page_id . '-' . $metabox_id ) {

				case 'image_sizes-image_dimensions':

					if ( $info_msg = $this->p->msgs->get( 'info-' . $metabox_id ) ) {

						$table_rows[ 'info-' . $metabox_id ] = '<td colspan="2">' . $info_msg . '</td>';
					}

					$p_img_disabled = empty( $this->p->options[ 'p_add_img_html' ] ) ? true : false;
					$p_img_msg      = $p_img_disabled ? $this->p->msgs->p_img_disabled( $extra_css_class = 'inline' ) : '';

					$table_rows[ 'og_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Open Graph (Facebook and oEmbed)', 'option label', 'wpsso' ), '', 'og_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'og_img' ) . '</td>';

					$table_rows[ 'p_img_size' ] = ( $p_img_disabled ? $this->form->get_tr_hide( 'basic' ) : '' ) .
					$this->form->get_th_html( _x( 'Pinterest Pin It', 'option label', 'wpsso' ), '', 'p_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'p_img', $p_img_disabled ) . $p_img_msg . '</td>';

					$table_rows[ 'schema_01_01_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Schema 1:1 (Google)', 'option label', 'wpsso' ), '', 'schema_1_1_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'schema_1_1_img' ) . '</td>';

					$table_rows[ 'schema_04_03_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Schema 4:3 (Google)', 'option label', 'wpsso' ), '', 'schema_4_3_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'schema_4_3_img' ) . '</td>';

					$table_rows[ 'schema_16_09_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Schema 16:9 (Google)', 'option label', 'wpsso' ), '', 'schema_16_9_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'schema_16_9_img' ) . '</td>';

					$table_rows[ 'schema_thumb_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Schema Thumbnail Image', 'option label', 'wpsso' ), '', 'thumb_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'thumb_img' ) . '</td>';

					$table_rows[ 'tc_00_sum_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Twitter Summary Card', 'option label', 'wpsso' ), '', 'tc_sum_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_sum_img' ) . '</td>';

					$table_rows[ 'tc_01_lrg_img_size' ] = '' .
					$this->form->get_th_html( _x( 'Twitter Large Image Summary Card', 'option label', 'wpsso' ), '', 'tc_lrg_img_size' ) . 
					'<td>' . $this->form->get_input_image_dimensions( 'tc_lrg_img' ) . '</td>';

					break;
			}

			return $table_rows;
		}
	}
}
