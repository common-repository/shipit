<?php

// orders


function sendOrderOrShipment($automatic_delivery, $orderPayload, $shipit, $order) {
    if ( $automatic_delivery === false ) {
      $integration = new Integration(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token']);
      return $integration->massiveOrders(['orders' => [$orderPayload->build()]]);
    } elseif ($shipit) {
        $order = new WC_Order($order->get_id());
        $order->add_order_note('Ya que aplicaron el metodo de retiro en tienda.');
    } else {
        $core = new Core(get_option('shipit_user')['shipit_user'], get_option('shipit_user')['shipit_token'], 'v4');
        return $core->shipments(['shipment' => $orderPayload->build()]);
    }
  }

  function create_order($order, $parcel, $company, $inventory, $communeName, $destinyId, $productCategories, $insuranceSetting) {
    $shipitUser = get_option('shipit_user');
    $opit = new Opit($shipitUser['shipit_user'], $shipitUser['shipit_token']);
    $shipit = getDeliveryType($order);
    $opitSetting = $opit->setting();
    
    $address = split_street($order->get_shipping_address_1());
    $destiny = create_destiny($order, $address, $destinyId, $communeName, $shipit);
    $seller = create_seller($order);
    $courier = create_courier($order, $opitSetting);
    $price = create_price($order);
    $payment = create_payment($order);
    $measure = create_measure($parcel);
    $measure->setCubicationId($parcel['cubication_id']);
    $insurance = create_insurance($order, $productCategories, $insuranceSetting);

    return new Order(
        $company->id,
        '#' . $order->get_id(),
        $order->get_item_count(),
        $company->service->name,
        false,
        ($shipit ? 2 : 1),
        $destiny->getDestiny(),
        $seller->getSeller(),
        $inventory,
        $courier->getCourier(),
        $price->getPrice(),
        $payment->getPayment(),
        $measure->getMeasure(),
        $insurance->getInsurance()
    );
}

    function create_destiny($order, $address, $destinyId, $communeName, $shipit) {
        return new Destiny(
            $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            $order->get_billing_phone(),
            $order->get_billing_email(),
            $address['street'] !== '' ? $address['street'] : $order->get_shipping_address_1(),
            $address['number'],
            isset($address['numberAddition']) && $address['numberAddition'] !== '' ? $address['numberAddition'] . '/' . $order->get_shipping_address_2() : $order->get_shipping_address_2(),
            $destinyId,
            $communeName,
            $shipit ? 'shopping_retired' : 'home_delivery',
            $order->get_billing_postcode()
        );
    }

    function create_seller($order) {
        return new Seller(
            $order->get_id(),
            $order->get_date_created(),
            get_site_url(),
            $order->get_status()
        );
    }

    function create_courier($order, $opitSetting) {
        $courierName = $order->get_shipping_method() === 'Despacho normal a domicilio' ? '' : $order->get_shipping_method();
        return new Courier(
            $courierName,
            $courierName,
            $opitSetting->algorithm,
            $opitSetting->algorithm_days,
            false
        );
    }

    function create_price($order) {
        return new Price(
            (int) $order->get_shipping_total(),
            (int) $order->get_shipping_total(),
            0,
            (int) $order->get_cart_tax(),
            0
        );
    }

    function create_payment($order) {
        return new Payment(
            (int) $order->get_total(),
            0,
            0,
            '',
            0,
            0,
            '',
            false
        );
    }

    function create_measure($parcel) {
        return new Measure(
            $parcel['height'],
            $parcel['width'],
            $parcel['length'],
            $parcel['weight'],
            $parcel['quantity'],
            $parcel['cubication_id']
        );
    }

    function create_insurance($order, $productCategories, $insuranceSetting) {
        $insuredAmount = (int) $order->get_total() - (int) $order->get_shipping_total();
        return new Insurance(
            $insuredAmount,
            $order->get_id(),
            ltrim($productCategories),
            $insuranceSetting->active && $insuredAmount > $insuranceSetting->amount
        );
    }
