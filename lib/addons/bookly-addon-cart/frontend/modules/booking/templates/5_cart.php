<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Utils\Price;
use Bookly\Lib\Proxy;
/** @var \Bookly\Lib\CartInfo $cart_info
 *  @var array $table */
echo $progress_tracker;
?>
<div class="bookly-box"><?php echo $info_text ?></div>
<div class="bookly-box">
    <button class="bookly-add-item bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_book_more' ) ?></span>
    </button>
</div>
<div class="bookly-box bookly-label-error"></div>
<div class="bookly-cart-step">
    <div class="bookly-cart bookly-box">
        <table>
            <thead class="bookly-desktop-version">
                <tr>
                    <?php foreach ( $table['headers'] as $position => $column ) : ?>
                        <th <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>><?php echo $column ?></th>
                    <?php endforeach ?>
                    <th></th>
                </tr>
            </thead>
            <tbody class="bookly-desktop-version">
            <?php foreach ( $table['rows'] as $key => $data ) : ?>
                <tr data-cart-key="<?php echo $key ?>" class="bookly-cart-primary">
                    <?php foreach ( $data as $position => $value ) : ?>
                    <td <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>><?php echo $value ?></td>
                    <?php endforeach ?>
                    <td class="bookly-rtext bookly-nowrap bookly-js-actions">
                        <button class="bookly-round" data-action="edit" title="<?php esc_attr_e( 'Edit', 'bookly-cart' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-edit"></i></span></button>
                        <button class="bookly-round" data-action="drop" title="<?php esc_attr_e( 'Remove', 'bookly-cart' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-drop"></i></span></button>
                    </td>
                </tr>
                <?php Proxy\Shared::renderCartItemInfo( $userData, $key, $table['header_position'], true ) ?>
            <?php endforeach ?>
            </tbody>
            <tbody class="bookly-mobile-version">
            <?php foreach ( $table['rows'] as $key => $data ) : ?>
                <?php foreach ( $data as $position => $value ) : ?>
                    <tr data-cart-key="<?php echo $key ?>" class="bookly-cart-primary">
                        <th><?php echo $table['headers'][ $position ] ?></th>
                        <td><?php echo $value ?></td>
                    </tr>
                <?php endforeach ?>
                <?php Proxy\Shared::renderCartItemInfo( $userData, $key, $table['header_position'], false ) ?>
                <tr data-cart-key="<?php echo $key ?>">
                    <th></th>
                    <td class="bookly-js-actions">
                        <button class="bookly-round" data-action="edit" title="<?php esc_attr_e( 'Edit', 'bookly-cart' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-edit"></i></span></button>
                        <button class="bookly-round" data-action="drop" title="<?php esc_attr_e( 'Remove', 'bookly-cart' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-drop"></i></span></button>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
            <?php if ( isset( $table['header_position']['price'] ) || ( $table['show']['deposit'] && isset( $table['header_position']['deposit'] ) ) ) : ?>
                <tfoot class="bookly-mobile-version">
                <?php Proxy\DepositPayments::renderPayNowRow( $cart_info, $table, 'mobile' ) ?>
                <?php if ( isset ( $table['header_position']['price'] ) ) : ?>
                    <tr class="bookly-cart-total">
                        <th><?php esc_html_e( 'Total', 'bookly-cart' ) ?>:</th>
                        <td><strong class="bookly-js-total-price"><?php echo Price::format( $cart_info->getTotal() ) ?></strong></td>
                    </tr>
                    <?php if( $table['show']['tax'] ) : ?>
                        <tr class="bookly-cart-total">
                            <th><?php esc_html_e( 'Total tax', 'bookly-cart' ) ?>:</th>
                            <td><strong class="bookly-js-total-tax"><?php echo Price::format( $cart_info->getTotalTax() ) ?></strong></td>
                        </tr>
                    <?php endif ?>
                <?php endif ?>
                </tfoot>
                <tfoot class="bookly-desktop-version">
                <?php if ( $cart_info->getWaitingListTotal() > 0 ) : ?>
                    <tr class="bookly-cart-total">
                        <?php foreach ( $table['headers'] as $position => $column ) : ?>
                            <td <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                                <?php if ( $position == 0 ) : ?>
                                    <strong><?php esc_html_e( 'Waiting list', 'bookly-cart' ) ?>:</strong>
                                <?php endif ?>
                                <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ): ?>
                                    <strong class="bookly-js-waiting-list-price"><?php echo Price::format( - $cart_info->getWaitingListTotal() ) ?></strong>
                                <?php endif ?>
                                <?php if ( $table['show']['deposit'] && $position == $table['header_position']['deposit'] ) : ?>
                                    <strong class="bookly-js-waiting-list-deposit"><?php echo Price::format( - $cart_info->getWaitingListDeposit() ) ?></strong>
                                <?php endif ?>
                            </td>
                        <?php endforeach ?>
                        <td></td>
                    </tr>
                <?php endif ?>
                <tr class="bookly-cart-subtotal">
                    <?php foreach ( $table['headers'] as $position => $column ) : ?>
                        <td <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                            <?php if ( $position == 0 ) : ?>
                                <strong><?php esc_html_e( 'Subtotal', 'bookly' ) ?>:</strong>
                            <?php endif ?>
                            <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) : ?>
                                <strong class="bookly-js-subtotal-price"><?php echo Price::format( $cart_info->getSubtotal() ) ?></strong>
                            <?php endif ?>
                            <?php if ( $table['show']['deposit'] && $position == $table['header_position']['deposit'] ) : ?>
                                <strong class="bookly-js-subtotal-deposit"><?php echo Price::format( $cart_info->getDeposit() ) ?></strong>
                            <?php endif ?>
                        </td>
                    <?php endforeach ?>
                    <td></td>
                </tr>
                <?php Proxy\CustomerGroups::renderCartDiscountRow( $table, 'desktop' ) ?>
                <?php Proxy\DepositPayments::renderPayNowRow( $cart_info, $table, 'desktop' ) ?>
                <tr class="bookly-cart-total">
                    <?php foreach ( $table['headers'] as $position => $column ) : ?>
                    <td <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                        <?php if ( $position == 0 ) : ?>
                        <strong><?php esc_html_e( 'Total', 'bookly-cart' ) ?>:</strong>
                        <?php endif ?>
                        <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) : ?>
                        <strong class="bookly-js-total-price"><?php echo Price::format( $cart_info->getTotal() ) ?></strong>
                        <?php endif ?>
                        <?php if ( $table['show']['tax'] && $position == $table['header_position']['tax'] ) : ?>
                        <strong class="bookly-js-total-tax"><?php echo Price::format( $cart_info->getTotalTax() ) ?></strong>
                        <?php endif ?>
                    </td>
                    <?php endforeach ?>
                    <td></td>
                </tr>
                </tfoot>
            <?php endif ?>
        </table>
    </div>
</div>

<?php Proxy\RecurringAppointments::renderInfoMessage( $userData ) ?>

<div class="bookly-box bookly-nav-steps">
    <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
    </button>
    <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_step_cart_button_next' ) ?></span>
    </button>
</div>