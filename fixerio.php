<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class FixerIo
 */
class FixerIo extends CurrencyRateModule
{
    protected $currencyCache = [];

    /**
     * FixerIo constructor.
     */
    public function __construct()
    {
        $this->name = 'fixerio';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Thirty Bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Fixer.io');
        $this->description = $this->l('Provides currency exchange rates from fixer.io. Source: European Central Bank');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        $this->registerHook('currencyRates');
        $this->registerHook('rate');

        return true;
    }

    /**
     * @param string $baseCurrency Uppercase base currency code
     *                             Only codes that have been added to the
     *                             `supportedCurrencies` array will be called.
     *                             The module will have to accept all currencies
     *                             from that array as a base.
     *
     * @return false|array Associate array with all supported currency codes as key (uppercase) and the actual
     *                     amounts as values (floats - be as accurate as you like), e.g.:
     *                     ```php
     *                     [
     *                         'EUR' => 1.233434,
     *                         'USD' => 1.343,
     *                     ]
     *                     ```
     *                     Returns `false`  if there were problems with retrieving the exchange rates
     *
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hookCurrencyRates($baseCurrency)
    {
        if (array_key_exists($baseCurrency, $this->currencyCache)) {
            return $this->currencyCache[$baseCurrency];
        }

        $guzzle = new GuzzleHttp\Client();
        try {
            $json = (string) $guzzle->get('http://api.fixer.io/latest')->getBody();
            $exchangeRates = json_decode($json, true);
            if (!array_key_exists('rates', $exchangeRates)) {
                return false;
            }

            $this->currencyCache[$baseCurrency] = $exchangeRates['rates'];

            return $exchangeRates['rates'];
        } catch (Exception $e) {
            return false;
        }


    }

    /**
     * Provide a single exchange rate
     *
     * @param string $from
     * @param string $to
     *
     * @return false|float Returns the rate, false if not found
     */
    public function hookRate($from, $to)
    {
        $exchangeRates = $this->hookCurrencyRates($from);

        if (array_key_exists($to, $exchangeRates)) {
            return (float) $exchangeRates[$to];
        }

        return false;
    }

    /**
     * @return array Supported currencies
     *               An array with uppercase currency codes (ISO 4217)
     */
    public function getSupportedCurrencies()
    {
        return ['EUR', 'USD'];
    }
}
