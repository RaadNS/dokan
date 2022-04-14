<?php
/**
 * @var $trn_date array
 */
$localized_date = dokan_format_date( $trn_date['from'] ) . ' - ' . dokan_format_date( $trn_date['to'] );
?>
<form method="get" action="" id="reverse-withdrawal-filter-form" class="dokan-form-inline">
    <div class="dokan-form-group">
        <input type="text" class="" id="trn_date_filter" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan' ); ?>" value="<?php echo esc_attr( $localized_date ); ?>" autocomplete="off">
        <input type="hidden" name="trn_date[from]" id="trn_date_form_filter_alt" value="<?php echo esc_attr( $trn_date['from'] ); ?>" />
        <input type="hidden" name="trn_date[to]" id="trn_date_to_filter_alt" value="<?php echo esc_attr( $trn_date['to'] ); ?>" />

        <?php wp_nonce_field( 'dokan_reverse_withdrawal_filter', '_nonce', false ) ?>
        <input type="submit" class="dokan-btn dokan-btn-info dokan-btn-lg dokan-theme" value="<?php esc_html_e( 'Filter', 'dokan-lite' ); ?>">
    </div>
</form>
