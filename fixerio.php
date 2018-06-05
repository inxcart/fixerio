<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
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
        $this->tab = 'administration';
        $this->version = '1.0.2';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Fixer.io Module');
        $this->description = $this->l('Provides currency exchange rates from fixer.io. Source: European Central Bank.');
        $this->tb_versions_compliancy = '> 1.0.0';
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        $this->registerHook('actionRetrieveCurrencyRates');

        return true;
    }

    /**
     * @param array $params It contains the following values:
     *                      - `currencies`: `array` of `string`s
     *                        Uppercase currency codes
     *                        Only codes that have been added to the
     *                        `currencies` array should be filled.
     *                        The module will have to accept all the currencies it provides
     *                        as a base currency, too. So if it provides `EUR` and `USD`, it should be able to calculate
     *                        with both `EUR` or `USD` as a base currency and find the exchange rate for the other.
     *                      - `baseCurrency`: `string`
     *                        Uppercase base currency code
     *
     * @return false|array Associate array with all supported and requested currency codes as key (uppercase) and the actual
     *                     amounts as values (floats - be as accurate as you like), e.g.:
     *                     ```php
     *                     [
     *                         'EUR' => 1.233434,
     *                         'USD' => 1.343,
     *                     ]
     *                     ```
     *                     Sets a currency as `false` if there were problems with retrieving the exchange rates.
     *                     This will cause thirty bees to not further process the currency. As of 1.0.x thirty bees will not request
     *                     other modules to provide the missing rates. This might change in the future.
     *
     * @since 1.0.1 Introduced as a replacement for `hookCurrencyRates`. All action modules should be prefixed with `action`
     */
    public function hookActionRetrieveCurrencyRates($params)
    {
        $guzzle = new GuzzleHttp\Client();
        try {
            $json = (string) $guzzle->get("http://api.fixer.io/latest?base={$params['baseCurrency']}")->getBody();
            $exchangeRates = json_decode($json, true);
            if (!array_key_exists('rates', $exchangeRates)) {
                return false;
            }

            foreach (array_keys($exchangeRates['rates']) as $currency) {
                if (!in_array($currency, $params['currencies'])) {
                    unset($exchangeRates['rates'][$currency]);
                }
            }

            return $exchangeRates['rates'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return array Supported currencies
     *               An array with uppercase currency codes (ISO 4217)
     */
    public function getSupportedCurrencies()
    {
        return ['AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'IDR', 'ILS', 'INR', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR'];
    }
}
