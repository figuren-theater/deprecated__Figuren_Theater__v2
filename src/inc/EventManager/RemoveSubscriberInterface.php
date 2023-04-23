<?php
declare(strict_types=1);

namespace Figuren_Theater\inc\EventManager;


/**
 * A RemoveSubscriber knows what specific WordPress events it wants to unsubscribe to.
 * 
 * When an EventManager adds a RemoveSubscriber, it gets all the WordPress events that 
 * it wants to unsubscribe from.
 *
 * @author Carl Alexander <contact@carlalexander.ca>
 * @author Carsten Bach
 */
interface RemoveSubscriberInterface
{
    /**
     * Returns an array of events that this subscriber wants to unsubscribe from
     *
     * The array key is the event name. The value can be:
     *
     *  * The method name
     *  * An array with the method name and priority
     *  * An array with the method name, priority and number of accepted arguments
     *
     * For instance:
     *
     *  * array('event_name' => 'method_name')
     *  * array('event_name' => array('method_name', $priority))
     *  * array('event_name' => array('method_name', $priority, $accepted_args))
     *
     * @return array
     */
    public static function remove_subscribed_events() : array;
}
