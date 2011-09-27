[{assign var="edit" value=$oView->getArticle()}]
[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]
[{ if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]


<script type="text/javascript">
<!--
window.onload = function ()
{
    [{ if $updatelist == 1}]
        top.oxid.admin.updateList('[{ $oxid }]');
    [{ /if}]
    top.reloadEditFrame();
}
function editThis( sID )
{
    var oTransfer = top.basefrm.edit.document.getElementById( "transfer" );
    oTransfer.oxid.value = sID;
    oTransfer.cl.value = top.basefrm.list.sDefClass;

    //forcing edit frame to reload after submit
    top.forceReloadingEditFrame();

    var oSearch = top.basefrm.list.document.getElementById( "search" );
    oSearch.oxid.value = sID;
    oSearch.actedit.value = 0;
    oSearch.submit();
}
//-->
</script>

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="marm_shopgate_article">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="marm_shopgate_article">
<input type="hidden" name="fnc" value="">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[article__oxid]" value="[{ $oxid }]">



  <table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
    <tr height="10">
      <td></td><td></td>
    </tr>
    <tr>
      <td width="15"></td>
      <td valign="top" class="edittext">

        <table cellspacing="0" cellpadding="0" border="0">
          <tr>
            <td class="edittext">
              [{ oxmultilang ident="MARM_SHOPGATE_ARTICLE_MARKETPLACE" }]
            </td>
            <td class="edittext">
                <input class="edittext" type="hidden" name="editval[oxarticles__marm_shopgate_marketplace]" value='0'>
                <input class="edittext" type="checkbox" name="editval[oxarticles__marm_shopgate_marketplace]" value='1' [{if $edit->oxarticles__marm_shopgate_marketplace->value == 1}]checked[{/if}] [{ $readonly }]>
                [{ oxinputhelp ident="MARM_SHOPGATE_ARTICLE_MARKETPLACE_HELP" }]
            </td>
          </tr>
            <tr>
              <td class="edittext"></td>
              <td class="edittext">
                <input type="submit" class="edittext" name="save" value="[{ oxmultilang ident="GENERAL_SAVE" }]" onClick="Javascript:document.myedit.fnc.value='save'"" ><br>
              </td>
            </tr>
        </table>

      </td>

      <td valign="top" class="edittext" align="left" width="55%" style="table-layout:fixed">

      </td>
    </tr>
  </table>


</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
