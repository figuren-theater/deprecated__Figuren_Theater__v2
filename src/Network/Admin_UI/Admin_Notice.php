<?php
declare(strict_types=1);

namespace Figuren_Theater\Network\Admin_UI;


/**
 * Thic Class holds a typicall WP Admin-Notice
 * without adding it to any of the (user|network|all)_'admin_notices' hook
 * nothing will happen.
 *
 * Typicall we use this as part of a new 'Rule__will_add_admin_notice',
 * which will then be added to the 'AdminUIManager' to be rendered. * 
 */
class Admin_Notice {

    /**
     * Text with minimal semantic styling 
     * like <em>, <b>, <s>
     * 
     * @var String
     */
    public $message;

    /**
     * Notice Style CSS class
     * According to WP default styles, this should be at least one of:
     * 
     * - is-dismissible
     * - info
     * - updated (default)
     * - warning
     * - error
     * 
     * @var string
     */
    public $class; 

    function __construct( String $message, String $class = 'updated' ){
        $this->class = $class;
        $this->message = $message;
    }

    function output() : string {
        return '<div class="notice ' . $this->class .'"><p>' . $this->message . '</p></div>';
    }
}
