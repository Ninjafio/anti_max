<?php
namespace FlamixLocal\WooOrders\Woo;

use Flamix\Bitrix24\WooCommerce\Helpers;
use Flamix\Bitrix24\WooCommerce\Settings;
use WC_Order;

class Products {
    /**
     * Get all attribute (include Custom)
     *
     * @return array
     */
    public static function getAttributes(): array
    {
        $result = ['NAME' => 'NAME', 'SKU' => 'SKU'];

        $attribute_taxonomies = wc_get_attribute_taxonomies();
        if (empty($attribute_taxonomies))
            return $result;

        foreach ($attribute_taxonomies as $attribute_taxonomy)
            $result[$attribute_taxonomy->attribute_name] = $attribute_taxonomy->attribute_label;

        return $result;
    }

    public static function mergeProducts(int $order_id, array $crm_products): bool
    {
        $order = new WC_Order($order_id);
        // TODO: Add more fields (SKU, NAME)?
        // Witch field we will be used to find product
        $product_find_by_woocommerce_attribute = Settings::getOption('product_find_by_woocommerce_attribute');
        if (empty($product_find_by_woocommerce_attribute)) {
            Helpers::log('[STOP: Products orders compare] Because you didnt set product_find_by_woocommerce_attribute value!');
            return false;
        }

        $current_products_in_order = self::getOrderProductsInSimplyFormat($order); // Products already located in our order
        $search = array_column($current_products_in_order, 'ID'); // Index of our products in order (taken by ID)
        foreach ($crm_products as $crm_product) {
            $xml_id = sanitize_text_field($crm_product['XML_ID'] ?? false);
            $woo_id = self::getProductIdByAttribute($product_find_by_woocommerce_attribute, $xml_id);
            if (!$woo_id) {
                Helpers::log('[STOP: Products orders compare] Because we didnt find product by ' . $product_find_by_woocommerce_attribute . ' end value ' . $xml_id);
                return false;
            }

            $is_found_in_current_order = array_search($woo_id, $search);
            if ($is_found_in_current_order === false) {
                // Add new product and update price
                $woo_product_id_in_order = $order->add_product(wc_get_product($woo_id), (float)$crm_product['QUANTITY']);
                wc_update_order_item_meta($woo_product_id_in_order, '_line_total', (float)($crm_product['PRICE'] * $crm_product['QUANTITY']));
            } else {
                // Product found
                $current_product_in_order = $current_products_in_order[$is_found_in_current_order];

                // Product has different QUANTITY and we update it
                if ($crm_product['QUANTITY'] != $current_product_in_order['QUANTITY']) {
                    wc_update_order_item_meta($current_product_in_order['WOO_ORDER_ID'], '_qty', (float)$crm_product['QUANTITY']);
                    wc_update_order_item_meta($current_product_in_order['WOO_ORDER_ID'], '_line_subtotal', (float)$crm_product['PRICE']);
                    wc_update_order_item_meta($current_product_in_order['WOO_ORDER_ID'], '_line_total', (float)($crm_product['PRICE'] * $crm_product['QUANTITY']));
                }

                // Delete $search[$is_found_in_current_order] because we already finish work with this product
                // We will delete all product witch left in $current_products_in_order[$is_found_in_current_order]
                unset($search[$is_found_in_current_order], $current_products_in_order[$is_found_in_current_order]);
            }
        }

        // Delete products witch we didn't add to CRM
        if (!empty($current_products_in_order)) {
            self::deleteProductsInOrder($current_products_in_order, 'WOO_ORDER_ID');
        }

        // Update and save total
        self::recalculate($order_id);

        return true;
    }

    /**
     * Get Product ID by attribute
     *
     * Example: Get Product ID by key EXTERNAL_ID and value 29be66891c37b4d933385786918a4663
     *
     * @param string $key
     * @param string $value
     * @return int
     */
    private static function getProductIdByAttribute(string $key, string $value): int
    {
        global $wpdb;
        return (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT posts.ID
                        FROM {$wpdb->posts} as posts
                        INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
                        WHERE
                              posts.post_type IN ('product', 'product_variation')
                              AND posts.post_status != 'trash'
                              AND postmeta.meta_key = %s
                              AND postmeta.meta_value = %s
                              LIMIT 1",
                $key,
                $value
            )
        );
    }

    /**
     * Get all orders product in simply format
     *
     * We don't need more data
     *
     * @param WC_Order $order
     * @return array
     */
    private static function getOrderProductsInSimplyFormat(WC_Order $order): array
    {
        $products = $order->get_items();

        $woo_products = [];
        foreach ($products as $product) {
            $woo_products[] = [
                'ID' => $product->get_product_id(),
                'QUANTITY' => $product->get_quantity(),
                'WOO_ORDER_ID' => $product->get_id(),
            ];
        }

        return $woo_products;
    }

    /**
     * Delete products in orders
     * Product will be found by $key
     *
     * @param array $products
     * @param string $key
     * @return bool
     * @throws \Exception
     */
    private static function deleteProductsInOrder(array $products, string $key): bool
    {
        foreach ($products as $product) {
            if ($product[$key]) {
                wc_delete_order_item($product[$key]);
            }
        }

        return true;
    }

    /**
     * Update total value
     *
     * @param int $order_id
     * @return void
     */
    private static function reCalculate(int $order_id): void
    {
        $order = new WC_Order($order_id);
        $order->calculate_totals();
        $order->save();
    }
}