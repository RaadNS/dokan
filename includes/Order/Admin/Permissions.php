<?php

namespace WeDevs\Dokan\Order\Admin;

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle Admin Order Permission Related Hooks
 *
 * @since DOKAN_SINCE
 */
class Permissions {
    /**
     * Class constructor
     *
     * @since DOKAN_SINCE
     */
    public function __construct() {
        add_filter( 'map_meta_cap', [ $this, 'map_meta_caps' ], 12, 4 );

        add_filter( 'posts_clauses', [ $this, 'filter_orders_for_current_vendor' ], 12, 2 );
        add_action( 'load-post.php', [ $this, 'revoke_change_order_status' ] );
        add_filter( 'manage_edit-shop_order_columns', [ $this, 'remove_action_column' ], 15 );
        add_filter( 'woocommerce_admin_order_preview_actions', [ $this, 'remove_action_button' ], 15 );
    }

    /**
     * Dokan map meta cpas for vendors
     *
     * @since DOKAN_SINCE moved this method from includes/functions.php file
     * @since DOKAN_SINCE Added HPOS support
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $user_id
     * @param array  $args
     *
     * @return array
     */
    public function map_meta_caps( $caps, $cap, $user_id, $args ) {
        global $post;

        if ( ! is_admin() ) {
            return $caps;
        }

        if ( $cap === 'edit_post' || $cap === 'edit_others_shop_orders' ) {
            $post_id = ! empty( $args[0] ) ? $args[0] : ( is_object( $post ) ? $post->ID : 0 );
            if ( empty( $post_id ) ) {
                return $caps;
            }

            $order = wc_get_order( $post_id );
            if ( ! $order ) {
                return $caps;
            }

            $vendor_id = $order->get_meta( '_dokan_vendor_id', true );
            if ( (int) $vendor_id === (int) $user_id ) {
                return [ 'edit_shop_orders' ];
            }
        }

        return $caps;
    }

    /**
     * Filter orders of current user
     *
     * @since 2.9.4
     * @since DOKAN_SINCE Moved this method from includes/functions.php
     * @since DOKAN_SINCE Added HPOS Support
     *
     * @param array  $args
     * @param object $query
     *
     * @return array
     */
    public function filter_orders_for_current_vendor( $args, $query ) {
        global $wpdb;

        if ( ! is_admin() || ! $query->is_main_query() ) {
            return $args;
        }

        if ( ! isset( $query->query_vars['post_type'] ) ) {
            return $args;
        }

        if ( ! in_array( $query->query_vars['post_type'], [ 'shop_order', 'wc_booking' ], true ) ) {
            return $args;
        }

        $vendor_id = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore;
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            $vendor_id = dokan_get_current_user_id();
        }

        if ( ! $vendor_id ) {
            return $args;
        }

        $args['join']  .= " LEFT JOIN {$wpdb->prefix}dokan_orders as do ON $wpdb->posts.ID=do.order_id";
        $args['where'] .= " AND do.seller_id=$vendor_id";

        return $args;
    }

    /**
     * Revoke vendor access of changing order status in the backend if permission is not given
     *
     * @since 2.8.0
     * @since DOKAN_SINCE Moved this method from includes/functions.php file
     *
     * @return void
     */
    public function revoke_change_order_status() {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( is_admin() && get_current_screen()->id === 'shop_order' ) {
            if ( dokan_get_option( 'order_status_change', 'dokan_selling', 'on' ) !== 'on' ) {
                ?>
                <style media="screen">
                    .order_data_column .wc-order-status {
                        display: none !important;
                    }
                </style>
                <?php
            }
        }
    }

    /**
     * Revoke vendor access of changing order status in the backend if permission is not given
     *
     * @since 2.8.0
     * @since DOKAN_SINCE Moved this method from includes/functions.php
     *
     * @param array $columns
     *
     * @return array
     */
    public function remove_action_column( $columns ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return $columns;
        }

        if ( dokan_get_option( 'order_status_change', 'dokan_selling', 'on' ) !== 'on' ) {
            unset( $columns['wc_actions'] );
        }

        return $columns;
    }

    /**
     * Revoke vendor access of changing order status in the backend if permission is not given
     *
     * @since 2.8.0
     * @since DOKAN_SINCE Moved this method form includes/functions.php file
     *
     * @param array $actions
     *
     * @return array;
     */
    public function remove_action_button( $actions ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return $actions;
        }

        if ( dokan_get_option( 'order_status_change', 'dokan_selling', 'on' ) !== 'on' ) {
            unset( $actions['status'] );
        }

        return $actions;
    }
}
