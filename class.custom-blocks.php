<?php

namespace CustomBlocks2024;

use WP_Admin_Bar;
use WP_Error;
use WP_REST_Request;

class CUSTOM_Blocks {
  
    public function __construct() {
        add_filter( 'admin_bar_menu', [$this, 'addDebugTool'], 800 );
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue'] );
        add_action( 'admin_footer', [$this, 'adminFooterScript'] );
        add_action( 'wp_ajax_edit_block_form', [$this, 'ajaxEditBlockForm'] );
        add_action( 'wp_ajax_edit_block', [$this, 'ajaxEditBlock'] );
    }

    public function enqueue() {
        wp_enqueue_style( 'custom-blocks', plugin_dir_url(__FILE__) . 'css/custom-blocks.min.css' );
        wp_enqueue_style( 'bootstrap-css-min', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css' );
        wp_enqueue_script( 'bootstrap-js-min', plugin_dir_url(__FILE__) . 'js/bootstrap.bundle.min.js', [ 'jquery' ], false, true );
        wp_enqueue_script( 'jquery-cookie', plugin_dir_url(__FILE__) . 'js/jquery.cookie.js', [ 'jquery' ], false, true );
        wp_enqueue_script( 'custom-blocks', plugin_dir_url(__FILE__) . 'js/custom-blocks.js', [ 'jquery-cookie' ], false, true );

        if ( is_user_logged_in() && isset( $_COOKIE['block_debug'] ) ) {
            wp_enqueue_media();
            wp_enqueue_editor();
            wp_enqueue_script( 'utils' );
            wp_enqueue_script( 'tinymce_js', includes_url( 'js/tinymce/' ) . 'wp-tinymce.php', [ 'jquery' ], false, true);
            wp_localize_script( 'custom-blocks', 'customBlocks', [
              'ajaxurl' => admin_url( 'admin-ajax.php' ),
              'nonce' => wp_create_nonce( 'edit_block_nonce' ),
          ]);
        }
    }
    
    public function addDebugTool( WP_Admin_Bar $wp_admin_bar ) {
        if ( !is_admin() && is_admin_bar_showing() && current_user_can( 'edit_others_posts' ) ) {
            $wp_admin_bar->add_menu([
                'id'    => 'custom_block_debug',
                'title' => __('Edit Blocks', 'custom-blocks-2024')
            ]);

            $wp_admin_bar->add_node([
                'parent' => 'user-actions',
                'id'     => 'custom_block_debug_two',
                'title'  => __('Edit Blocks', 'custom-blocks-2024'),
                'href'   => '#',
            ]);
        }
    }

    public function block( $block_name, $wpautop = false, $echo = true, array $args = [] ) {
        $out = '';
        $post = get_page_by_path( sanitize_text_field( $block_name ), OBJECT, 'block' );

        // if the post exists, get the content
        if ( is_object( $post ) ) {
            $content = $wpautop ? wpautop( $post->post_content ) : $post->post_content;
            $out = html_entity_decode( $content );

            // add the edit link if the user is logged in and has the edit_blocks capability
            if ( empty( $args['no_edit_link']) && current_user_can( 'edit_blocks' ) ) {
                $out = '<div data-block-content="' . esc_attr($post->ID) . '">' . $out . '</div>';

                if ( isset( $_COOKIE['block_debug'] ) ) {
                    $edit_link = get_edit_post_link($post->ID);
                    $out .= '<a href="' . esc_url($edit_link) . '" target="_blank" class="edit_block btn btn-secondary rounded-pill px-3" data-edit-type="block" data-post-id="' . esc_attr($post->ID) . '">Edit Block</a>';
                }
            }
        } else {
          // if the post doesn't exist, show option to create a new block
            $out = $this->blockMissing( $block_name );
        }

        if ( $echo ) {
            echo $out;
        } else {
            return $out;
        }
    }
  
    private function blockMissing( $block_name ) {
        $title = ucwords( str_replace( '-', ' ', sanitize_text_field( $block_name ) ) );
        return sprintf(
            '<span style="font-size: .7em;">Looking For Block "%s"<br><a href="%s" target="_blank" class="edit_block btn btn-secondary rounded-pill px-3">Create New Block</a></span>',
            esc_html( $title ),
            esc_url( admin_url( 'post-new.php?post_type=block&block_title=' . urlencode( $title ) ) )
        );
    }

    public function blockTitle( $block_name, $echo = true, array $args = [] ) {
        $out = '';
        $post = get_page_by_path( $block_name, OBJECT, 'block' );

        if ( is_object( $post ) ) {
            // get the block title from the post meta
            $title = get_post_meta( $post->ID, 'block_title', true );
            $out .= wp_kses_post( $title );

            // add the edit link if the user is logged in and has the edit_blocks capability
            if ( current_user_can( 'edit_blocks' ) && isset( $_COOKIE['block_debug'] ) && empty( $args['no_edit_link'] ) ) {
                $out = '<div data-block-title="' . esc_attr( $post->ID ) . '">' . $out . '</div>';
                $edit_link = get_edit_post_link( $post->ID );
                $out .= '<a href="' . esc_url( $edit_link ) . '" target="_blank" class="edit_block btn btn-secondary rounded-pill px-3" data-edit-type="title" data-post-id="' . esc_attr( $post->ID ) . '">Edit Title</a>';
            }
        }

        if ( $echo ) {
            echo $out;
        } else {
            return $out;
        }
    }

    public function blockHref( $block_name, $fallback = false, $echo = true, array $args = [] ) {
        $out = '';
        $post = get_page_by_path( $block_name, OBJECT, 'block' );

        if ( is_object( $post ) ) {
            $out = get_post_meta( $post->ID, 'block_href', true );
        }

        // use the fallback URL if no post meta is found
        if ( !$out ) {
            $out = $fallback;
        }

        if ( $echo ) {
            echo esc_url( $out );
        } else {
            return esc_url( $out );
        }
    }

    public function blockImage( $block_name, $fallback = false, $echo = false, array $args = [] ) {
        $args = wp_parse_args( $args, ['thumbnail_size' => 'full'] );
        $post = get_page_by_path( $block_name, OBJECT, 'block' );

        $out = is_object( $post ) ? get_the_post_thumbnail_url( $post->ID, $args['thumbnail_size'] ) : '';

        // use the fallback image URL if no image is found
        if ( !$out ) {
            $out = $fallback;
        }

        if ( $echo ) {
            echo esc_url( $out );
        } else {
            return esc_url( $out );
        }
    }
    
    public function adminFooterScript() {
        // include the admin footer script template
        include_once 'admin-footer-script.phtml';
    }

    public function ajaxEditBlockForm() {
        // security check
        if ( !check_ajax_referer( 'edit_block_nonce', 'security', false ) ) {
            wp_send_json_error( 'Nonce verification failed.' );
        }

        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
        $edit_type = isset( $_GET['edit_type'] ) && in_array( $_GET['edit_type'], ['block', 'title'] ) ? $_GET['edit_type'] : 'block';
        $post = get_post($post_id);

        if ( !$post ) {
            wp_die('Post not found.');
        }
      
        // get the post meta
        $block_title = get_post_meta( $post->ID, 'block_title', true );
        $block_href = get_post_meta( $post->ID, 'block_href', true );
        $block_thumbnail_id = get_post_thumbnail_id( $post->ID );
        $block_thumbnail_url = $block_thumbnail_id ? get_the_post_thumbnail_url( $post->ID ) : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';
        $block_thumbnail_height = $block_thumbnail_id ? 'auto' : '20px';
      
        // include the ajax form template
        include dirname( __FILE__ ) . '/ajax-form.php';
        wp_die();
    }

    public function ajaxEditBlock() {
        // security check
        if ( !check_ajax_referer( 'edit_block_nonce', 'security', false ) ) {
            wp_send_json_error( 'Nonce verification failed.' );
        }

        if ( !isset( $_POST['form'] ) ) {
            wp_send_json_error( 'Form data is missing.' );
        }

        // parse the form data
        parse_str( $_POST['form'], $params );

        // init variables / set defaults
        $post_id = isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0;
        $edit_type = isset( $params['edit_type'] ) && in_array( $params['edit_type'], ['block', 'title'] ) ? $params['edit_type'] : 'block';
        $block_title = isset( $params['block_title'] ) ? sanitize_text_field( $params['block_title'] ) : '';
        $block_content = isset( $params['block_content'] ) ? wp_kses_post( $params['block_content'] ) : '';
        $block_image_id = isset( $params['block_image_id'] ) ? intval( $params['block_image_id'] ) : 0;
        $block_href = isset( $params['block_href'] ) ? esc_url_raw( $params['block_href'] ) : '';

        // ensure the user has perms
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( 'Unauthorized action.' );
        }

        // update post meta and thumbnail
        update_post_meta( $post_id, 'block_title', $block_title );
        update_post_meta( $post_id, 'block_href', $block_href );

        if ( $block_image_id ) {
            set_post_thumbnail( $post_id, $block_image_id );
        } else {
            delete_post_thumbnail( $post_id );
        }

        // update the post content
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => $block_content,
        ] );

        // prepare and send the response
        wp_send_json_success( [
            'post_id'       => $post_id,
            'edit_type'     => $edit_type,
            'block_title'   => $block_title,
            'block_content' => $block_content,
        ] );
    }
}
