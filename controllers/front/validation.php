<?php
/**
 * @author : Omair Afzal 
 **/
class FastPayValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
		  //  	print_r($module['name']) ; die();
			if ($module['name'] == 'ps_wirepayment')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l( 'fdf', 'validation'));

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(false, Cart::BOTH);
	
		$mailVars = array(
			'{bankwire_owner}' => 'xxxxxx',
			'{bankwire_details}' => 'xxxxxxxxx',
			'{bankwire_address}' => 'xxxxxxxx'
		);
    	$this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
	
        $post_data = array();
        $post_data['merchant_mobile_no'] = Configuration::get('FASTPAY_MERCHANT_PHONE');
        $post_data['store_password'] = Configuration::get('FASTPAY_MERCHANT_STORE_PASSWORD');
        $post_data['order_id'] = $this->module->currentOrder;
        $post_data['bill_amount'] = $total;
        $post_data['success_url'] =  'https://lellia.com/en/order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        $post_data['fail_url'] = "https://lellia.com";
        $post_data['cancel_url'] = "https://lellia.com";
        $payment_base_url = Configuration::get('FAST_PAY_PAYMENT_URL');
        $direct_api_url = $payment_base_url."/merchant/generate-payment-token";
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url );
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_POST, 1 );
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($handle );
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if($code == 200 && !( curl_errno($handle))) {
        	curl_close( $handle);
        	$response = $content;
        } else {
        	curl_close( $handle);
         echo '<pre>';	echo 'Failed to Connect to Pay Fast API. ' ; echo '<pre>'; exit(200);
        }
        $decodedResponse = json_decode($response, true );
        		if($decodedResponse['code'] != 200){
        		    echo '<pre>';	var_dump($decodedResponse);echo '<pre>'; exit(200);
        		}
        header("Location: ".$payment_base_url."/merchant/payment?token=".$decodedResponse['token']);
        	}
}
