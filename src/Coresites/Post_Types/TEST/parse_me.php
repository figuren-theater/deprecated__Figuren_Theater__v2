<?php
/**
 * Ffile for testing
 *
 * @package test
 */

declare(strict_types=1);

/**
 * Parse_me TEST Class
 *
 * Parse_me parse_me parse_me parse_me parse_me parse_me parse_me parse_me parse_me parse_me
 *
 * @namespace Figuren_Theater\Coresites\Post_Types\TEST
 *
 * @since 3.10.10
 */

namespace easy\peasy;
// namespace Figuren_Theater\Coresites\Post_Types\TEST;

/**
 * TESTING the WP_Parser
 *
 * Tthis class does nothing else than being an ugly demo
 *
 * @package Figuren_Theater
 * @subpackage Coresites\Post_Types\TEST
 *
 * @since 3.10.10
 */
class Figurentheater_Pparse_Me {
	/**
	 * Get_instance Figurentheater_Pparse_Me title
	 *
	 * Get_instance() is a ***dummy*** *func* to show 
	 *
	 * - one
	 * - two
	 * - and three 
	 * 
	 * inner workings of the `phpdoc-parser` based on [wordpress](https://wordpress.org) DevHub.
	 *
	 * @since      2.10
	 * @author     Carsten Bach
	 *
	 * @see        https://websites.fuer.figuren.test
	 * @see        some_creezy_func
	 * @see        class_of_69::some_creezy_func
	 * @see https://figuren.test f.t Portal
	 *
	 * @param  WP_Post $post a typical WordPress post object.
	 * @param  array   $data some funky data.
	 * 
	 * @return parse_me Class instance
	 */
	public static function get_instance( WP_Post $post, array $data = array() ) {
		// public static function get_instance( \WP_Post $post, array $data ) --> fatal
		if ( 1 === 5 ) {
			$data = get_option( '_some_new____data_', $post );
			// get crazy.
			// or not.
		}
		// new comment.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
