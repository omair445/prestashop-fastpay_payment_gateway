{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='fastpay'}">{l s='Checkout' mod='fastpay'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='fastpay payment' mod='fastpay'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='fastpay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='fastpay'}</p>
{else}

<h3>{l s='fastpay payment' mod='fastpay'}</h3>
<form action="{$link->getModuleLink('fastpay', 'validation', [], true)|escape:'html'}" method="post">
<p>
	<img src="data:image/jpeg;base64,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" alt="{l s='fastpay' mod='fastpay'}" width="86" height="55" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='You have chosen to pay by Fast Pay.' mod='fastpay'}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='fastpay'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='fastpay'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='fastpay'}
    {/if}
</p>
<p>
	-
	{{!--{if $currencies|@count > 1}--}}
	{{!--	{l s='We allow several currencies to be sent via bank wire.' mod='bankwire'}--}}
	{{!--	<br /><br />--}}
	{{!--	{l s='Choose one of the following:' mod='bankwire'}--}}
	{{!--	<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">--}}
	{{!--		{foreach from=$currencies item=currency}--}}
	{{!--			<option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>--}}
	{{!--		{/foreach}--}}
	{{!--	</select>--}}
	{{!--{else}--}}
	{{!--	{l s='We allow the following currency to be sent via bank wire:' mod='bankwire'}&nbsp;<b>{$currencies.0.name}</b>--}}
	{{!--	<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />--}}
	{{!--{/if}--}}
</p>
<p>
	{l s='Fast Pay account information will be displayed on the next page.' mod='fastpay'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order".' mod='fastpay'}</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='fastpay'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='fastpay'}</a>
</p>
</form>
{/if}
