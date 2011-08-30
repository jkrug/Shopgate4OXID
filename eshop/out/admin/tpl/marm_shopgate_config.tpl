[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{ if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="marm_shopgate_config">
    <input type="hidden" name="fnc" value="">
    <input type="hidden" name="actshop" value="[{$oViewConf->getActiveShopId()}]">
    <input type="hidden" name="updatenav" value="">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" method="post">
[{ $oViewConf->getHiddenSid() }]
<input type="hidden" name="cl" value="marm_shopgate_config">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="editval[oxshops__oxid]" value="[{ $oxid }]">

    <div class="groupExp"><div class="exp">
      [{foreach from=$oView->getShopgateConfig() item='aConfigItem'}]
        <dl>
            <dt>
                [{if $aConfigItem.type == 'checkbox'}]
                <input type=hidden name=confbools[[{$aConfigItem.oxid_name}]] value=false>
                <input type=checkbox name=confbools[[{$aConfigItem.oxid_name}]] value=true [{if ($aConfigItem.value)}]checked[{/if}] [{ $readonly}]>
                [{else}][{*  if $aConfigItem.type == 'input'  *}]
                <input type=text class="txt" name=confstrs[[{$aConfigItem.oxid_name}]] value="[{$aConfigItem.value}]" [{ $readonly}]>
                [{/if}]
                [{ oxinputhelp ident="MARM_SHOPGATE_CONFIG_"|cat:$aConfigItem.shopgate_name|upper|cat:"_HELP" }]
            </dt>
                <dd>
                [{ oxmultilang ident="MARM_SHOPGATE_CONFIG_"|cat:$aConfigItem.shopgate_name|upper noerror=1 alternative=$aConfigItem.shopgate_name }]
            </dd>
                <div class="spacer"></div>
        </dl>
      [{/foreach}]
    </div></div>


    <br>

    <input type="submit" name="save" value="[{ oxmultilang ident="GENERAL_SAVE" }]" onClick="Javascript:document.myedit.fnc.value='save'" [{ $readonly}]>

</form>

[{include file="bottomnaviitem.tpl"}]

[{include file="bottomitem.tpl"}]
