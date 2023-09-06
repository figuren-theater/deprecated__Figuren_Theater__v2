<?php
declare(strict_types=1);

namespace Figuren_Theater\inc\EventManager;


/**
 * The WordPress event manager manages events using the WordPress plugin API.
 *
 * 100% based on this excellent blog post and the related gist 
 * https://carlalexander.ca/designing-system-wordpress-event-management/
 * https://gist.github.com/carlalexander/d439be349fffe01cd381
 *
 * @author Carl Alexander <contact@carlalexander.ca>
 */
class EventManager
{
	/**
	 * The WordPress plugin API manager.
	 *
	 * @var PluginAPIManager
	 */
	private $plugin_api_manager;

	/**
	 * Constructor.
	 *
	 * @param PluginAPIManager $plugin_api_manager
	 */
	public function __construct(PluginAPIManager $plugin_api_manager)
	{
		$this->plugin_api_manager = $plugin_api_manager;
	}

	/**
	 * Adds the given event listener to the list of event listeners
	 * that listen to the given event.
	 *
	 * @param string   $event_name
	 * @param callable $listener
	 * @param int      $priority
	 * @param int      $accepted_args
	 */
	public function add_listener($event_name, $listener, $priority = 10, $accepted_args = 1)
	{
		$this->plugin_api_manager->add_callback($event_name, $listener, $priority, $accepted_args);
	}

	/**
	 * Adds an event subscriber.
	 *
	 * The event manager adds the given subscriber to the list of event listeners 
	 * for all the events that it wants to listen to.
	 *
	 * @param SubscriberInterface $subscriber
	 */
	public function add_subscriber(SubscriberInterface $subscriber)
	{
		if ($subscriber instanceof PluginAPIManagerAwareSubscriberInterface) {
			$subscriber->set_plugin_api_manager($this->plugin_api_manager);
		}
	  
		foreach ($subscriber->get_subscribed_events() as $event_name => $parameters) {
			$this->add_subscriber_listener($subscriber, $event_name, $parameters);
		}
	}

	/**
	 * Removes the given event listener from the list of event listeners
	 * that listen to the given event.
	 *
	 * @param string   $event_name
	 * @param callable $listener
	 * @param int      $priority
	 */
	public function remove_listener($event_name, $listener, $priority = 10)
	{
		$this->plugin_api_manager->remove_callback($event_name, $listener, $priority);
	}

	/**
	 * Removes an event subscriber.
	 *
	 * The event manager removes the given subscriber from the list of event listeners 
	 * for all the events that it wants to listen to.
	 *
	 * @param SubscriberInterface $subscriber
	 */
	public function remove_subscriber(RemoveSubscriberInterface $subscriber)
	{
		foreach ($subscriber->remove_subscribed_events() as $event_name => $parameters) {
			$this->remove_subscriber_listener($subscriber, $event_name, $parameters);
		}
	}

	/**
	 * Adds the given subscriber listener to the list of event listeners
	 * that listen to the given event.
	 *
	 * @param SubscriberInterface $subscriber
	 * @param string              $event_name
	 * @param mixed               $parameters
	 */
	private function add_subscriber_listener(SubscriberInterface $subscriber, $event_name, $parameters)
	{
		if (is_string($parameters)) {
			$this->add_listener($event_name, array($subscriber, $parameters));
		} elseif (is_array($parameters) && isset($parameters[0])) {
			$this->add_listener($event_name, array($subscriber, $parameters[0]), isset($parameters[1]) ? $parameters[1] : 10, isset($parameters[2]) ? $parameters[2] : 1);
		}
	}

	/**
	 * Adds the given subscriber listener to the list of event listeners
	 * that listen to the given event.
	 *
	 * @param SubscriberInterface $subscriber
	 * @param string              $event_name
	 * @param mixed               $parameters
	 */
	private function remove_subscriber_listener(RemoveSubscriberInterface $subscriber, $event_name, $parameters)
	{
		if (is_string($parameters)) {
			$this->remove_listener($event_name, array($subscriber, $parameters));
		} elseif (is_array($parameters) && isset($parameters[0])) {
			$this->remove_listener($event_name, array($subscriber, $parameters[0]), isset($parameters[1]) ? $parameters[1] : 10);
		}
	}
}


\add_action( 
	'Figuren_Theater\init', 
	function ( $ft_site ) : void {

		if ( ! is_a( $ft_site, 'Figuren_Theater\ProxiedSite' ))
			return;

		// 0.1
		$PluginAPIManager = new PluginAPIManager();
		$event_manager = new EventManager( $PluginAPIManager );
	
		$ft_site->set_EventManager( $event_manager );


	},
	0
);

