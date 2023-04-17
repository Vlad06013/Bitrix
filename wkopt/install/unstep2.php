<?if(!check_bitrix_sessid()) return;?>
<?
global $errors;

if(empty($errors)):
	echo CAdminMessage::ShowNote(GetMessage("WKOpt_DELETE_ACTION_SUCCSESS"));
else:
	for($i=0; $i<count($errors); $i++)
		$alErrors .= $errors[$i]."<br>";
	echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("WKOpt_DELETE_ACTION_ERROR"), "DETAILS"=>$alErrors, "HTML"=>true));
endif;
if ($ex = $APPLICATION->GetException())
{
	echo CAdminMessage::ShowMessage(Array("TYPE" => "ERROR", "MESSAGE" => GetMessage("WKOpt_DELETE_ACTION_ERROR"), "HTML" => true, "DETAILS" => $ex->GetString()));
}
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("WKOpt_BACK_MAIN_PAGE")?>">	
<form>