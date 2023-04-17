<?

namespace Bitrix\wkopt;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main;
\CModule::IncludeModule('sale');

class WKAdminPage {

    function __construct($tableName){

        $this->tableName = $tableName;
        $this->classOrm =  WKUtils::getClassNameByTableName($this->tableName);
        $this->lAdmin = new \CAdminList($this->tableName);
        $this->request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $this->nav = new \Bitrix\Main\UI\AdminPageNavigation($this->tableName);//construct
        $this->obTable = new \CPerfomanceTable;
        $this->obTable->Init($this->tableName);
        $this->catalogIblockID = \COption::GetOptionInt('wkopt',"catalogID");

        if( $_GET["mode"] == "list" ){
            if( $_GET["table_id"] != $tableName ){
                $this->not_show = true;
            }
        }
    }

    public function RenderField(){
        foreach ($this->formFields as $key => $value) {

            switch ($value["TYPE"]) {
                case 'INPUT':
                    if(!$value["READONLY"]){
                        $this->htmlFields[$key] = '<input type="text" name = "arValues['.$key.']" id="'.$key.'" value = "'.$value["VALUE"].'">';
                    }else{
                        $this->htmlFields[$key] = '<p>'.$value["VALUE"].'</p>';
                        $this->htmlFields[$key.'_hidden'] = '<input type="hidden"  name = "arValues['.$key.']" id="'.$key.'" value = "'.$value["VALUE"].'">';
                    }
                break;
                case 'CALENDAR':
                    if(!$value["READONLY"]){
                        $this->htmlFields[$key] = '<input type="text" name = "arValues['.$key.']" id="'.$key.'" value = "'.$value["VALUE"].'" onclick="BX.calendar({node: this, field: this, bTime: false});">';
                    }else{
                        $this->htmlFields[$key] ='<p>'.$value["VALUE"].'</p>';
                        // $this->htmlFields[$key] = '<input type="text" disabled name = "arValues['.$key.']" id="'.$key.'" value = "'.$value["VALUE"].'" onclick="BX.calendar({node: this, field: this, bTime: false});">';
                    }
                break;
                case 'TEXT':
                    if(!$value["READONLY"]){
                        $this->htmlFields[$key.'_hidden'] = '<input type="hidden"  name = "arValues['.$key.']" id="'.$key.'" value = "'.$value["VALUE"].'">';
                    }else{
                        $this->htmlFields[$key] = '<p>'.$value["VALUE"].'</p>';
                    }
                
                break;
                case 'LIST':

                    if(!$value["READONLY"]){
                        $this->htmlFields[$key]=SelectBoxFromArray("arValues[$key]", $value["CUSTOM_VALUE"], $value["VALUE"], 'Выбор');
                    }else {
                        $this->htmlFields[$key]=SelectBoxFromArray("arValues[$key]", $value["CUSTOM_VALUE"], $value["VALUE"], 'Выбор', "disabled");
                        $this->htmlFields[$key.'_hidden'] = '<input type="hidden"  name = "arValues['.$key.']" id="'.$key.'" value = "'.$value["VALUE"].'">';
                    }
                break;
                case 'CHECKBOX':
                    if($value["VALUE"] =="Y"){
                        $cheked = 'checked';
                    }
                    $this->htmlFields[$key] = '<input type="checkbox" '.$cheked.' name = "arValues['.$key.']" id="'.$key.'" value = "Y">';
                break;
                case 'BUTTONS':
                    foreach ($value["ARR_BTNS"] as $btnKey => $btnValue) {
                        $this->htmlFields['BUTTONS'][] =  '<input type="'.$btnValue["TYPE_BTN"].'" '.$btnValue["ADDITIONAL_PARAMS"].' name= "'.$btnValue["NAME"].'" value = "'.$btnValue["VALUE"].'">';
                    }
                break;
            }
        }
    }
    public function InitForm(){

        $this->data = new \CAdminResult($this->dataForm,$this->tableName);
        self::RenderField();
    }
    public function InitFilter(){

        $buf = array();
        foreach ($this->filterFieldArr as $key => $value) {
            $buf[$key] = "find_".$key;

        }
        $this->lAdmin->InitFilter($buf);
        $this->filterValues = array();
        foreach ($this->filterFieldArr as $key => $value) {

            if( $GLOBALS[$buf[$key]]){
                $this->filterValues[ $key ] = $GLOBALS[$buf[$key]];
            }
        }
        $this->oFilter = new \CAdminFilter(
            $this->tableName."_filter",
            $this->filterLang,
        );

    }
    public function Show(){
        if($this->oFilter){
            $this->ShowFilterForm();
        }
        if($this->htmlFields){
            $this->ShowEditForm();
        }
        if($this->row){
            $this->ShowTable();
        }
    }
    public function ShowFilterForm(){
        global $APPLICATION;
        ?><form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
            <?$this->oFilter->Begin();?>
            <?foreach ($this->filterFieldArr as $fieldName => $fieldType):?>
                <tr>
                    <td><?=Loc::getMessage($fieldName)?>:</td>
                    <td>
                        <?switch ($fieldType["TYPE"]) {
                            case 'INPUT':
                                ?><input type="text" name='find_<?=$fieldName?>' value="<?=$this->filterValues[$fieldName]?>" size="47"><?
                            break;
                            case 'LIST':
                                echo SelectBoxFromArray('find_'.$fieldName, $fieldType["VALUE"], $this->filterValues[$fieldName], 'Выбор', "");
                            break;
                            case 'CHECKBOX':
                                if($this->filterValues[$fieldName]=="Y"){$checked='checked';}
                                ?><input type="checkbox" <?=$checked?> name='find_<?=$fieldName?>' size="47" value="Y"><?
                            break;
                        }?>
                    </td>
                </tr>
            <?endforeach;?>
            <?$this->oFilter->Buttons(array("table_id"=>$this->tableName,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
             $this->oFilter->End();?>
        </form>
        <?
    }
    public function CheckListMode(){
        $this->lAdmin->CheckListMode();
    }
    public function InitTable(){

        if( !$this->not_show ){

            $this->nav->setRecordCount($this->cultureList->getCount());

            $this->lAdmin->setNavigation($this->nav, "Записей");

            $this->lAdmin->AddHeaders($this->headers);

                foreach ($this->arrRows as $element_id => $arrFields) {

                    $this->row = $this->lAdmin->AddRow($element_id, $arrFields["DB"]);

                    foreach ($arrFields["CUSTOM"] as $key => $value) {

                        $this->row->AddInputField($key, array("size"=>20));
                        $this->row->AddViewField($key, $value);

                    }
                    $this->row->AddActions($this->arActionsRows[$element_id]);

                }
                if(!$this->arrRows){

                    $this->row = $this->lAdmin->AddRow(0);
                    $this->row->AddActions($this->arActionsRows[0]);

                }

            self::CheckListMode();
        }
    }
    public function ShowTable(){
        if(!$this->catalogIblockID){
            echo \CAdminMessage::ShowMessage(Loc::getMessage("SELECT_CATALOG"));
            die;
        } else{
            ?><p><?=Loc::getMessage($this->tableName);?></p><?
            $this->lAdmin->DisplayList();
        }
    }
    public function ShowEditForm(){
        ?><form class = "wk-table-row-edit" action="" method = "post"><?
            foreach ($this->htmlFields  as $key => $value) {
                if($key!="BUTTONS"){?>
                    <div class="row">
                    <?if($this->formFields[$key]["NAME"]){
                        ?><p><?=$this->formFields[$key]["NAME"]?>:</p><?
                    }?>
                    <!-- <p><?//=Loc::getMessage($key)?>:</p> -->
                    <?=$value;?>
                </div>
                <?}?>
            <?}?>
            <?self::GetFormBtns();?>

        </form><?
    }
    public function GetFormBtns(){
        if($this->htmlFields["BUTTONS"]){
            foreach ($this->htmlFields["BUTTONS"] as $key => $value) {
                echo $value;
            }
        }
    }
   
}

?>
