<?php
/**
 * An extension for the Connections Business Directory which add a repeatable field for entering the degrees and honours an individual has received.
 *
 * @package   Connections Business Directory Degrees
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2017 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Degrees
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for the Connections Business Directory which add a repeatable field for entering the degrees and honours an individual has received.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections-degrees
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Connections_Degrees' ) ) {

	final class Connections_Degrees {

		const VERSION = '1.0';

		/**
		 * @var string The absolute path this this file.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $basename = '';

		public function __construct() {

			self::$file       = __FILE__;
			self::$url        = plugin_dir_url( self::$file );
			self::$path       = plugin_dir_path( self::$file );
			self::$basename   = plugin_basename( self::$file );

			self::loadDependencies();

			// This should run on the `plugins_loaded` action hook. Since the extension loads on the
			// `plugins_loaded action hook, call immediately.
			self::loadTextdomain();

			// Register CSS and JavaScript.
			add_action( 'init', array( __CLASS__ , 'registerScripts' ) );

			// Add to Connections menu.
			add_filter( 'cn_submenu', array( __CLASS__, 'addMenu' ) );

			// Add bulk action to Categories to convert to Degree or School.
			add_filter( 'bulk_actions-connections_page_connections_categories', array( __CLASS__, 'registerBulkActions' ) );

			// Callbacks to process bulk actions.
			add_action( 'bulk_term_action-category-convert_to_degree', array( __CLASS__, 'processConvertCategoryToDegree' ) );
			add_action( 'bulk_term_action-category-convert_to_school', array( __CLASS__, 'processConvertCategoryToSchool' ) );

			// Remove the "View" link from the "Facility" taxonomy admin page.
			add_filter( 'cn_degree_row_actions', array( __CLASS__, 'removeViewAction' ) );

			// Register the metabox.
			add_action( 'cn_metabox', array( __CLASS__, 'registerMetabox') );

			// Law License uses a custom field type, so let's add the action to add it.
			add_action( 'cn_meta_field-degrees', array( __CLASS__, 'field' ), 10, 2 );

			// Since we're using a custom field, we need to add our own sanitization method.
			add_filter( 'cn_meta_sanitize_field-degrees', array( __CLASS__, 'sanitize') );

			// Attach Degrees to entry when saving an entry.
			add_action( 'cn_process_taxonomy-category', array( __CLASS__, 'attachDegrees' ), 9, 2 );

			// Add the "Facilities" option to the admin settings page.
			// This is also required so it'll be rendered by $entry->getContentBlock( 'degrees' ).
			add_filter( 'cn_content_blocks', array( __CLASS__, 'registerContentBlockOptions') );

			// Add the action that'll be run when calling $entry->getContentBlock( 'law_licenses' ) from within a template.
			add_action( 'cn_output_meta_field-degrees', array( __CLASS__, 'block' ), 10, 4 );

			// Register the widget.
			//add_action( 'widgets_init', array( 'CN_Degrees_Widget', 'register' ) );
		}

		/**
		 * The widget.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @return void
		 */
		private static function loadDependencies() {

			//require_once( self::$path . 'includes/class.widgets.php' );
		}

		/**
		 * Load the plugin translation.
		 *
		 * Credit: Adapted from Ninja Forms / Easy Digital Downloads.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @return void
		 */
		public static function loadTextdomain() {

			// Plugin textdomain. This should match the one set in the plugin header.
			$domain = 'connections-degrees';

			// Set filter for plugin's languages directory
			$languagesDirectory = apply_filters( "cn_{$domain}_languages_directory", dirname( self::$file ) . '/languages/' );

			// Traditional WordPress plugin locale filter
			$locale   = apply_filters( 'plugin_locale', get_locale(), $domain );
			$fileName = sprintf( '%1$s-%2$s.mo', $domain, $locale );

			// Setup paths to current locale file
			$local  = $languagesDirectory . $fileName;
			$global = WP_LANG_DIR . "/{$domain}/" . $fileName;

			if ( file_exists( $global ) ) {

				// Look in global `../wp-content/languages/{$domain}/` folder.
				load_textdomain( $domain, $global );

			} elseif ( file_exists( $local ) ) {

				// Look in local `../wp-content/plugins/{plugin-directory}/languages/` folder.
				load_textdomain( $domain, $local );

			} else {

				// Load the default language files
				load_plugin_textdomain( $domain, FALSE, $languagesDirectory );
			}
		}

		public static function registerScripts() {

			// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
			$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$min = '';

			$requiredCSS = class_exists( 'Connections_Form' ) ? array( 'cn-public', 'cn-form-public' ) : array( 'cn-public' );

			// Register CSS.
			//wp_register_style( 'cnbh-admin' , CNBH_URL . "assets/css/cnbh-admin$min.css", array( 'cn-admin', 'cn-admin-jquery-ui' ) , CNBH_CURRENT_VERSION );
			//wp_register_style( 'cnbh-public', CNBH_URL . "assets/css/cnbh-public$min.css", $requiredCSS, CNBH_CURRENT_VERSION );

			// Register JavaScript.
			//wp_register_script( 'jquery-timepicker' , CNBH_URL . "assets/js/jquery-ui-timepicker-addon$min.js", array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ) , '1.4.3' );
			wp_register_script( 'cnd-ui-js' , self::$url . "assets/js/cnd-common$min.js", array( 'jquery-chosen', 'jquery-ui-sortable' ) , self::VERSION, true );

			//wp_localize_script( 'cnbh-ui-js', 'cnbhDateTimePickerOptions', Connections_Business_Hours::dateTimePickerOptions() );
		}

		public static function addMenu( $menu ) {

			$menu['61.98']  = array(
				'hook'       => 'degrees',
				'page_title' => 'Connections : ' . __( 'Degrees', 'connections-degrees' ),
				'menu_title' => __( 'Degrees', 'connections-degrees' ),
				'capability' => 'connections_edit_categories',
				'menu_slug'  => 'connections_degrees',
				'function'   => array( __CLASS__, 'showPage' ),
			);

			$menu['61.99']  = array(
				'hook'       => 'schools',
				'page_title' => 'Connections : ' . __( 'Schools', 'connections-degrees' ),
				'menu_title' => __( 'Schools', 'connections-degrees' ),
				'capability' => 'connections_edit_categories',
				'menu_slug'  => 'connections_schools',
				'function'   => array( __CLASS__, 'showPage' ),
			);

			return $menu;
		}

		public static function showPage() {

			// Grab an instance of the Connections object.
			$instance = Connections_Directory();

			if ( $instance->dbUpgrade ) {

				include_once CN_PATH . 'includes/inc.upgrade.php';
				connectionsShowUpgradePage();
				return;
			}

			switch ( $_GET['page'] ) {

				case 'connections_degrees':
					include_once self::$path . 'includes/admin/pages/degrees.php';
					connectionsShowDegreesPage();
					break;

				case 'connections_schools':
					include_once self::$path . 'includes/admin/pages/schools.php';
					connectionsShowSchoolsPage();
					break;
			}
		}

		/**
		 * Callback for the `bulk_actions-connections_page_connections_categories` filter.
		 *
		 * @param array $actions
		 *
		 * @return array
		 */
		public static function registerBulkActions( $actions ) {

			$actions['convert_to_degree'] = 'Convert to Degree';
			$actions['convert_to_school'] = 'Convert to School';

			return $actions;
		}

		/**
		 * Callback for the `bulk_term_action-category-convert_to_degree` action.
		 */
		public static function processConvertCategoryToDegree() {

			self::convertTaxonomy( $_REQUEST['category'], 'category', 'degree' );
		}

		/**
		 * Callback for the `bulk_term_action-category-convert_to_school` action.
		 */
		public static function processConvertCategoryToSchool() {

			self::convertTaxonomy( $_REQUEST['category'], 'category', 'school' );
		}

		/**
		 * Convert an array of term ID/s from one taxonomy to another.
		 *
		 * NOTE: When converting a parent term, its descendants will also be converted but the hierarchy is not preserved.
		 *
		 * @param array  $term_ids An Array of term ID to convert.
		 * @param string $from     The taxonomy to convert from.
		 * @param string $to       The taxonomy to convert to.
		 *
		 * @return bool
		 */
		private static function convertTaxonomy( $term_ids, $from, $to ) {

			global $wpdb;

			//$to = $_POST['new_tax'];
			//
			//if ( ! taxonomy_exists( $to ) ) {
			//	return FALSE;
			//}
			//
			//if ( $to == $from ) {
			//	return FALSE;
			//}

			$tt_ids = array();
			$table  = CN_TERM_TAXONOMY_TABLE;

			foreach ( $term_ids as $term_id ) {

				$term = cnTerm::get( $term_id, $from );

				if ( $term->parent && ! in_array( $term->parent, $term_ids ) ) {

					$wpdb->update(
						$table,
						array( 'parent' => 0 ),
						array( 'term_taxonomy_id' => $term->term_taxonomy_id )
					);
				}

				$tt_ids[] = $term->term_taxonomy_id;

				//if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				if ( TRUE ) {

					$child_terms = cnTerm::getTaxonomyTerms(
						$from,
						array(
							'child_of'   => $term_id,
							'hide_empty' => FALSE,
						)
					);

					$tt_ids = array_merge( $tt_ids, wp_list_pluck( $child_terms, 'term_taxonomy_id' ) );
				}
			}

			$tt_ids = implode( ',', array_map( 'absint', $tt_ids ) );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table SET taxonomy = %s WHERE term_taxonomy_id IN ( $tt_ids )",
					$to
				)
			);

			//if ( is_taxonomy_hierarchical( $from ) && ! is_taxonomy_hierarchical( $to ) ) {
			if ( TRUE ) {

				$wpdb->query( "UPDATE $table SET parent = 0 WHERE term_taxonomy_id IN ( $tt_ids )" );
			}

			cnTerm::cleanCache( $tt_ids, $from );
			cnTerm::cleanCache( $tt_ids, $to );

			//do_action( 'term_management_tools_term_changed_taxonomy', $tt_ids, $to, $from );

			return TRUE;
		}

		public static function removeViewAction( $actions ) {

			unset( $actions['view'] );

			return $actions;
		}

		/**
		 * Registered the custom metabox.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 */
		public static function registerMetabox() {

			$atts = array(
				'id'       => 'metabox-degrees',
				'title'    => __( 'Degrees', 'connections-degrees' ),
				'context'  => 'normal',
				'priority' => 'core',
				'fields'   => array(
					array(
						'id'    => 'degrees',
						'type'  => 'degrees',
					),
				),
			);

			cnMetaboxAPI::add( $atts );
		}

		/**
		 * Callback for the `cn_content_blocks` filter.
		 *
		 * Add the custom meta as an option in the content block settings in the admin.
		 * This is required for the output to be rendered by $entry->getContentBlock().
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param array $blocks An associative array containing the registered content block settings options.
		 *
		 * @return array
		 */
		public static function registerContentBlockOptions( $blocks ) {

			$blocks['degrees'] = __( 'Degrees', 'Connections_Degrees' );

			return $blocks;
		}

		public static function field( $field, $value ) {

			// Setup a default value if no licenses exist so the first license row is rendered.
			if ( empty( $value ) ) {

				$value = array(
					array(
						'school' => '',
						'degree' => '',
						'year'   => '',
						'honors' => '',
					)
				);
			}

			?>
			<style type="text/css" scoped>
				#cn-degrees thead td {
					vertical-align: bottom;
				}
				i.fa.fa-sort {
					cursor: move;
					padding-bottom: 4px;
					padding-right: 4px;
					vertical-align: middle;
				}
				i.cnd-clearable__clear {
					display: none;
					position: absolute;
					right: 0;
					top: 0;
					font-style: normal;
					user-select: none;
					cursor: pointer;
					font-size: 1.5em;
					padding: 0 8px;
				}
				@media screen and ( max-width: 782px ) {
					i.cnd-clearable__clear {
						font-size: 2.15em;
						/*padding: 7px 8px;*/
					}
				}
			</style>
			<table id="cn-degrees" data-count="<?php echo count( $value ) ?>">

				<thead>
				<tr>
					<td>&nbsp;</td>
					<td><?php _e( 'School', 'connections-degrees' ); ?></td>
					<td><?php _e( 'Degree', 'connections-degrees' ); ?></td>
					<td><?php _e( 'Year Received', 'connections-degrees' ); ?></td>
					<td><?php _e( 'Honors/Awards', 'connections-degrees' ); ?></td>
					<td><?php _e( 'Add / Remove', 'connections-degrees' ); ?></td>
				</tr>
				</thead>

				<tbody>

				<?php foreach ( $value as $degree ) : ?>

					<tr class="widget">
						<td><i class="fa fa-sort"></i></td>
						<td style="max-width: 175px;">
							<?php

							cnTemplatePart::walker(
								'term-select-enhanced',
								array(
									'taxonomy'        => 'school',
									'name'            => $field['id'] . '[0][school]',
									'class'           => array('cn-school-select'),
									'style'           => array( 'min-width' => '150px' ),
									'show_option_all' => '',
									'default'         => __( 'Select School', 'connections-degrees' ),
									'selected'        => cnArray::get( $degree, 'school', 0 ),
								)
							);

							?>
						</td>
						<td style="max-width: 175px;">
							<?php

							cnTemplatePart::walker(
								'term-select-enhanced',
								array(
									'taxonomy'        => 'degree',
									'name'            => $field['id'] . '[0][degree]',
									'class'           => array('cn-degree-select'),
									'style'           => array( 'min-width' => '150px' ),
									'show_option_all' => '',
									'default'         => __( 'Select Degree', 'connections-degrees' ),
									'selected'        => cnArray::get( $degree, 'degree', 0 ),
								)
							);

							?>
						</td>
						<td>
							<?php

							cnHTML::field(
								array(
									'type'     => 'select',
									'class'    => '',
									'id'       => $field['id'] . '[0][year]',
									'required' => false,
									'label'    => '',
									'before'   => '',
									'after'    => '',
									'options'  => self::getYearOptions(),
									'return'   => false,
								),
								cnArray::get( $degree, 'year', NULL )
							);

							?>
						</td>
						<td>
							<span class="cnd-clearable" style="display: inline-block; position: relative">
								<?php

								cnHTML::field(
									array(
										'type'     => 'text',
										'class'    => 'clearable',
										'id'       => $field['id'] . '[0][honors]',
										'style'    => array(
											'box-sizing'    => 'border-box',
											'padding-right' => '24px',
											'width'         => '100%',
										),
										'required' => false,
										'label'    => '',
										'before'   => '',
										'after'    => '',
										'return'   => false,
									),
									cnArray::get( $degree, 'honors', NULL )
								);

								?>
								<i class="cnd-clearable__clear">&times;</i>
							</span>
						</td>
						<td>
							<span class="button disabled cnd-remove-degree">&ndash;</span><span class="button cnd-add-degree">+</span>
						</td>
					</tr>

				<?php endforeach; ?>

				</tbody>
			</table>

			<?php

			// Enqueue the JS required for the metabox.
			wp_enqueue_script( 'cnd-ui-js' );
		}

		/**
		 * Sanitize the times as a text input using the cnSanitize class.
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param array $value
		 *
		 * @return array
		 */
		public static function sanitize( $value ) {

			if ( empty( $value ) ) return $value;

			foreach ( $value as $key => &$degree ) {

				if ( 0 != $degree['school'] || 0 != $degree['degree'] ) {

					$degree['school'] = absint( $degree['school'] );
					$degree['degree'] = absint( $degree['degree'] );
					$degree['year']   = absint( $degree['year'] );
					$degree['honors'] = sanitize_text_field( $degree['honors'] );

				} else {

					unset( $value[ $key ] );
				}
			}

			return $value;
		}

		protected static function getYearOptions() {

			$year  = date( 'Y' );
			$range = range( $year, $year - 100 );
			$years = array_combine( $range, $range );

			return apply_filters( 'cnd_year_options', $years );
		}

		/**
		 * Add, update or delete the entry degrees/schools.
		 *
		 * @access public
		 * @since  1.0
		 * @static
		 *
		 * @param  string $action The action to being performed to an entry.
		 * @param  int    $id     The entry ID.
		 */
		public static function attachDegrees( $action, $id ) {

			// Grab an instance of the Connections object.
			$instance = Connections_Directory();
			$schools  = array();
			$degrees  = array();

			if ( isset( $_POST['degrees'] ) && ! empty( $_POST['degrees'] ) ) {

				foreach ( $_POST['degrees'] as $key => &$item ) {

					if ( 0 != $item['school'] || 0 != $item['school'] ) {

						$schools[] = absint( $item['school'] );
					}

					if ( 0 != $item['degree'] || 0 != $item['degree'] ) {

						$degrees[] = absint( $item['degree'] );
					}
				}

			}

			$instance->term->setTermRelationships( $id, $schools, 'school' );
			$instance->term->setTermRelationships( $id, $degrees, 'degree' );
		}

		/**
		 * The output of the license data.
		 *
		 * Called by the cn_meta_output_field-law_licenses action in cnOutput->getMetaBlock().
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param string  $id    The field id.
		 * @param array   $value The license data.
		 * @param cnEntry $object
		 * @param array   $atts  The shortcode atts array passed from the calling action.
		 */
		public static function block( $id, $value, $object = NULL, $atts ) {
			?>

			<div class="cn-licenses">

				<?php

				foreach ( $value as $key => &$degree ) {

					$paper  = $degree['degree'] ? cnTerm::get( $degree['degree'], 'degree' ) : 0;
					$school = $degree['school'] ? cnTerm::get( $degree['school'], 'school' ) : 0;

					?>

					<ul class="cn-degree">
						<?php if ( $paper ) : ?> <li class="cn-license cn-degree"><span class="cn-label"><?php _e( 'Degree:', 'connections-degrees' ) ?></span> <span class="cn-value"><?php echo esc_html( $paper->name ); ?></span></li><?php endif; ?>
						<?php if ( $school ) : ?><li class="cn-license cn-school"><span class="cn-label"><?php _e( 'School:', 'connections-degrees' ) ?></span> <span class="cn-value"><?php echo esc_html( $school->name ); ?></span></li><?php endif; ?>
						<?php if ( $degree['year'] ) : ?><li class="cn-license cn-year"><span class="cn-label"><?php _e( 'Year:', 'connections-degrees' ) ?></span> <span class="cn-value"><?php echo absint( $degree['year'] ); ?></span></li><?php endif; ?>
						<?php if ( $degree['honors'] ) : ?><li class="cn-license cn-honors"><span class="cn-label"><?php _e( 'Honors:', 'connections-degrees' ) ?></span> <span class="cn-value"><?php echo esc_html( $degree['honors'] ); ?></span></li><?php endif; ?>
					</ul>

					<?php
				}

				?>

			</div>

			<?php
		}
	}

	/**
	 * Start up the extension.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return mixed object | bool
	 */
	function Connections_Degrees() {

			if ( class_exists('connectionsLoad') ) {

					return new Connections_Degrees();

			} else {

				add_action(
					'admin_notices',
					 create_function(
						 '',
						'echo \'<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Degrees.</p></div>\';'
						)
				);

				return FALSE;
			}
	}

	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11 so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Degrees', 11 );

}
