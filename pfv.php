<?php
/**
 * @package 
 * @Author: advivadk
 * @version 1.0.9.1
 */
/*
Plugin Name: Product Feed Viewer
Plugin URI: http://www.productfeedviewer.dk/
Description: Show products from Partner Ads (Affiliate Network) product feeds. A plugin for showing affiliate products. Go to the plugin options page to see usage.
Author: advivadk
Version: 1.0.9.1
Author URI: http://adviva.dk/

*/
$supportMail = 'mail@adviva.dk';

if (!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');
if (!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', ABSPATH.'wp-content');
if (!defined('WP_PLUGIN_URL')) define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
if (!defined('WP_PLUGIN_DIR')) define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
if(!defined('CACHEDIR')) define('CACHEDIR', __DIR__ . '/cache/');

add_action('activated_plugin', 'pfv_save_activation_error');
function pfv_save_activation_error() {
    $error = ob_get_contents();
    if(!empty($error)) update_option('pfv_plugin_error',  $error);
}

function pfv_callback($action) {
    $response = wp_remote_post('http://productfeedviewer.dk/callback/tracksite.php', array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
                'action' => $action,
                'home_url' => home_url()
            ),
            'cookies' => array()
        )
    );

    if(is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
    }/* else {
		echo 'Response:<pre>';
		print_r($response);
		echo '</pre>';
	}*/
}

//Install funcsion
function pfv_install() {
    if(get_option('pfv_db_version') === false) {
        //Install
        pfv_callback('install');
    } else {
        //Update
        pfv_callback('update');
    }
    $pfv_db_version = '1.0';
    update_option('pfv_db_version', $pfv_db_version);
}

function pfv_deactivate() {
    //Deactivate
    pfv_callback('deactivate');
}

register_activation_hook(__FILE__, 'pfv_install');
register_deactivation_hook(__FILE__, 'pfv_deactivate');
add_action('admin_menu', 'pfv_plugin_menu');

function pfv_plugin_menu() {
    add_options_page('Product Feed Viewer', 'Product Feed Viewer', 8, __FILE__, 'pfv_plugin_options');
}

//Funcion para crear los elementos del menu
function pfv_plugin_options() {
    global $supportMail;
    ?>
    <style type="text/css">
        .form-table th {
            font-weight: bold;
        }
    </style>
    <div class="wrap">
        <h1>Product Feed Viewer</h1>
        <p>Support kan f&#229;s p&#229; <a href="mailto:<?php echo $supportMail; ?>?subject=Sp&#248;rgsm&#229;l til Product Feed Viewer"><?php echo $supportMail; ?></a>.</p>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options'); ?>
            <table width="100%" class="form-table">
                <tr valign="top">
                    <th colspan="2" scope="row"><h3>Indstillinger</h3></th>
                </tr>

                <?php
                /*
                <tr valign="top">
                    <td width="40%" scope="row"><div align="left">Tradedoubler Affiliate ID</div></td>
                    <td width="40%"><input type="text" name="pfv_td" value="<?php echo get_option('pfv_td'); ?>" /></td>
                </tr>
                */
                ?>
                <?php
                /*
                <tr valign="top">
                    <td width="40%" scope="row"><div align="left">Partner Ads Affiliate ID</div></td>
                    <td width="40%"><input type="text" name="pfv_pa" value="<?php echo get_option('pfv_pa'); ?>" /></td>
                </tr>
                */
                ?>

                <?php /*
			<tr valign="top">
				<td width="40%" scope="row">
                    <div align="left">
                        "Betaling"<br />
                        <small>
                            Plugin er gratis at bruge mod at du lader plugin linke retur til
                            http://productfeedviewer.dk/ (det er sat automatisk op til dette)"
                        </small>
                    </div>
                </td>
				<td width="40%">
					<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('.pfv_payment').change(function() {
							if(jQuery('input[name=pfv_payment]:checked').val() == 'paid') {
								jQuery('#pfv_paid_license').show();
								jQuery('#pfv_license').focus();
							} else {
								jQuery('#pfv_paid_license').hide();
							}
						});
					});
					</script>
					<?php
					$pfv_payment = get_option('pfv_payment');
					?>
                    <?php /*
                    <input class="pfv_payment" type="radio" name="pfv_payment" <?php if($pfv_payment == 'link') echo 'checked="checked"'; ?> value="link" id="pfv_link" /> <label for="pfv_link">Backlink til plugin side</label><br />
                    <input class="pfv_payment" type="radio" name="pfv_payment" <?php if($pfv_payment == 'percentage') echo 'checked="checked"'; ?> value="percentage" id="pfv_percentage" /> <label for="pfv_percentage">Udskiftning af partnerid i 5% af linksene</label><br />
					<input class="pfv_payment" type="radio" name="pfv_payment" <?php if($pfv_payment == 'paid') echo 'checked="checked"'; ?> value="paid" id="pfv_paid" /> <label for="pfv_paid">Jeg har betalt og har en licensekode:</label><br />
                    */ ?>
                <?php /*
					<div id="pfv_paid_license" style="display:<?php if($pfv_payment == 'paid') { echo 'block'; } else { echo 'none'; }?>"><input type="text" id="pfv_license" name="pfv_license" value="<?php echo get_option('pfv_license'); ?>" /></div>
				</td>
			</tr>
            */ ?>

                <tr valign="top">
                    <td width="40%" scope="row"><div align="left">Produktfeed Cache<br /><small>I sekunder, en dag = 86400, en uge = 604800) Standard er en uge.</small></div></td>
                    <td width="40%"><input type="text" name="pfv_cache_timeout" value="<?php echo get_option('pfv_cache_timeout'); ?>" /></td>
                </tr>
            </table>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="pfv_td,pfv_pa,pfv_payment,pfv_license,pfv_cache_timeout" />
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>

        <table width="100%" class="form-table">
            <tr valign="top">
                <th scope="row"><h3>Brug</h3></th>
            </tr>
            <tr valign="top">
                <td scope="row">[PFV <?php /*network="NETV&#198;RK" */?>query="S&#216;GEORD1|S&#216;GEORD2" feed="URL"]</td>
            </tr>
            <tr valign="top">
                <td scope="row">[PFV <?php /*network="NETV&#198;RK" */?>query="S&#216;GEORD1|S&#216;GEORD2" feed="URL" sorting="FELT" exclude="UDELUK1|UDELUK2" template="FILNAVN" newlineafter="ANTAL_PRODUKTER_F&#216;R_LINJESKIFT" limit="ANTAL_PRODUKTER"]</td>
            </tr>
            <tr valign="top">
                <th scope="row"><h3>Standard v&#230;rdier</h3></th>
            </tr>
            <tr valign="top">
                <td>
                    <table>
                        <tr>
                            <th>Felt</th>
                            <th>Standardv&#230;rdi</th>
                            <th>Muligheder</th>
                            <th>Funtkion</th>
                        </tr>
                        <?php
                        /*
                        <tr>
                            <td>network</td>
                            <td><i>n/a</i></td>
                            <td>pa, td</td>
                            <td>V&#230;lg om du vil bruge links fra Partner Ads eller Tradedoubler.</td>
                        </tr>
                        */
                        ?>
                        <tr>
                            <td>query</td>
                            <td><i>n/a</i></td>
                            <td>Opdel ord med |</td>
                            <td>Finder kun produkter som indeholder alle s&#248;geordene.</td>
                        </tr>
                        <tr>
                            <td>sorting</td>
                            <td>price</td>
                            <td>productsid, productsname, productsdescription, productsprice, productsurl, productsimageurl, categoryname, brand, currency</td>
                            <td>Hvilken v&#230;rdi du vil sortere produktlisten efter.</td>
                        </tr>
                        <tr>
                            <td>exclude</td>
                            <td><i>n/a</i></td>
                            <td>Opdel ord med |</td>
                            <td>Finder kun produkter, som ikke indeholder minimum 1 af ordene.</td>
                        </tr>
                        <tr>
                            <td>template</td>
                            <td>template.tpl</td>
                            <td>Opret flere templates.</td>
                            <td>Du kan her definerer hvilken template der skal bruges til at vise de valgte produkter.</td>
                        </tr>
                        <tr>
                            <td>newlineafter</td>
                            <td>2</td>
                            <td>1-10</td>
                            <td>V&#230;lg hvor mange produkter der skal vises f&#248;r der kommer et tvunget linjeskift.</td>
                        </tr>
                        <tr>
                            <td>limit</td>
                            <td>99999999</td>
                            <td>1-&infin;</td>
                            <td>V&#230;lg hvor mange produkter der skal vises.</td>
                        </tr>
                        <tr>
                            <td>buttoncolor</td>
                            <td>#59c44e</td>
                            <td>Indtast farvekode</td>
                            <td>V&#230;lg hvilken farve "L&#230;s mere"-knappen skal have.</td>
                        </tr>
                        <tr>
                            <td>buttonbordercolor</td>
                            <td>#388e2f</td>
                            <td>Indtast farvekode</td>
                            <td>V&#230;lg hvilken farve kanten omkring "L&#230;s mere"-knappen skal have.</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table width="100%" class="form-table">
            <tr valign="top">
                <th scope="row"><h3>Template muligheder</h3></th>
            </tr>
            <tr valign="top">
                <td>
                    <p>Templatefilen finder du i mappen <b>/templates/</b> i pluginmappen. Den hedder <b>template.tpl</b>. Du kan kopirer denne fil og lave en template1.tpl, template2.tpl m.f. hvis du &#248;nsker at have flere muligheder for at fremvise produkter.</p>
                    <table>
                        <tr>
                            <th>Feltnavn</th>
                            <th>Beskrivelse</th>
                        </tr>
                        <tr>
                            <td>[PRODUCTSID]</td>
                            <td>Varenummeret</td>
                        </tr>
                        <tr>
                            <td>[PRODUCTSNAME]</td>
                            <td>Produktnavnet</td>
                        </tr>
                        <tr>
                            <td>[PRODUCTSDESCRIPTION]</td>
                            <td>Produktbeskrivelsen</td>
                        </tr>
                        <tr>
                            <td>[PRODUCTSPRICE]</td>
                            <td>Produktpris</td>
                        </tr>
                        <tr>
                            <td>[PRODUCTSURL]</td>
                            <td>Produkturl inkl. affiliate tracking.</td>
                        </tr>
                        <tr>
                            <td>[PRODUCTSIMAGEURL]</td>
                            <td>Produktbilledeurl</td>
                        </tr>
                        <tr>
                            <td>[CATEGORYNAME]</td>
                            <td>Kategorinavn</td>
                        </tr>
                        <tr>
                            <td>[BRAND]</td>
                            <td>M&#230;rke</td>
                        </tr>
                        <tr>
                            <td>[CURRENCY]</td>
                            <td>Valuta</td>
                        </tr>
                        <tr>
                            <td>[BUTTONCOLOR]</td>
                            <td>Farve p&#229; "L&#230;s mere"-knappen.</td>
                        </tr>
                        <tr>
                            <td>[BUTTONBORDERCOLOR]</td>
                            <td>Farve p&#229; kanten p&#229; "L&#230;s mere"-knappen.</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <?php
}
require_once(dirname(__FILE__).'/function.php');
add_shortcode('PFV', 'pfv_ProductFeedViewer');
add_shortcode('pfv', 'pfv_ProductFeedViewer');
?>