<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components;
use Bookly\Backend\Components\Appearance\Codes;
use Bookly\Backend\Components\Appearance\Editable;
use Bookly\Backend\Modules\Appearance\Proxy;
use Bookly\Backend\Modules\Settings\Page as Settings;
use Bookly\Lib\Config;
use Bookly\Lib\Utils\Common;
/** @var array $userData */
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>

    <div class="bookly-box">
        <?php Editable::renderText( 'bookly_l10n_info_details_step', Codes::getHtml( 6 ) ) ?>
    </div>
    <div class="bookly-box">
        <?php Editable::renderText( 'bookly_l10n_info_details_step_guest', Codes::getHtml( 6, true ), 'bottom', __( 'Visible to non-logged in customers only', 'bookly' ) ) ?>
    </div>
    <div class="bookly-box bookly-guest">
        <div class="bookly-btn" id="bookly-login-button">
            <?php Editable::renderString( array( 'bookly_l10n_step_details_button_login' ) ) ?>
        </div>
        <div class="fb-login-button" id="bookly-facebook-login-button" data-max-rows="1" data-size="large" data-button-type="login_with" data-show-faces="false" data-auto-logout-link="false" data-use-continue-as="false" data-scope="public_profile,email" style="display:none"></div>
    </div>
    <div class="bookly-details-step">

        <div class="bookly-box bookly-table bookly-details-first-last-name" style="display: <?php echo get_option( 'bookly_cst_first_last_name' ) == 0 ? ' none' : 'table' ?>">
            <div class="bookly-form-group">
                <?php Editable::renderLabel( array( 'bookly_l10n_label_first_name', 'bookly_l10n_required_first_name', ) ) ?>
                <div>
                    <input type="text" value="" maxlength="60" />
                </div>
            </div>
            <div class="bookly-form-group">
                <?php Editable::renderLabel( array( 'bookly_l10n_label_last_name', 'bookly_l10n_required_last_name', ) ) ?>
                <div>
                    <input type="text" value="" maxlength="60" />
                </div>
            </div>
        </div>

        <div class="bookly-box bookly-table">
            <div class="bookly-form-group bookly-details-full-name" style="display: <?php echo get_option( 'bookly_cst_first_last_name' ) == 1 ? ' none' : 'block' ?>">
                <?php Editable::renderLabel( array( 'bookly_l10n_label_name', 'bookly_l10n_required_name', ) ) ?>
                <div>
                    <input type="text" value="" maxlength="60" />
                </div>
            </div>
            <div class="bookly-form-group">
                <?php Editable::renderLabel( array( 'bookly_l10n_label_phone', 'bookly_l10n_required_phone', ) ) ?>
                <div>
                    <input type="text" class="<?php if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) : ?>bookly-user-phone<?php endif ?>" value="" />
                </div>
            </div>
            <div class="bookly-form-group">
                <?php Editable::renderLabel( array( 'bookly_l10n_label_email', 'bookly_l10n_required_email' ) ) ?>
                <div>
                    <input maxlength="40" type="text" value="" />
                </div>
            </div>
        </div>

        <div id="bookly-js-address" style="display: <?php echo get_option( 'bookly_app_show_address' ) == 1 ? ' block' : 'none' ?>">
            <div class="bookly-box bookly-bold">
                <?php Editable::renderText( 'bookly_l10n_info_address' ); ?>
            </div>

            <?php Proxy\GoogleMapsAddress::renderAutocompleter() ?>
            <?php Components\Appearance\Address::render() ?>
        </div>

        <div class="bookly-box bookly-table" id="bookly-js-birthday">
            <?php Components\Appearance\Birthday::render() ?>
        </div>

        <div class="bookly-box" id="bookly-js-notes">
            <div class="bookly-form-group">
                <?php Editable::renderLabel( array( 'bookly_l10n_label_notes' ) ) ?>
                <div>
                    <textarea rows="3"></textarea>
                </div>
            </div>
        </div>

        <?php Proxy\Files::renderAppearance() ?>
    </div>

    <?php Proxy\RecurringAppointments::renderInfoMessage() ?>

    <div class="bookly-box bookly-nav-steps">
        <div class="bookly-back-step bookly-js-back-step bookly-btn">
            <?php Editable::renderString( array( 'bookly_l10n_button_back' ) ) ?>
        </div>
        <div class="bookly-next-step bookly-js-next-step bookly-btn">
            <?php Editable::renderString( array( 'bookly_l10n_step_details_button_next' ) ) ?>
        </div>
    </div>
</div>
<?php if ( Config::getFacebookAppId() != '' ) : ?>
    <div id="fb-root"></div>
    <script>
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = <?php echo json_encode( sprintf( 'https://connect.facebook.net/%s/sdk.js#xfbml=1&version=v2.12&appId=%s', Config::getLocale(), Config::getFacebookAppId() ) ) ?>;
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    </script>
<?php else : ?>
    <div id="bookly-facebook-warning" class="modal fade" tabindex=-1 role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <div class="modal-title h2">Facebook</div>
                </div>
                <div class="modal-body">
                    <?php printf( __( 'Please configure Facebook App integration in <a href="%s">settings</a> first.', 'bookly' ), Common::escAdminUrl( Settings::pageSlug(), array( 'tab' => 'facebook' ) ) ) ?>
                </div>
                <div class="modal-footer">
                    <?php Buttons::renderCustom( null, 'btn-default btn-lg', __( 'Ok', 'bookly' ), array( 'data-dismiss' => 'modal' ) ) ?>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>