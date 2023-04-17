<?use Bitrix\Main\Localization\Loc;
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<?=bitrix_sessid_post()?>

	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
	<input type="hidden" name ="id" value ="wkopt">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name ="step" value ="2">
	<p><input type="checkbox" name="deletedata" id="deletedata" value="Y" checked><label for="deletedata"><?echo GetMessage('WKOpt_DELETE_TABLES');?></label></p>
	<input type="submit" name="inst" value="<?echo GetMessage('WKOpt_DELETE_BTN_TITLE');?>">
</form>