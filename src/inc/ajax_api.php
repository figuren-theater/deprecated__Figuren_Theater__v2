<?php
declare(strict_types=1);

namespace Figuren_Theater\inc;


/**
 * create ajax-api shortcut
 * 
 * Faster AJAX Calls using the rewrite API
 *
 * This class creates a custom rewrite endpoint
 * on the base of
 * 'https://domain.tld/ajax-api/' which returns 
 * the given callback. While this callback could do anything,
 * its primary purpose was to serve JSON as a much faster
 * alternative to the REST API.
 *
 * @example
 * 1. Create a custom endpoint and add a callback
 * ```
 * new ajax_api(
 *   'domain_request',
 *   $domain_name_regex,
 *   __NAMESPACE__.'\\check_domain_request'
 * );
 *
 * @see https://trello.com/c/lMDtOYXD/427-6-faster-ajax-calls-with-the-wordpress-rewrite-api-youtube
 */
class ajax_api 
{
	
	protected $rewrite_tag = '';
	protected $regex = '';
	protected $template_redirect_callback = '';
	protected $rewrite_base = '';
	protected $rewrites_position = '';

	function __construct( String $rewrite_tag, String $regex, Callable $callback, String $rewrite_base = 'ajax-api', String $rewrites_position = 'top' )
	{

		$this->rewrite_tag = str_replace('-', '_', $rewrite_tag );
		$this->rewrite_tag_url = str_replace('_', '-', $this->rewrite_tag );
		$this->regex = $regex;
		$this->template_redirect_callback = $callback;
		$this->rewrite_base = $rewrite_base;
		$this->rewrites_position = $rewrites_position;

		// TODO // move into SubscriberInterface
		\add_action( 'init', [$this,'init'] );
		\add_action( 'template_redirect', [$this,'template_redirect'] );
	}

	public function init()
	{
		$this->add_rewrite_tag();
		$this->add_rewrite_rule();
// error_log('init new ajax_api(): ');
// error_log(var_export($this,true));

		\add_filter( 'query_vars', [$this,'whitelist_query_var'] );
		// do_action( 'qm/debug', $this->template_redirect_callback );

	}

	protected function add_rewrite_tag()
	{
		\add_rewrite_tag( '%'.$this->rewrite_tag.'%', $this->regex );
	}

	protected function add_rewrite_rule()
	{
		\add_rewrite_rule( 
			$this->rewrite_base.'/'.$this->rewrite_tag_url.'/'. $this->regex .'/?',
			'index.php?'.$this->rewrite_tag.'=$matches[1]',
			$this->rewrites_position
		);
	}
	public function whitelist_query_var( $query_vars )
	{
		$query_vars[] = $this->rewrite_tag;
		return $query_vars;
	}
	public function template_redirect() {
		global $wp_query;

		$query_var = $wp_query->get( $this->rewrite_tag );

		if ( !empty($query_var)) {

			call_user_func( $this->template_redirect_callback, $query_var );
			
		}
	}
}

