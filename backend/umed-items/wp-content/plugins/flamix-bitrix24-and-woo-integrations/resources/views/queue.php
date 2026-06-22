<h2>Orders Queue</h2>
<?php if (!count($queue_not_sent)):?>
    <p>Queue is empty</p>
<?php else: ?>
    <ul id="queue_list">
        <?php foreach ($queue_not_sent as $order): $woo_order = wc_get_order($order->order_id);?>
            <li>
                <a href="/wp-admin/post.php?post=<?= $order->order_id; ?>&action=edit" target="_blank">#<?= $order->order_id; ?></a>
                <?php
                    // Error messages
                    if (!$woo_order) {
                        echo "Order #{$order->order_id} does not exist! Please remove it from the queue!";
                    }

                    if ($woo_order && !$woo_order->get_item_count()) {
                        echo "Order #{$order->order_id} does not contain any products and cannot be sent!";
                    }
                ?>
            </li>
        <?php endforeach;?>
    </ul>

    <input id="flamix_b24_woo_clear_queue" type="submit" class="button-primary" value="<?php esc_html_e('Clear Queue', 'flamix-bitrix24-and-woo-integrations'); ?>" />
<?php endif;?>

<table class="form-table">
    <tr class="form-field">
        <th>
            <label for="order_id"><?php esc_html_e('Manually resend unsent orders', 'flamix-bitrix24-and-woo-integrations'); ?></label>
        </th>
        <td>
            <input type="text" name="order_id" id="order_id" value="" placeholder="<?php esc_html_e('Add an order to the dispatch queue', 'flamix-bitrix24-and-woo-integrations'); ?>">
        </td>
        <td>
            <input id="flamix_b24_woo_dispatch_order" type="submit" class="button-primary" value="<?php esc_html_e('Add', 'flamix-bitrix24-and-woo-integrations'); ?>" />
        </td>
    </tr>
</table>


<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add order to the queue
        $("#flamix_b24_woo_dispatch_order").click(function() {
            if ($(this).hasClass('disabled')) {
                console.log('Button is disabled');
                return;
            }

            let order_id = parseInt($('#order_id').val());
            console.log('Ajax sending', order_id);
            if (order_id > 0) {
                $(this).addClass('disabled');
                flamix_b24_woo_dispatch_order(order_id);
            }
        });

        // Clear queue
        $("#flamix_b24_woo_clear_queue").click(function() {
            console.log('Clearing queue');
            $(this).addClass('disabled');
            $("#queue_list").hide();
            flamix_b24_woo_clear_queue();
        });
    });

    // Function to dispatch order
    function flamix_b24_woo_dispatch_order(order_id) {
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            'action': 'flamix_b24_woo_dispatch_order',
            'order_id': parseInt(order_id)
        }, function(response) {
            alert(response);
            jQuery("#flamix_b24_woo_dispatch_order").removeClass('disabled');
            jQuery('#order_id').val('');
        });
    }

    // Function to clear queue
    function flamix_b24_woo_clear_queue() {
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            'action': 'flamix_b24_woo_clear_queue'
        }, function() {
            jQuery("#flamix_b24_woo_clear_queue").removeClass('disabled');
        });
    }
</script>