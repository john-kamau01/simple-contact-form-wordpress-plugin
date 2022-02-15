<?php
/**
 * Plugin Name:       Simple Contact Form
 * Plugin URI:        https://github.com/john-kamau01
 * Description:       Simple Contact Form Plugin
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            John Kamau
 * Author URI:        https://github.com/john-kamau01
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/john-kamau01
 * Text Domain:       simple-contact-form
 * Domain Path:       /languages
 */

if(!defined('ABSPATH')){
    echo 'What are you trying to do';
    exit;
}

class SimpleContactForm{
    public function __construct(){
        wp_enqueue_script('jquery');
        //Create custom post type
        add_action('init', array($this, 'create_custom_post_type'));

        //Add Assets(JS, CSS, etc)
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));

        //Add Shortcode
        add_shortcode( 'contact-form', array($this, 'load_short_code') );

        //Add Javascript
        add_action('wp_footer', array($this, 'load_scripts'));

        //Register REST API
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    public function create_custom_post_type(){
        $args = array(
            'public' => true,
            'has_archive' => true,
            'supports' => array('title'),
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
            'labels' => array(
                'name' => 'Contact Form',
                'singular_name' => 'Contact Form Entry'
            ),
            'menu_icon' => 'dashicons-id',
        );

        register_post_type('simple_contact_form', $args);
    }

    public function load_assets(){
        wp_enqueue_style( 
            'simple-contact-form',
            plugin_dir_url( __FILE__ ) . 'css/simple-contact-form.css', 
            array(),
            1,
            'all'
        );

        wp_enqueue_script( 
            'simple-contact-form', 
            plugin_dir_url( __FILE__ ) . 'js/simple-contact-form.js', 
            array(), 1, true );
    }

    public function load_short_code(){
    ?>
        <div class="simple-contact-form">
            <h1>Send us a message</h1>
            <p>Please fill in the form below</p>
            <form id="simple-contact-form__form">
                <div class="form-group">
                    <input name="name" type="text" placeholder="Enter your name">
                </div>

                <div class="form-group">
                    <input name="email" type="email" placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <input name="phone" type="tel" placeholder="Enter your phone number">
                </div>

                <div class="form-group">
                    <textarea name="message" placeholder="Write a message"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit">Submit</button>
                </div>
                
            </form>
        </div>


    <?php
    }


    public function load_scripts(){
    ?>
        <script>
            var nonce = '<?php echo wp_create_nonce( 'wp_rest' );?>';

            jQuery(function($){
                $('#simple-contact-form__form').submit(function(e){
                    e.preventDefault();

                    var form = $(this).serialize();

                    $.ajax({
                        method: 'post',
                        url: '<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email'); ?>',
                        headers: { 'X-WP-Nonce': nonce },
                        data: form
                    })
                })
            })
        </script>
    <?php
    }

    public function register_rest_api(){
        register_rest_route( 'simple-contact-form/v1', 'send-email', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_contact_form')
        ));
    }

    public function handle_contact_form($data){
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers['x_wp_nonce'][0];
        //$nonce = 12344;

        if(!wp_verify_nonce( $nonce, 'wp_rest' )){
            return new WP_REST_Response('Message not sent', 422);
        }

        $post_id = wp_insert_post([
            'post_type' => 'simple_contact_form',
            'post_title' => 'Contact Enquiry',
            'post_status' => 'publish'
        ]);

        if($post_id){
            return new WP_REST_Response('Thank you for your message', 200);
        }
    }
}

//Instantiate the class
new SimpleContactForm;