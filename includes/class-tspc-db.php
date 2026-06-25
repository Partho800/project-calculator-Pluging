<?php
/**
 * Database Handler for TSPC Plugin
 *
 * @package TSPC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSPC_DB {

	/**
	 * Table names
	 */
	private static $services_table = 'tspc_services';
	private static $inquiries_table = 'tspc_inquiries';

	/**
	 * Get full table name with prefix
	 */
	public static function get_services_table() {
		global $wpdb;
		return $wpdb->prefix . self::$services_table;
	}

	public static function get_inquiries_table() {
		global $wpdb;
		return $wpdb->prefix . self::$inquiries_table;
	}

	/**
	 * Create tables
	 */
	public static function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$services_table = self::get_services_table();
		$inquiries_table = self::get_inquiries_table();

		// Services Schema
		$sql_services = "CREATE TABLE $services_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text DEFAULT '',
			icon varchar(100) DEFAULT 'dashicons-admin-site',
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'enabled',
			sub_services text DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Inquiries Schema
		$sql_inquiries = "CREATE TABLE $inquiries_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(30) DEFAULT '',
			services text NOT NULL,
			subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
			discount decimal(10,2) NOT NULL DEFAULT 0.00,
			total decimal(10,2) NOT NULL DEFAULT 0.00,
			message text DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'new',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_services );
		dbDelta( $sql_inquiries );
	}

	/**
	 * Services CRUD
	 */
	public static function insert_service( $data ) {
		global $wpdb;
		$table = self::get_services_table();
		$format = array( '%s', '%s', '%s', '%f', '%s', '%s' );
		$inserted = $wpdb->insert( $table, $data, $format );
		return $inserted ? $wpdb->insert_id : false;
	}

	public static function update_service( $id, $data ) {
		global $wpdb;
		$table = self::get_services_table();
		$format = array( '%s', '%s', '%s', '%f', '%s', '%s' );
		return $wpdb->update( $table, $data, array( 'id' => $id ), $format, array( '%d' ) ) !== false;
	}

	public static function delete_service( $id ) {
		global $wpdb;
		$table = self::get_services_table();
		return $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ) !== false;
	}

	public static function get_service( $id ) {
		global $wpdb;
		$table = self::get_services_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
	}

	public static function get_services( $limit = 100, $offset = 0, $only_enabled = false ) {
		global $wpdb;
		$table = self::get_services_table();
		$where = $only_enabled ? "WHERE status = 'enabled'" : "";
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);
	}

	public static function get_services_count( $only_enabled = false ) {
		global $wpdb;
		$table = self::get_services_table();
		$where = $only_enabled ? "WHERE status = 'enabled'" : "";
		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table $where" );
	}

	/**
	 * Inquiries CRUD
	 */
	public static function insert_inquiry( $data ) {
		global $wpdb;
		$table = self::get_inquiries_table();
		// name, email, phone, services, subtotal, discount, total, message, status
		$format = array( '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s' );
		$inserted = $wpdb->insert( $table, $data, $format );
		return $inserted ? $wpdb->insert_id : false;
	}

	public static function get_inquiries( $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table = self::get_inquiries_table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);
	}

	public static function get_inquiries_count() {
		global $wpdb;
		$table = self::get_inquiries_table();
		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table" );
	}

	public static function delete_inquiry( $id ) {
		global $wpdb;
		$table = self::get_inquiries_table();
		return $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ) !== false;
	}

	public static function update_inquiry_status( $id, $status ) {
		global $wpdb;
		$table = self::get_inquiries_table();
		return $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		) !== false;
	}
}
