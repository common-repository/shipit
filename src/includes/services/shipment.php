<?php

// shipment  

function createShipment($company, $skus, $sellerSetting, $insuranceSetting, $order, $destinyId = null) {
    $measuresCollection = new MeasureCollection();
    $helper = new WoocommerceSettingHelper(get_option('woocommerce_weight_unit'), get_option('woocommerce_dimension_unit'));
    $measureConversion = $helper->getMeasureConversion();
    $weightConversion = $helper->getWeightConversion();
    $settings = get_option('woocommerce_shipit_settings');
    $communeName = getCommuneName($order);
    $inventory = getInventoryAndProductCategories($order, $skus, $helper, $measureConversion, $weightConversion, $measuresCollection, $settings);
    $parcel = $measuresCollection->calculate();
    $orderPayload = create_order($order, $parcel, $company, $inventory['items'], $communeName, $destinyId, $inventory['categories'], $insuranceSetting);

    $courier_name = $order->get_shipping_method() == 'Despacho normal a domicilio' ? '' : $order->get_shipping_method();
    $same_day = Courier::sameDayCourier($courier_name, get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');

    $responseSendOrder = sendOrderOrShipment($same_day ? $sellerSetting->same_day->automatic_delivery : $sellerSetting->automatic_delivery, $orderPayload, getDeliveryType($order), $order);

    $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
    $core->mixPanelLogger('envio importacion automatica', mixPanelObject($sellerSetting, $responseSendOrder, $orderPayload, $order));

    return $responseSendOrder;
}

function getCommuneName($order) {
    $country = $order->get_shipping_country();
    $state = $order->get_shipping_state();
    return WC()->countries->get_states($country)[$state];
}

function getInventoryAndProductCategories($order, $skus, $helper, $measureConversion, $weightConversion, $measuresCollection, $settings) {
    $inventory = [];
    $productCategories = "";

    foreach ($order->get_items() as $cartItem) {
        $product = getProduct($cartItem);
        $terms = get_the_terms($product->get_id(), 'product_cat');

        foreach ($terms as $term) {
            $productCategories .= ' ' . $term->slug;
        }

        if (!empty($skus)) {
            $sku = $product->get_sku() != '' ? $product->get_sku() : $product->get_id();
            foreach ($skus as $skuObject) {
                if (strtolower($skuObject['name']) == strtolower($sku)) {
                    $inventory[] = [
                        'sku_id' => $skuObject['id'],
                        'amount' => $cartItem['qty'],
                        'description' => $skuObject['description'],
                        'warehouse_id' => $skuObject['warehouse_id']
                    ];
                    addMeasureToCollection($measuresCollection, $skuObject, $cartItem['qty']);
                }
            }
        } else {
            addProductMeasureToCollection($measuresCollection, $product, $helper, $measureConversion, $weightConversion, $cartItem['quantity'], $settings);
        }
    }

    return ['items' => $inventory, 'categories' => ltrim($productCategories)];
}

function addMeasureToCollection($measuresCollection, $skuObject, $quantity) {
    $parcel = $measuresCollection->calculate();
    $measure = new Measure(
        (float)$skuObject['height'],
        (float)$skuObject['width'],
        (float)$skuObject['length'],
        (float)$skuObject['weight'],
        (int)$quantity,
        (int)$parcel['cubication_id'],
    );
    $measuresCollection->setMeasures($measure->buildBoxifyRequest());
}

function addProductMeasureToCollection($measuresCollection, $product, $helper, $measureConversion, $weightConversion, $quantity, $settings) {
    $height = $helper->packingSetting($product->get_height(), $settings, 'height', 'packing_set', $measureConversion);
    $width = $helper->packingSetting($product->get_width(), $settings, 'width', 'packing_set', $measureConversion);
    $length = $helper->packingSetting($product->get_length(), $settings, 'length', 'packing_set', $measureConversion);
    $weight = $helper->packingSetting($product->get_weight(), $settings, 'weight', 'weight_set', $weightConversion);
    ShipitDebug::debug('height: ' . $height . ' width: ' . $width . ' length: ' . $length . ' weight: ' . $weight . ' quantity: ' . $quantity);

    $parcel = $measuresCollection->calculate();
    $measure = new Measure(
        (float)$height,
        (float)$width,
        (float)$length,
        (float)$weight,
        (int)$quantity,
        (int)$parcel['cubication_id'],
    );
    $measuresCollection->setMeasures($measure->buildBoxifyRequest());
}


