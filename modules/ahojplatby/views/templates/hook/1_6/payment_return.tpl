{**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

{if $status == 'ok'}
    <p>
      {l s='PLATBA SCHVÁLENÁ' mod='ahojplatby'}<br/>
    </p>

    <p>
      {l s='Potvrdzujeme Vašu objednávku a odosielame tovar. Ďakujeme za nákup.' mod='ahojplatby'}<br/>
    </p>
{else}
    <p class="warning">
      <p>
        {l s='PLATBA ZRUŠENÁ' mod='ahojplatby'}<br/>
      </p>
      
      {l s='Platba' mod='ahojplatby'} <b>{$payment}</b> {l s='bola zrušená. Na zaplatenie objednávky je potrebné zvoliť iný spôsob platby, kontaktujte e-shop. Ďakujeme.' mod='ahojplatby'}
    </p>
{/if}
