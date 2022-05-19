<?php
namespace WeDevs\Dokan\ReverseWithdrawal;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Helper class for reverse withdrawal
 *
 * @since DOKAN_SINCE
 */
class Helper {
    /**
     * This method will return option key for reverse withdrawal base product
     *
     * @since DOKAN_SINCE
     *
     * @return string
     */
    public static function get_base_product_option_key() {
        return 'dokan_reverse_withdrawal_product_id';
    }

    /**
     * This method will return balance_threshold_exceed_date_key
     *
     * @since DOKAN_SINCE
     *
     * @return string
     */
    public static function balance_threshold_exceed_date_key() {
        return '_dokan_reverse_withdrawal_threshold_exceeded_date';
    }

    /**
     * This method will return failed actions key
     *
     * @return string
     */
    public static function failed_actions_key() {
        return '_dokan_reverse_withdrawal_failed_actions';
    }

    /**
     * Get reverse withdrawal failed payment actions
     *
     * @since DOKAN_SINCE
     *
     * @return array|string return associated array of transaction types if no argument is provided. If $transaction_type is provided and if data exists then return the label otherwise return empty string
     */
    public static function get_transaction_types( $transaction_type = null ) {
        /**
         * ! do not change the keys, it will break the query
         * ! also do not use any filter here, if new transaction type is needed add it to the below array
         */
        $transaction_types = [
            // admin will get payment (debit)
            'order_commission'          => esc_html__( 'Commission', 'dokan-lite' ),
            'failed_transfer_reversal'  => esc_html__( 'Failed Transfer Reversal', 'dokan-lite' ),
            'product_advertisement'     => esc_html__( 'Product Advertisement', 'dokan-lite' ),
            'manual_order_commission'   => esc_html__( 'Manual Order Commission', 'dokan-lite' ),
            // vendor paid to admin (credit)
            'vendor_payment'            => esc_html__( 'Payment', 'dokan-lite' ),
            'order_refund'              => esc_html__( 'Refund', 'dokan-lite' ),
        ];

        if ( $transaction_type ) {
            return isset( $transaction_types[ $transaction_type ] ) ? $transaction_types[ $transaction_type ] : '';
        }

        return $transaction_types;
    }

    /**
     * Get reverse withdrawal failed payment actions
     *
     * @since DOKAN_SINCE
     *
     * @param $vendor_id
     *
     * @return array
     */
    public static function get_failed_actions_by_vendor( $vendor_id ) {
        return get_user_meta( $vendor_id, self::failed_actions_key(), true );
    }

    /**
     * Set reverse withdrawal failed payment actions
     *
     * @since DOKAN_SINCE
     *
     * @param int $vendor_id
     * @param array $failed_actions
     *
     * @return void
     */
    public static function set_failed_actions_by_vendor( $vendor_id, $failed_actions ) {
        update_user_meta( $vendor_id, self::failed_actions_key(), $failed_actions );
    }

    /**
     * This method will return the balance threshold exceeded date
     *
     * @since DOKAN_SINCE
     *
     * @param $vendor_id
     *
     * @return mixed
     */
    public static function get_balance_threshold_exceed_date( $vendor_id ) {
        return get_user_meta( $vendor_id, self::balance_threshold_exceed_date_key(), true );
    }

    /**
     * This method will update the balance threshold exceeded date
     *
     * @since DOKA_SINCE
     *
     * @param int $vendor_id
     * @param string $date
     *
     * @return void
     */
    public static function set_balance_threshold_exceed_date( $vendor_id, $date = '' ) {
        update_user_meta( $vendor_id, self::balance_threshold_exceed_date_key(), $date );
    }

    /**
     * This method will check if cart contain reverse withdrawal product
     *
     * @since DOKAN_SINCE
     *
     * @return bool
     */
    public static function has_reverse_withdrawal_payment_in_order( $order ) {
        // check if we get order object or order id
        if ( ! $order instanceof \WC_Abstract_Order && is_numeric( $order ) ) {
            // get order object from order_id
            $order = wc_get_order( $order );
        }

        if ( ! $order instanceof \WC_Abstract_Order ) {
            return false;
        }

        foreach ( $order->get_items() as $item ) {
            if ( $item->get_meta( '_dokan_reverse_withdrawal_balance' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method will return reverse withdrawal payment amount
     *
     * @param \WC_Abstract_Order $order
     *
     * @since DOKAN_SINCE
     *
     * @return float|bool false if meta key not found
     */
    public static function get_balance_from_order( \WC_Abstract_Order $order ) {
        $balance = false;

        foreach ( $order->get_items() as $item ) {
            if ( $item->get_meta( '_dokan_reverse_withdrawal_balance' ) ) {
                $balance = floatval( wc_format_decimal( $item->get_meta( '_dokan_reverse_withdrawal_balance' ) ) );
                break;
            }
        }

        return $balance;
    }

    /**
     * Get reverse withdrawal base product id
     *
     * @since DOKAN_SINCE
     *
     * @return int
     */
    public static function get_reverse_withdrawal_base_product() {
        // get product id from option table
        return (int) get_option( static::get_base_product_option_key(), 0 );
    }

    /**
     * This method will check if cart contain reverse withdrawal payment product
     *
     * @since DOKAN_SINCE
     *
     * @return bool
     */
    public static function has_reverse_withdrawal_payment_in_cart() {
        if ( ! WC()->cart ) {
            return false;
        }

        foreach ( WC()->cart->get_cart() as $item ) {
            if ( isset( $item['dokan_reverse_withdrawal_balance'] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method will return formatted transaction id
     *
     * @since DOKAN_SINCE
     *
     * @param int $transaction_id
     * @param string $transaction_type
     * @param string $contex admin or seller
     *
     * @return string
     */
    public static function get_formatted_transaction_id( $transaction_id, $transaction_type, $contex = 'admin' ) {
        switch ( $transaction_type ) {
            case 'product_advertisement':
                // get product edit link
                $url = $contex === 'admin'
                    ? get_admin_url( null, 'post.php?post=' . $transaction_id . '&action=edit' )
                    : dokan_edit_product_url( $transaction_id );
                break;

            case 'vendor_payment':
                $order = wc_get_order( $transaction_id );
                // get vendor order view url
                $order_view_url = '#';
                if ( $order ) {
                    $order_view_url = $order->get_view_order_url();
                }
                $url = $contex === 'admin'
                    ? get_admin_url( null, 'post.php?post=' . $transaction_id . '&action=edit' )
                    : $order_view_url;
                break;

            default:
                // get order edit link
                $url = $contex === 'admin'
                    ? get_admin_url( null, 'post.php?post=' . $transaction_id . '&action=edit' )
                    : wp_nonce_url( add_query_arg( [ 'order_id' => $transaction_id ], dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' );
        }

        return esc_url_raw( $url );
    }

    /**
     * This method will return formatted transaction data
     *
     * @since DOKAN_SINCE
     *
     * @param array $item
     * @param float $current_balance
     * @param string $context
     *
     * @return array
     */
    public static function get_formated_transaction_data( $item, &$current_balance, $context = 'admin' ) {
        $current_balance = ( $current_balance + $item['debit'] ) - $item['credit'];
        return [
            'id'        => absint( $item['id'] ),
            'trn_id'    => absint( $item['trn_id'] ),
            'trn_url'   => static::get_formatted_transaction_id( absint( $item['trn_id'] ), sanitize_text_field( $item['trn_type'] ), $context ),
            'trn_date'  => dokan_format_date( $item['trn_date'] ),
            'trn_type'  => static::get_transaction_types( $item['trn_type'] ),
            'vendor_id' => absint( $item['vendor_id'] ),
            'note'      => esc_html( $item['note'] ),
            'debit'     => $item['debit'],
            'credit'    => $item['credit'],
            'balance'   => $current_balance,
        ];
    }

    /**
     * This method will return default transaction data for vendor reverse withdrawal balance
     *
     * @since DOKAN_SINCE
     *
     * @return array
     */
    public static function get_default_transaction_date() {
        return [
            'from' => dokan_current_datetime()->modify( '-1 month' )->format( 'Y-m-d' ),
            'to'   => dokan_current_datetime()->format( 'Y-m-d' ),
        ];
    }

    /**
     * This method will return payable amount of a vendor for a month
     *
     * @param int $vendor_id
     * @param int|string $current_date
     *
     * @return float|WP_Error
     */
    public static function get_vendor_payable_amount_by_month( $vendor_id, $current_date ) {
        // check for valid vendor id
        if ( empty( $vendor_id ) || ! is_numeric( $vendor_id ) ) {
            return new WP_Error( 'invalid_vendor_id', __( 'No vendor id provided', 'dokan-lite' ) );
        }

        // get current_date
        $current_date = is_numeric( $current_date ) ? dokan_current_datetime()->setTimestamp( $current_date ) : dokan_current_datetime()->modify( $current_date );
        // validate current date
        if ( ! $current_date ) {
            return new WP_Error( 'invalid_date', __( 'Invalid date provided', 'dokan-lite' ) );
        }

        $manager = new Manager();
        // get previous month balance
        $previous_month_balance = $manager->get_store_balance(
            [
                'vendor_id' => $vendor_id,
                'trn_date'  => [
                    // we need remaining balance till previous month
                    'to' => $current_date->modify( 'last day of previous month' )->format( 'Y-m-d' ),
                ],
            ]
        );

        if ( is_wp_error( $previous_month_balance ) ) {
            return $previous_month_balance;
        }

        if ( $previous_month_balance <= 0 ) {
            return 0;
        }

        // is user paid for previous month
        $paid_balance = $manager->get_payments_by_vendor(
            [
                'vendor_id' => $vendor_id,
                'trn_date'  => [
                    'from' => $current_date->modify( 'first day of this month' )->format( 'Y-m-d' ),
                    'to'   => $current_date->modify( 'last day of this month' )->format( 'Y-m-d' ),
                ],
            ]
        );

        if ( is_wp_error( $paid_balance ) ) {
            return $paid_balance;
        }

        return $previous_month_balance - $paid_balance;
    }

    /**
     * This method will return payable balance of a vendor
     *
     * @since DOKAN_SINCE
     *
     * @param int|null $vendor_id
     * @param int|string|null $current_date
     *
     * @return array|WP_Error
     */
    public static function get_vendor_balance( $vendor_id = null, $current_date = null ) {
        // check for valid vendor id
        if ( ! is_numeric( $vendor_id ) ) {
            $vendor_id = dokan_get_current_user_id();
        }

        // validate current_date
        if ( null === $current_date ) {
            $current_date = dokan_current_datetime();
        } else {
            $current_date = is_numeric( $current_date ) ? dokan_current_datetime()->setTimestamp( $current_date ) : dokan_current_datetime()->modify( $current_date );
        }

        if ( ! $current_date ) {
            return new WP_Error( 'invalid_date', __( 'Invalid date provided', 'dokan-lite' ) );
        }

        $manager = new Manager();
        // get balance of the vendor till now
        $balance = $manager->get_store_balance(
            [
				'vendor_id' => $vendor_id,
			]
        );

        if ( is_wp_error( $balance ) ) {
            return $balance;
        }

        // get required settings
        $args = [
            'balance'      => $balance,
            'billing_type' => SettingsHelper::get_billing_type(),
            'billing_day'  => SettingsHelper::get_billing_day(),
            'due_period'   => SettingsHelper::get_due_period(),
            'threshold'    => SettingsHelper::get_reverse_balance_threshold(),
        ];

        // check settings for billing type
        switch ( $args['billing_type'] ) {
            case 'by_month':
                // subtract paid balance from previous month balance
                $args['payable_amount'] = static::get_vendor_payable_amount_by_month( $vendor_id, $current_date->getTimestamp() );
                break;

            case 'by_amount':
                $args['payable_amount'] = $balance;
                break;
        }

        return $args;
    }

    /**
     * This method will check if vendor needs to pay balance along with details data
     *
     * @since DOKAN_SINCE
     *
     * @param int|null $vendor_id
     *
     * @return array|WP_Error
     */
    public static function get_vendor_due_status( $vendor_id = null, $current_date = null ) {
        // check for valid vendor id
        if ( ! is_numeric( $vendor_id ) ) {
            $vendor_id = dokan_get_current_user_id();
        }

        if ( ! $vendor_id ) {
            return new WP_Error( 'invalid_vendor_id', __( 'Invalid vendor provided.', 'dokan-lite' ) );
        }

        // validate current_date
        if ( null === $current_date ) {
            $current_date = dokan_current_datetime();
        } else {
            $current_date = is_numeric( $current_date ) ? dokan_current_datetime()->setTimestamp( $current_date ) : dokan_current_datetime()->modify( $current_date );
        }

        if ( ! $current_date ) {
            return new WP_Error( 'invalid_date', __( 'Invalid date provided', 'dokan-lite' ) );
        }

        // get balance
        $balance = static::get_vendor_balance( $vendor_id, $current_date->getTimestamp() );

        // check for error
        if ( is_wp_error( $balance ) ) {
            return $balance;
        }

        $ret = [
            'status'   => false,
            'message'  => '',
            'due_date' => '',
            'balance'  => $balance,
        ];

        // check which billing type setting is enabled
        switch ( SettingsHelper::get_billing_type() ) {
            case 'by_month':
                // check if we need to display payment notice
                if ( $balance['payable_amount'] <= 0 ) {
                    // there is no balance to be paid
                    break;
                }

                // vendor needs to pay due amount
                $ret['status'] = true;

                // check if user crossed the due period
                $due_date = $current_date->modify( 'first day of this month' )->setTime( 23, 59, 59 );

                if ( SettingsHelper::get_billing_day() ) {
                    $billing_day = SettingsHelper::get_billing_day() - 1;
                    $due_date    = $billing_day > 0 ? $due_date->modify( "+ {$billing_day} days" ) : $due_date;
                }

                if ( SettingsHelper::get_due_period() ) {
                    $due_period = SettingsHelper::get_due_period() - 1;
                    $due_date   = $due_period > 0 ? $due_date->modify( "+ {$due_period} days" ) : $due_date;
                }

                if ( $current_date > $due_date ) {
                    // vendor needs to pay due balance immediately
                    $ret['due_date'] = 'immediate';
                } else {
                    // vendor needs to pay due balance on due date
                    $ret['due_date'] = $due_date->format( 'Y-m-d' );
                }

                break;

            case 'by_amount':
                $threshold = SettingsHelper::get_reverse_balance_threshold();
                if ( $balance['payable_amount'] < $threshold ) {
                    // balance amount is less than threshold
                    $ret['status'] = false;
                    break;
                }

                $ret['status'] = true;
                // check when user crossed the threshold limit
                $last_threshold_limit_exceed_date = static::get_balance_threshold_exceed_date( $vendor_id );
                $threshold_exceeded = false;
                if ( empty( $last_threshold_limit_exceed_date ) ) {
                    $last_threshold_limit_exceed_date = dokan_current_datetime()->format( 'Y-m-d' );
                    // we will only update this value if current date is today, because this function can be used to get older month's data
                    if ( $last_threshold_limit_exceed_date === $current_date->format( 'Y-m-d' ) ) {
                        $threshold_exceeded = true;
                        static::set_balance_threshold_exceed_date( $vendor_id, $last_threshold_limit_exceed_date );
                    }
                }

                //get due date
                $due_date = $current_date->modify( $last_threshold_limit_exceed_date )->setTime( 23, 59, 59 );

                if ( SettingsHelper::get_due_period() ) {
                    $due_period = SettingsHelper::get_due_period() - 1;
                    $due_date   = $due_period > 0 ? $due_date->modify( "+ {$due_period} days" ) : $due_date;
                }

                if ( $current_date > $due_date ) {
                    // vendor needs to pay due balance immediately
                    $ret['due_date'] = 'immediate';
                } else {
                    // vendor needs to pay due balance on due date
                    $ret['due_date'] = $due_date->format( 'Y-m-d' );
                }

                if ( $threshold_exceeded ) { // this will make sure that below hook will not be called twice
                    do_action( 'dokan_reverse_withdrawal_balance_threshold_exceed', $vendor_id, $ret, $last_threshold_limit_exceed_date );
                }

                break;
        }

        return $ret;
    }

    /**
     * This method will check if a vendors need to pay their unpaid balance
     *
     * @since DOKAN_SINCE
     *
     * @param int|null $vendor_id
     *
     * @return bool|WP_Error
     */
    public static function is_balance_due( $vendor_id = null ) {
        $due_status = static::get_vendor_due_status( $vendor_id );

        if ( is_wp_error( $due_status ) ) {
            return $due_status;
        }

        return $due_status['status'];
    }

    /**
     * This method will return formatted failed action messages
     *
     * @since DOKAN_SINCE
     *
     * @return string
     */
    public static function get_formatted_failed_actions() {
        $failed_actions = SettingsHelper::get_failed_actions();
        $messages       = [];
        foreach ( $failed_actions as $failed_action ) {
            switch ( $failed_action ) {
                case 'enable_catalog_mode':
                    $messages[] = __( 'Your products add to cart will be hidden. Hence users will not be able to purchase any of your products.', 'dokan-lite' );
                    break;

                case 'hide_withdraw_menu':
                    $messages[] = __( 'Withdraw menu will be hidden. Hence you will not be able to make any withdraw request from your account.', 'dokan-lite' );
                    break;

                case 'status_inactive':
                    $messages[] = __( 'Your account will be disabled for selling. Hence you will no longer be able to sell any products.', 'dokan-lite' );
                    break;
            }
        }

        $ret = '';
        if ( empty( $messages ) ) {
            return $ret;
        }

        $ret = '<ol>';
        foreach ( $messages as $message ) {
            $ret .= '<li>' . $message . '</li>';
        }
        $ret .= '</ol>';

        return $ret;
    }

    /**
     * This method will return formatted failed action messages
     *
     * @since DOKAN_SINCE
     *
     * @param int $vendor_id
     *
     * @return string
     */
    public static function get_formatted_failed_actions_by_vendor( $vendor_id ) {
        $failed_actions = static::get_failed_actions_by_vendor( $vendor_id );
        $messages       = [];

        foreach ( $failed_actions as $failed_action ) {
            switch ( $failed_action ) {
                case 'enable_catalog_mode':
                    $messages[] = __( 'Your products add to cart button has been temporarily hidden. Hence users are not able to purchase any of your products currently.', 'dokan-lite' );
                    break;

                case 'hide_withdraw_menu':
                    $messages[] = __( 'Withdraw menu has been temporarily hidden. Hence you are not able to make any withdrawal requests from your account.', 'dokan-lite' );
                    break;

                case 'status_inactive':
                    $messages[] = __( 'Your account has been temporarily disabled for selling. Hence you are no longer able to sell any products.', 'dokan-lite' );
                    break;
            }
        }

        $ret = '';
        if ( empty( $messages ) ) {
            return $ret;
        }

        $ret = '<ol>';
        foreach ( $messages as $message ) {
            $ret .= '<li>' . $message . '</li>';
        }
        $ret .= '</ol>';

        return $ret;
    }
}