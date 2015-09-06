<?php
/**
 * Represents release objects.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use IronBound\Cache\Cache;
use IronBound\DB\Model;
use IronBound\DB\Table\Table;
use IronBound\DB\Manager;
use IronBound\DB\Exception as DB_Exception;

/**
 * Class Release
 * @package ITELIC
 */
class Release extends Model {

	/**
	 * Major releases. 1.5 -> 1.6
	 */
	const TYPE_MAJOR = 'major';

	/**
	 * Minor, bug fixing releases. 1.5.3 -> 1.5.4
	 */
	const TYPE_MINOR = 'minor';

	/**
	 * Security releases. Follows minor release version number syntax.
	 */
	const TYPE_SECURITY = 'security';

	/**
	 * Pre-releases. Alpha, beta, etc...
	 */
	const TYPE_PRERELEASE = 'pre-release';

	/**
	 * Restricted releases. Only distributed to a subset of customers.
	 */
	const TYPE_RESTRICTED = 'restricted';

	/**
	 * Draft status. Default. Not yet available.
	 */
	const STATUS_DRAFT = 'draft';

	/**
	 * Active releases.
	 */
	const STATUS_ACTIVE = 'active';

	/**
	 * Partially complete releases.
	 */
	const STATUS_PARTIAL = 'partial';

	/**
	 * Complete releases where everyone has been upgraded.
	 */
	const STATUS_COMPLETE = 'complete';

	/**
	 * @var int
	 */
	private $ID;

	/**
	 * @var \IT_Exchange_Product
	 */
	private $product;

	/**
	 * @var int
	 */
	private $download;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $changelog;

	/**
	 * @var \DateTime|null
	 */
	private $start_date;

	/**
	 * Constructor.
	 *
	 * @param \stdClass $data
	 */
	public function __construct( \stdClass $data ) {
		$this->init( $data );
	}

	/**
	 * Init an object.
	 *
	 * @since 1.0
	 *
	 * @param \stdClass $data
	 */
	protected function init( \stdClass $data ) {
		$this->ID      = $data->ID;
		$this->product = it_exchange_get_product( $data->product );

		if ( ! $this->product ) {
			throw new \InvalidArgumentException( "Invalid product." );
		}

		$this->download = (int) $data->download;
		$this->version  = $data->version;

		if ( array_key_exists( $data->status, self::get_statuses() ) ) {
			$this->status = $data->status;
		} else {
			throw new \InvalidArgumentException( "Invalid status." );
		}

		if ( array_key_exists( $data->type, self::get_types() ) ) {
			$this->type = $data->type;
		} else {
			throw new \InvalidArgumentException( "Invalid type." );
		}

		$this->changelog = $data->changelog;

		if ( $data->start_date && $data->start_date != '0000-00-00 00:00:00' ) {
			$this->start_date = new \DateTime( $data->start_date );
		}
	}

	/**
	 * Retrieve a release by its ID.
	 *
	 * @since 1.0
	 *
	 * @param int $id
	 *
	 * @return Release|null
	 */
	public static function with_id( $id ) {
		return self::get( $id );
	}

	/**
	 * Create a new release record.
	 *
	 * If status is set to active, the start date will automatically be set to now.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Product $product
	 * @param int                  $download
	 * @param string               $version
	 * @param string               $type
	 * @param string               $status
	 * @param string               $changelog
	 *
	 * @return Release|null
	 * @throws DB_Exception
	 */
	public static function create( \IT_Exchange_Product $product, $download, $version, $type, $status = '', $changelog = '' ) {

		if ( empty( $status ) ) {
			$status = self::STATUS_DRAFT;
		}

		if ( ! array_key_exists( $status, self::get_statuses() ) ) {
			throw new \InvalidArgumentException( "Invalid status." );
		}

		if ( ! array_key_exists( $type, self::get_types() ) ) {
			throw new \InvalidArgumentException( "Invalid type." );
		}

		if ( get_post_type( $download ) != 'it_exchange_download' ) {
			throw new \InvalidArgumentException( "Invalid download ID." );
		}

		if ( ! it_exchange_product_has_feature( $product->ID, 'licensing' ) ) {
			throw new \InvalidArgumentException( "Product given does not have the licensing feature enabled." );
		}

		$data = array(
			'product'   => $product->ID,
			'download'  => $download,
			'version'   => $version,
			'type'      => $type,
			'status'    => $status,
			'changelog' => wp_kses_post( $changelog )
		);

		if ( $status == self::STATUS_ACTIVE ) {
			$data['start_date'] = current_time( 'mysql' );
		}

		$db = Manager::make_simple_query_object( 'itelic-releases' );
		$ID = $db->insert( $data );

		$release = self::get( $ID );

		if ( $release ) {
			Cache::add( $release );
		}

		return $release;
	}

	/**
	 * Convert an attachment in the media library into an Exchange download for a product.
	 *
	 * @since 1.0
	 *
	 * @param \WP_Post             $attachment
	 * @param \IT_Exchange_Product $product
	 *
	 * @return \WP_Post
	 */
	public static function convert_attachment_to_download( \WP_Post $attachment, \IT_Exchange_Product $product ) {


		$download_id = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'update-file' ) );

		$download_data           = get_post_meta( $download_id, '_it-exchange-download-info', true );
		$download_data['source'] = wp_get_attachment_url( $attachment->ID );
		update_post_meta( $download_id, '_it-exchange-download-info', $download_data );

		return get_post( $download_id );
	}

	/**
	 * Get the unique pk for this record.
	 *
	 * @since 1.0
	 *
	 * @return mixed (generally int, but not necessarily).
	 */
	public function get_pk() {
		return $this->ID;
	}

	/**
	 * Retrieve the ID of this release.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_ID() {
		return $this->get_pk();
	}

	/**
	 * Get the product this release corresponds to.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * Get the ID of the download.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_download() {
		return $this->download;
	}

	/**
	 * Change the download this release corresponds to.
	 *
	 * @since 1.0
	 *
	 * @param int $download
	 */
	public function set_download( $download ) {

		if ( get_post( $download ) != 'it_exchange_download' ) {
			throw new \InvalidArgumentException( "Invalid post type for download." );
		}

		$this->download = $download;

		$this->update( 'download', $download );
	}

	/**
	 * Get the version of this release.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Change the version this release corresponds to.
	 *
	 * @since 1.0
	 *
	 * @param string $version
	 */
	public function set_version( $version ) {
		$this->version = $version;

		$this->update( 'version', $version );
	}

	/**
	 * Get the status of this Release.
	 *
	 * @since 1.0
	 *
	 * @param bool $label
	 *
	 * @return string
	 */
	public function get_status( $label = false ) {

		if ( $label ) {
			$labels = self::get_statuses();

			return $labels[ $this->status ];
		}

		return $this->status;
	}

	/**
	 * Set the status of this release.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 */
	public function set_status( $status ) {

		if ( ! array_key_exists( $status, self::get_statuses() ) ) {
			throw new \InvalidArgumentException( "Invalid status." );
		}

		$this->status = $status;
		$this->update( 'status', $status );
	}

	/**
	 * Get the type of this release.
	 *
	 * @since 1.0
	 *
	 * @param bool $label
	 *
	 * @return string
	 */
	public function get_type( $label = false ) {

		if ( $label ) {
			$labels = self::get_types( true );

			return $labels[ $this->type ];
		}

		return $this->type;
	}

	/**
	 * Set the type of this release.
	 *
	 * @since 1.0
	 *
	 * @param string $type
	 */
	public function set_type( $type ) {

		if ( ! array_key_exists( $type, self::get_types() ) ) {
			throw new \InvalidArgumentException( "Invalid type." );
		}

		$this->type = $type;
		$this->update( 'type', $type );
	}

	/**
	 * Get the changelog.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_changelog() {
		return $this->changelog;
	}

	/**
	 * Set the changelog for this release.
	 *
	 * @since 1.0
	 *
	 * @param string $changelog
	 * @param string $mode If replace, replaces changelog. If append, appends to changelog. Default replace.
	 */
	public function set_changelog( $changelog, $mode = 'replace' ) {

		if ( $mode == 'append' ) {
			$this->changelog .= $changelog;
		} else {
			$this->changelog = $changelog;
		}

		$this->update( 'changelog', $this->changelog );
	}

	/**
	 * Get the date when this release started.
	 *
	 * @since 1.0
	 *
	 * @return \DateTime|null
	 */
	public function get_start_date() {
		return $this->start_date;
	}

	/**
	 * Set the start date.
	 *
	 * @since 1.0
	 *
	 * @param \DateTime|null $start_date
	 */
	public function set_start_date( \DateTime $start_date = null ) {
		$this->start_date = $start_date;

		if ( $start_date ) {
			$val = $start_date->format( "Y-m-d H:i:s" );
		} else {
			$val = null;
		}

		$this->update( 'start_date', $val );
	}

	/**
	 * Get a list of the various statuses.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			self::STATUS_DRAFT    => __( "Draft", Plugin::SLUG ),
			self::STATUS_ACTIVE   => __( "Active", Plugin::SLUG ),
			self::STATUS_PARTIAL  => __( "Partial", Plugin::SLUG ),
			self::STATUS_COMPLETE => __( "Complete", Plugin::SLUG )
		);
	}

	/**
	 * Get a list of the various types of releases.
	 *
	 * @since 1.0
	 *
	 * @param bool $short
	 *
	 * @return array
	 */
	public static function get_types( $short = false ) {

		if ( $short ) {
			return array(
				self::TYPE_MAJOR      => __( "Major", Plugin::SLUG ),
				self::TYPE_MINOR      => __( "Minor", Plugin::SLUG ),
				self::TYPE_SECURITY   => __( "Security", Plugin::SLUG ),
				self::TYPE_PRERELEASE => __( "Pre-release", Plugin::SLUG ),
				self::TYPE_RESTRICTED => __( "Restricted", Plugin::SLUG )
			);
		}

		return array(
			self::TYPE_MAJOR      => __( "Major Release", Plugin::SLUG ),
			self::TYPE_MINOR      => __( "Minor Release", Plugin::SLUG ),
			self::TYPE_SECURITY   => __( "Security Release", Plugin::SLUG ),
			self::TYPE_PRERELEASE => __( "Pre-release", Plugin::SLUG ),
			self::TYPE_RESTRICTED => __( "Restricted Release", Plugin::SLUG )
		);
	}

	/**
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @returns Table
	 */
	protected static function get_table() {
		return Manager::get( 'itelic-releases' );
	}
}