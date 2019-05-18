<?php
/**
 * @author : Omair Afzal 
 **/

if (!defined('_PS_VERSION_'))
	exit;

class FastPay extends PaymentModule
{
	protected $_html = '';
	protected $_postErrors = array();

	public $details;
	public $owner;
	public $merchantphone;
	public $merchantpassword;
	public function __construct()
	{
		$this->name = 'fastpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Omair Afzal';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('FASTPAY_MERCHANT_NAME', 'FASTPAY_MERCHANT_PHONE', 'FASTPAY_MERCHANT_STORE_PASSWORD'));
		if (!empty($config['FASTPAY_MERCHANT_PHONE']))
			$this->merchantphone = $config['FASTPAY_MERCHANT_PHONE'];
		if (!empty($config['FASTPAY_MERCHANT_NAME']))
			$this->owner = $config['FASTPAY_MERCHANT_NAME'];
		if (!empty($config['FASTPAY_MERCHANT_STORE_PASSWORD']))
			$this->merchantpassword = $config['FASTPAY_MERCHANT_STORE_PASSWORD'];
		if (!empty($config['FAST_PAY_PAYMENT_URL']))
			$this->merchantpassword = $config['FAST_PAY_PAYMENT_URL'];


		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Fast Pay');
		$this->description = $this->l('Accept payments for your products via fast pay.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

		if (!isset($this->owner) || !isset($this->merchantphone) || !isset($this->merchantpassword))
			$this->warning = $this->l('Account owner and fast pay merchant details are required.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');

//		$this->extra_mail_vars = array(
//										'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
//										'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
//										'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
//										);
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('FASTPAY_MERCHANT_NAME')
				|| !Configuration::deleteByName('FASTPAY_MERCHANT_PHONE')
				|| !Configuration::deleteByName('FAST_PAY_PAYMENT_URL')
				|| !Configuration::deleteByName('FASTPAY_MERCHANT_STORE_PASSWORD')
				|| !parent::uninstall())
			return false;
		return true;
	}

	protected function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('FASTPAY_MERCHANT_PHONE'))
				$this->_postErrors[] = $this->l('Merchant Phone number is required.');
			elseif (!Tools::getValue('FASTPAY_MERCHANT_STORE_PASSWORD'))
				$this->_postErrors[] = $this->l('Merchant Store Password is required.');
			elseif (!Tools::getValue('FAST_PAY_PAYMENT_URL'))
			$this->_postErrors[] = $this->l('Fast Pay Payment Url is Required');	
		}
	}

	protected function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('FASTPAY_MERCHANT_PHONE', Tools::getValue('FASTPAY_MERCHANT_PHONE'));
			Configuration::updateValue('FASTPAY_MERCHANT_STORE_PASSWORD', Tools::getValue('FASTPAY_MERCHANT_STORE_PASSWORD'));
			Configuration::updateValue('FASTPAY_MERCHANT_NAME', Tools::getValue('FASTPAY_MERCHANT_NAME'));
				Configuration::updateValue('FAST_PAY_PAYMENT_URL', Tools::getValue('FAST_PAY_PAYMENT_URL'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Pay Fast Settings updated'));
	}

	protected function _displayFastPay()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';

		$this->_html .= $this->_displayFastPay();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;

		if (!$this->checkCurrency($params['cart']))
			return;

		$payment_options = array(
			'cta_text' => $this->l('Pay by Fast Pay'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/fastpay.jpg'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

// 		$state = $params['objOrder']->getCurrentState();
//		if (in_array($state, array(Configuration::get('PS_OS_BANKWIRE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		if (in_array($state, array(Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'fastpay_merchant_phone' => Tools::nl2br($this->merchantphone),
				'fastpay_merchant_password' => Tools::nl2br($this->merchantpassword),
				'fastpay_owner' => $this->owner,
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Merchant Account Details'),
					'icon' => 'icon-envelope'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Fast Pay Merchant Name'),
						'name' => 'FASTPAY_MERCHANT_NAME',
						'desc' => 'Fast Pay Merchant Name',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Fast Pay Merchant Phone Number'),
						'name' => 'FASTPAY_MERCHANT_PHONE',
						'desc' => $this->l('Payfast Merchant Name'),
						'required' => true
					),
					array(
						'type' => 'password',
						'label' => $this->l('Fast Pay Account Password'),
						'name' => 'FASTPAY_MERCHANT_STORE_PASSWORD',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Fast Pay Payment URL'),
						'desc' => $this->l('https://dev.fast-pay.cash |||   https://secure.fast-pay.cash'),
						'name' => 'FAST_PAY_PAYMENT_URL',
						'required' => true
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'FASTPAY_MERCHANT_NAME' => Tools::getValue('FASTPAY_MERCHANT_NAME', Configuration::get('FASTPAY_MERCHANT_NAME')),
			
			'FASTPAY_MERCHANT_PHONE' => Tools::getValue('FASTPAY_MERCHANT_PHONE',Configuration::get('FASTPAY_MERCHANT_PHONE')),
			
			'FAST_PAY_PAYMENT_URL' => Tools::getValue('FAST_PAY_PAYMENT_URL',Configuration::get('FAST_PAY_PAYMENT_URL')),
			
			'FASTPAY_MERCHANT_STORE_PASSWORD' => Tools::getValue('FASTPAY_MERCHANT_STORE_PASSWORD', Configuration::get('FASTPAY_MERCHANT_STORE_PASSWORD')),
		);
	}
}
