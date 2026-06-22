<?php

namespace Flamix\Bitrix24\WooCommerce;

use FlamixLocal\WooOrders\Woo\Products;
use Flamix\Plugin\General\Checker;
use Flamix\Bitrix24\WooCommerce\Helpers;
use Flamix\Plugin\WP\Markup;
use Flamix\Plugin\WP\Recommendations;
?>
<div class="notice notice-error">
    <p>Install the Lead interceptor <a href="https://en.flamix.solutions/bitrix24/integrations/site/woocommerce.php" target="_blank">module in Bitrix24</a>!</p>
</div>
<div class="wrap">
    <h2>Bitrix24 Lead integrations with WooCommerce</h2>

    <form method="post" action="options.php">
        <?php settings_fields(Settings::getOptionName('group')); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Bitrix24 domain name</th>
                <td><input type="text" name="<?php echo esc_html(Settings::getOptionName('lead_domain')); ?>" placeholder="company.bitrix24.com" value="<?php echo esc_html(Settings::getOption('lead_domain')); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Flamix plugin API key</th>
                <td><input type="text" name="<?php echo esc_html(Settings::getOptionName('lead_api')); ?>" placeholder="xxxxxx.....xxxxx" value="<?php echo esc_html(Settings::getOption('lead_api')); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Backup mailbox</th>
                <td><input type="text" name="<?php echo esc_html(Settings::getOptionName('lead_backup_email')); ?>" placeholder="backup@email.com" value="<?php echo esc_html(Helpers::get_backup_email()); ?>" /> When an error occurs, send a message to this mail</td>
            </tr>
            <?php if(Checker::isPluginActive('woocommerce')): ?>
                <tr valign="top">
                    <th scope="row">Product find by</th>
                    <td>
                        <select id="product_find_by_select" name="<?php echo esc_html(Settings::getOptionName('product_find_by')); ?>">
                            <?php
                            $fields = ['DISABLE', 'NAME', 'EXTERNAL_ID' => 'EXTERNAL_ID (Recommended, if you have Flamix Products Sync plugin)', 'XML_ID' => 'SKU in Woo and XML_ID in B24', 'CUSTOM' => 'CUSTOM'];
                            foreach ($fields as $key => $name):
                                $value = (is_integer($key))? $name : $key;
                                ?>
                                <option value="<?php echo esc_html($value); ?>" <?php if($value == Handlers::getFindBy()) echo 'selected=selected'?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- FIND BY BLOCK -->
                        <br/>
                        <div id="product_find_by_wrap" style="margin-top: 20px; <?php if(Handlers::getFindBy() !== 'CUSTOM') echo 'display: none;'; ?>">
                            <div style="float: left; margin-right: 20px">
                                <label for="label_1">Bitrix24 Product Field Code<br/></label>
                                <input id="label_1" type="text" name="<?php echo esc_html(Settings::getOptionName('product_find_by_bitrix24_attribute')); ?>" placeholder="PROPERTY_000" value="<?php echo esc_html(Settings::getOption('product_find_by_bitrix24_attribute')); ?>" />
                            </div>
                            <div>
                                <label for="label_2">WP Product Field Code<br/></label>
                                <select name="<?php echo esc_html(Settings::getOptionName('product_find_by_woocommerce_attribute')); ?>" id="label_2">
                                    <?php
                                    $wc_attributes = Products::getAttributes();
                                    foreach ($wc_attributes as $wc_attribute_key => $wc_attribute_value):
                                        ?>
                                        <option value="<?php echo esc_html($wc_attribute_key); ?>" <?php if($wc_attribute_key == Settings::getOption('product_find_by_woocommerce_attribute')) echo 'selected=selected'?>><?php echo esc_html($wc_attribute_value); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- /FIND BY BLOCK -->
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
        </p>
    </form>

    <table style="width: 95%;">
        <tr class="form-field">
            <td style="width: 33%; vertical-align: top;"><?php include 'configuration.php'; ?></td>
            <td style="width: 33%; vertical-align: top;"><?php include 'recommendations.php'; ?></td>
            <td style="width: 33%; vertical-align: top; text-align: right;"><?php echo Recommendations::banner(FLAMIX_BITRIX24_WOOCOMMERCE_ORDER_CODE); ?></td>
        </tr>
    </table>

    <?php include 'queue.php'; ?>
</div>