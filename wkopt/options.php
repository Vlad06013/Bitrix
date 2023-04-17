<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");


use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use \Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;
use Bitrix\wkopt;
Loader::includeModule('wkopt');
    $module_id = "wkopt";


$table_id = "wk_spec_delivery_timelist";

$aTabs = array(
    array("DIV" => "edit1", "TAB" =>"Настройки времени доставки", "ICON"=>"main_user_edit", "TITLE"=>"Настройки времени доставки"),
    array("DIV" => "edit2", "TAB" => "Права доступа", "ICON"=>"main_user_edit", "TITLE"=>"Права доступа"),
    array("DIV" => "edit3", "TAB" => "Выбор каталога товаров", "ICON"=>"main_user_edit", "TITLE"=>"Выбор каталога товаров"),
  );
  $tabControl = new CAdminTabControl("tabControl", $aTabs);
?>

<?
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
    if($_POST["ACTION"]["DELETE"]){
        foreach ($_POST["ACTION"]["DELETE"] as $key => $value) {
            Bitrix\wkopt\WKOptSpecification::DeleteItem('wk_spec_delivery_timelist', htmlspecialcharsbx($key));
        }
    }
    if($_POST["ACTION"]["DELETE_GROUP"]){
        foreach ($_POST["ACTION"]["DELETE_GROUP"] as $key => $value) {
            Bitrix\wkopt\WKOptSpecification::DeleteItem('wk_group_rights', htmlspecialcharsbx($key));
        }
    }

    if($_POST['GROUP']){
        foreach ($_POST['GROUP']["ITEMS"] as $key => $groupeId) {
            $arDBGroups[$groupeId]= array(
                'ID' =>$groupeId,
                'RIGHTS' =>$_POST['GROUP']["RIGHT"][$key]
            );
            $newValue=array(
                'GROUP_ID'=>$groupeId,
                'RIGHTS'=>$_POST['GROUP']["RIGHT"][$key]
            );
            $result =  Bitrix\wkopt\WKOptSpecification::UpdateItem("wk_group_rights",$key,$newValue);
        }
        if($_POST['GROUP']["AddNewGroup"]){
            foreach ($_POST['GROUP']["AddNewGroup"] as $key => $groupeId) {
                if((!array_key_exists($key, $_POST['ACTION']["DELETE_GROUP"]))&&($arDBGroups[$groupeId]["RIGHTS"] != $_POST['GROUP']["AddNewRights"][$key] )){
                    $newValue=array(
                        'GROUP_ID'=>$groupeId,
                        'RIGHTS'=>$_POST['GROUP']["AddNewRights"][$key]
                    );
                    $result =  Bitrix\wkopt\WKOptSpecification::AddNewItem("wk_group_rights",$newValue);
                }
            }
        }
    }

    COption::SetOptionInt($module_id,"catalogID",$_POST['catalogID']);

    if($_POST["arTimes"]){
        $arErrors=array();
        foreach ($_POST["arTimes"] as $key => $values) {
            if($key == 'ADD_NEW'){
                foreach ($values as $newId => $newValue) {
                    if(!array_key_exists($newId, $_POST['ACTION']["DELETE"])){
                        $result =  Bitrix\wkopt\WKOptSpecification::AddNewItem("wk_spec_delivery_timelist",$newValue);
                        if(!$result->isSuccess()){
                            $arErrors = $result->getErrorMessages();
                        }
                    }
                }
            }else{
                $result = Bitrix\wkopt\WKOptSpecification::UpdateItem("wk_spec_delivery_timelist", $key,$values);
                if(!$result->isSuccess()){
                    $arErrors= $result->getErrorMessages();
                }
            }
        }
    }
    if(!$arErrors){
        echo \CAdminMessage::ShowNote(Loc::getMessage("SUCCSESS"));
    }else{
        $arFields = array("TIME_TO","TIME_FROM");
        $nameFields = array(Loc::getMessage("TIME_TO"),Loc::getMessage("TIME_FROM"));
        foreach ($arErrors as $key => $error) {
            $newphrase = str_replace($arFields, $nameFields, $error);
            echo \CAdminMessage::ShowMessage($newphrase);
        }
    }
}
if (\Bitrix\wkopt\WKOptSpecification::userHaveModuleSettingsAccsess()){

    $timeList = \Bitrix\wkopt\WKOptSpecification::getAllItems('wk_spec_delivery_timelist');
    $gropesRights =  \wkopt::GetUsersGroupRights();
    $gropesRightsItems = \wkopt::GetRightsList();

    ?>

        <?$tabControl->Begin();?>
        <form class = "timeListForm" method="POST" Action="<?echo $APPLICATION->GetCurPage()?>?lang=ru&mid=wkopt&mid_menu=1" ENCTYPE="multipart/form-data" name="post_form">

        <?$tabControl->BeginNextTab();?>
            <th class = "header-time-params">
                <p>Настройки времени доставки</p>
            </th>
            <th class = "headers-delete">
                <p>Удалить</p>
            </th>
            <?if($timeList){?>
                <?foreach ($timeList as $id => $value) {?>
                    <tr>
                        <td class = "time-row">
                            <p>С</p>
                            <input type="text" name="arTimes[<?=$value['ID']?>][TIME_FROM]" value = "<?=$value["TIME_FROM"]?>" require>
                            <p> По </p>
                            <input type="text" name="arTimes[<?=$value['ID']?>][TIME_TO]" value = "<?=$value["TIME_TO"]?>" require>
                        </td>
                        <td class ="delete">
                            <input  type="checkbox"  name= "ACTION[DELETE][<?=$value['ID']?>]" value = "Y">
                        </td>
                    </tr>
                <?}?>

            <?}?>
            <tr class = "btn-add-new">
                <td>
                    <input id = "new-time" type="submit"  name="ACTION[ADD_NEW]" value = "Добавить новое время">
                </td>
            </tr>

    <?$tabControl->BeginNextTab();?>
    <?if($gropesRights){?>
        <th class = "header-groups-params">
            <p>Группа пользователей</p>
        </th>
        <th class = "header-rights-params">
            <p>Права доступа</p>
        </th>
        <th class = "headers-groups-delete">
            <p>Удалить</p>
        </th>
        <?$i=0;?>

            <?foreach ($gropesRights["VALS_DB"] as $key => $value) {?>
                <tr class = "group-row">
                    <td>
                        <? echo SelectBoxFromArray('GROUP[ITEMS]['.$value['ID'].']', $gropesRights["SELECT_BOX"],$value["GROUP_ID"], "Выбор", "");?>
                    </td>
                    <td>
                        <?echo SelectBoxFromArray('GROUP[RIGHT]['.$value['ID'].']', $gropesRightsItems["SELECT_BOX"],$value["RIGHTS"], "Выбор", "");?>
                    </td>
                    <td class ="delete">
                        <input  type="checkbox"  name= "ACTION[DELETE_GROUP][<?=$value['ID']?>]" value = "Y">
                    </td>
                    <?$i++;?>
                </tr>

            <?}?>
            <?}?>
    <tr class = "btn-add-new-group">
        <td>
            <input id = "new-group" type="submit"  name="ACTION[ADD_NEW_GROUP]" value = "Добавить новую группу">
        </td>
    </tr>

    <?$tabControl->BeginNextTab();?>
    <?

    $res = CIBlock::GetList(
        Array(),
        Array(),
        true
    );
    while($ar_res = $res->Fetch())
    {
        $arIblocks["reference"][]= "[".$ar_res["ID"]."] ".$ar_res['NAME'];
        $arIblocks["reference_id"][]= $ar_res["ID"];
    }
    $catalogId = COption::GetOptionInt($module_id,"catalogID");

    ?>
    <p>Выбор инфоблока каталога</p>
    <?echo SelectBoxFromArray("catalogID", $arIblocks, $catalogId, "Выбор", "");?>

    <?$tabControl->Buttons();?>

    <input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
    <?=bitrix_sessid_post();?>


    <?$tabControl->End();?><?
    ?><style>
        .timeListForm table {
            width:unset;
        }
        .header-time-params {
            text-align:center;
        }
        .headers-delete{
            text-align:center;
        }
        .delete{
            text-align:center;
        }
        .btn-add-new{
            text-align:center;
        }
        .time-row{
            display: flex;
        }
        .time-row p{
            padding:0 10px;
        }
    </style>
    </form>

    <script>
        class AddTime
        {
            constructor (){
            this.i=0
                this.addBtn = document.getElementById("new-time");
                this.addBtn.onclick =(e)=>{this.AddNewTime();}
            }
            AddNewTime(){
                let id = 'new_'+this.i;
                event.preventDefault();
                // let parentBlock = document.querySelector(".timeListForm tbody");
                let parentBlock = document.querySelector("#edit1_edit_table tbody");
                

                let blockAddBtn = document.querySelector(".btn-add-new");

                let tr = document.createElement('tr');
                parentBlock.insertBefore(tr, blockAddBtn);

                let block1 = document.createElement('td');
                block1.className = 'time-row';
                tr.appendChild(block1);


                let titleFrom = document.createElement('p');
                titleFrom.innerText = 'С ';
                block1.appendChild(titleFrom);

                let timeFrom = document.createElement('input');
                timeFrom.name = 'arTimes[ADD_NEW]['+id+'][TIME_FROM]';
                timeFrom.type = "text";
                block1.appendChild(timeFrom);

                let titleTo = document.createElement('p');
                titleTo.innerText = ' По ';
                block1.appendChild(titleTo);

                let timeTo = document.createElement('input');
                timeTo.name = 'arTimes[ADD_NEW]['+id+'][TIME_TO]';
                timeTo.type = "text";
                block1.appendChild(timeTo);

                let del = document.createElement('td');
                del.className = "delete";
                tr.appendChild(del);


                let delCheck = document.createElement('input');
                delCheck.name = 'ACTION[DELETE]['+id+']';
                delCheck.className = 'adm-designed-checkbox';
                delCheck.type = "checkbox";
                delCheck.id = 'del_'+id;
                delCheck.value = "Y";
                del.appendChild(delCheck);

                let label = document.createElement('label');
                label.className = "adm-designed-checkbox-label";
                label.setAttribute('for','del_'+id);
                del.appendChild(label);


                this.i++;
            }
        }
        class AddNewGroupRights
        {
            constructor (arGroups,arGroupRights){
                this.i=0
                this.arGroups=JSON.parse(arGroups);
                this.arGroupRights=JSON.parse(arGroupRights);
                this.addBtn = document.getElementById("new-group");
                this.addBtn.onclick =(e)=>{this.AddNewGropeRights();}
            }
            AddNewGropeRights(){
                event.preventDefault();
                let id = 'new_'+this.i;

                let parentBlock = document.querySelector("#edit2_edit_table tbody");
                let blockAddBtn = document.querySelector(".btn-add-new-group");
            

                let tr = document.createElement('tr');
                tr.className = "group-row";
                parentBlock.insertBefore(tr, blockAddBtn);

                let tdGroups = document.createElement('td');
                tdGroups.className = "adm-detail-content-cell-l";
                tr.appendChild(tdGroups);

                let tdRights = document.createElement('td');
                tdRights.className = "adm-detail-content-cell-r";
                tr.appendChild(tdRights);

                let selectGroups = document.createElement('select');
                selectGroups.name = 'GROUP[AddNewGroup]['+id+']';
                tdGroups.appendChild(selectGroups);
                for (let i = 0; i < this.arGroups.reference.length; i++) {
                    var option = document.createElement("option");
                    option.value = this.arGroups.reference_id[i];
                    option.text = this.arGroups.reference[i];
                    selectGroups.appendChild(option);
                }

                let selectRights = document.createElement('select');
                selectRights.name = 'GROUP[AddNewRights]['+id+']';
                tdRights.appendChild(selectRights);
                for (let i = 0; i < this.arGroupRights.reference.length; i++) {
                    var option = document.createElement("option");
                    option.value = this.arGroupRights.reference_id[i];
                    option.text = this.arGroupRights.reference[i];
                    selectRights.appendChild(option);
                }

                let del = document.createElement('td');
                del.className = "delete";
                tr.appendChild(del);


                let delCheck = document.createElement('input');
                delCheck.name = 'ACTION[DELETE_GROUP]['+id+']';
                delCheck.className = 'adm-designed-checkbox';
                delCheck.type = "checkbox";
                delCheck.id = 'del_group_'+id;
                delCheck.value = "Y";
                del.appendChild(delCheck);

                let label = document.createElement('label');
                label.className = "adm-designed-checkbox-label";
                label.setAttribute('for','del_group_'+id);

                label.title='';
                del.appendChild(label);

                this.i++;
            }
        }

        AddTime.newTime = new AddTime;
        AddNewGroupRights.newGroup = new AddNewGroupRights(
            '<?=json_encode($gropesRights["SELECT_BOX"])?>',
            '<?=json_encode($gropesRightsItems["SELECT_BOX"])?>'
            );

    </script>
<?}else {
    echo \CAdminMessage::ShowMessage(Loc::getMessage("ACCSESS_DENIED"));
}?>