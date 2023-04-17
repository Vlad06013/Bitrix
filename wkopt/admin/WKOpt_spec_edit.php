<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once(dirname(__DIR__)."/lib/model/ORM_Classes.php");

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use \Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;
use Bitrix\wkopt\WKOptSpecification;
use Bitrix\wkopt\WKSpecAdminPage;
use Bitrix\wkopt\WKSpecProductsAdminPage;
use Bitrix\wkopt\WKNewOrderAdminPage;
use Bitrix\wkopt\WKNewOrderProductsAdminPage;
use Bitrix\wkopt\WKAdminPage;
use Bitrix\wkopt\WKUtils;

$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = mb_substr($strPath2Lang, 0, mb_strlen($strPath2Lang) - mb_strlen("/admin/WKOpt_spec_edit.php"));

Loc::loadMessages($strPath2Lang. '/WKOpt_spec_edit.php');
Loader::includeModule('wkopt');
Loader::includeModule('perfmon');


if (WKOptSpecification::userHaveModuleSettingsAccsess()){

	global $arFilter;

	$arErrors=array();
	class SpecEdit
	{
		function __construct($arPost,$arGet){
			$this->arResultToShow=array();
			self::InitData($arPost,$arGet);
			self::CheckPageType();

		}
		public function InitData($arPost,$arGet){
			$this->tableName = htmlspecialcharsbx($arGet["tableID"]);
			$this->specID = htmlspecialcharsbx($arGet["specID"]);
			$this->rowId = htmlspecialcharsbx($arGet["ID"]);
			$this->specProductID = htmlspecialcharsbx($arGet["specProductID"]);
			$this->newOrderProductID = htmlspecialcharsbx($arGet["newOrderProductID"]);
			$this->pageType = htmlspecialcharsbx($arGet["action"]);
			$this->orderId = htmlspecialcharsbx($arGet["newOrderID"]);
			$this->arProperties=$arPost['arValues'];
			$this->arErrors=array();

			$this->action=$arPost['ACTION'];

			if($this->arProperties){
				if(empty($this->arProperties["DATE_CREATE"])){
					unset($this->arProperties["DATE_CREATE"]);
				}
				if(!$this->arProperties["ACTIVE"]){
					$this->arProperties["ACTIVE"] ="N";
				}
				if($this->arProperties["PICKUP"] == "Y"){
					$this->arProperties["DELIVERY_TO"] ="Самовывоз";
				}
				if(!$this->arProperties["PICKUP"]){
					$this->arProperties["PICKUP"] ="N";
				}
				if($this->arProperties["DATE_CREATE"]){
					$this->arProperties["DATE_CREATE"] = Bitrix\wkopt\WKOptSpecification::FormatDate($this->arProperties["DATE_CREATE"]);
				}
				if($this->arProperties["DATE_DELIVERY"]){
					$this->arProperties["DATE_DELIVERY"] = Bitrix\wkopt\WKOptSpecification::FormatDate($this->arProperties["DATE_DELIVERY"]);
				}
				if(empty($this->arProperties["STATUS_DELIVERY"])){
					unset($this->arProperties["STATUS_DELIVERY"]);
				}
				if($this->arProperties["RESERVED_QUANTITY"]==''){
					unset($this->arProperties["RESERVED_QUANTITY"]);
				}
				if($this->arProperties["AVAILABLE_QUANTITY"]==''){
					unset($this->arProperties["AVAILABLE_QUANTITY"]);
				}
			}
			if($arPost['deleteCheked']){
				$this->action='DELETE';
			}
			if($arPost['CHECK_ORDER']){
				$this->action='CHECK_ORDER';
			}
			if($arPost['CONFIRM_ORDER']){
				$this->action='CONFIRM_ORDER';
			}
			?><pre><?var_dump($_POST);?></pre><?
			if($this->action){
				self::DoAction();
			}
		}
		public function DoAction(){

			switch ($this->action) {
				case 'ADD':
					self::Add();
				break;
				case 'UPDATE':

					self::Update();
				break;
				case 'DELETE':
					if($this->tableName=='wk_spec'){
						$this->rowId = $this->specID;
					}
					if($this->tableName=='wk_new_order_info'){
						$this->rowId = $this->orderId;
					}
					self::Delete();
				case 'CHECK_ORDER':
					$result = WKOptSpecification::CheckOrder($this->orderId);
					if($result->getErrorMessages()){
						$this->arErrors += $result->getErrorMessages();
					}else{
						$this->succsessMessage =true;
					}
				break;
				case 'CONFIRM_ORDER':
					$result = WKOptSpecification::ConfirmOrder($this->orderId);
					if($result->getErrorMessages()){
						$this->arErrors += $result->getErrorMessages();
					}else{
						$this->succsessMessage =true;
					}
				break;

			}
		}
		public function Update(){
			switch ($this->tableName) {
				case 'wk_spec_products':
					$result = WKOptSpecification::UpdateSpecProducts($this->arProperties);
				break;
				case 'wk_new_order_products':

					$result = WKOptSpecification::UpdateNewOrderProductsQuant($this->arProperties);
				break;
				case 'wk_new_order_info':
					$result = WKOptSpecification::UpdateItem($this->tableName, $this->orderId ,$this->arProperties);
				break;
				case 'wk_spec':
					$result = WKOptSpecification::UpdateItem($this->tableName, $this->specID ,$this->arProperties);
				break;
				default:
					$result = WKOptSpecification::UpdateItem($this->tableName, $this->specID ,$this->arProperties);
				break;
			}
			if($result->getErrorMessages()){
				$this->arErrors += $result->getErrorMessages();
			}else{
				$this->succsessMessage =true;
			}
		}
		public function Add(){
			switch ($this->tableName) {
				case 'wk_new_order_products':
					if($this->arProperties["QUANTITY"]){
						$result = WKOptSpecification::AddOrderProducts($this->arProperties);
					}else{
						$msg = Loc::getMessage("NULL_QUANTITY");
						$result = new Entity\AddResult();
						$result->addError(new Entity\EntityError($msg));
					}
				break;
				default:
					$className = WKUtils::getClassNameByTableName($this->tableName);
					$result = WKOptSpecification::AddNewItem($this->tableName,$this->arProperties);

				break;
			}
			if($result->getErrorMessages()){
				$this->arErrors += $result->getErrorMessages();
			}else{
				$this->succsessMessage =true;
			}
		} 
		public function Delete(){
			switch ($this->tableName) {
				case 'wk_spec':
					$result = WKOptSpecification::deteteSpecification($this->rowId);
				break;
				case 'wk_new_order_info':
					$result = WKOptSpecification::deleteOrder($this->rowId,$sync=true);
				break;
				case 'wk_new_order_products':
					$result = WKOptSpecification::DeleteOrderProducts($this->rowId);
				break;
				case 'wk_spec_products':
					$result = WKOptSpecification::DeleteSpecProducts($this->rowId);
				break;
				default:
					WKOptSpecification::DeleteItem($this->tableName,$this->rowId);
				break;
			}
		}
		public function CheckPageType(){

			switch ($this->pageType) {
				case 'add':
					$this->GetAddPage();
				break;
				case 'delete':
					self::Delete();
				break;
				case 'update':
					$this->GetEditPage();
				break;
				default:
					$this->SpecObject = new WKSpecAdminPage("wk_spec");
					$this->SpecObject->setFilterParams();

					$this->SpecObject->InitTable();
					$this->arResultToShow[] = $this->SpecObject;
				break;
			}

		}
		public function GetAddPage(){

			switch ($this->tableName) {
				case 'wk_spec':
					$this->SpecObject = new WKSpecAdminPage($this->tableName,$this->specID);
					$this->SpecObject->InitAddForm();
					$this->arResultToShow[] = $this->SpecObject;

				break;
				case 'wk_spec_products':
					$this->SpecProducts = new WKSpecProductsAdminPage('wk_spec_products',$this->specID,$this->specProductID);
					$this->SpecProducts->InitAddForm();
					$this->arResultToShow[] = $this->SpecProducts;
				break;
				case 'wk_new_order_products':
					$this->NewOrderProducts = new WKNewOrderProductsAdminPage('wk_new_order_products',$this->specID,$this->orderId);
					$this->NewOrderProducts->InitAddForm();
					$this->arResultToShow[] = $this->NewOrderProducts;

				break;
				case 'wk_new_order_info':

					$this->NewOrder = new WKNewOrderAdminPage('wk_new_order_info',$this->specID);
					$this->NewOrder->InitAddForm();
					$this->arResultToShow[] = $this->NewOrder;

				break;

			}

		}

		public function GetEditPage(){
			switch ($this->tableName) {
				case 'wk_spec':

					$this->SpecObject = new WKSpecAdminPage($this->tableName,$this->specID);
					$this->SpecObject->InitEditForm();
					$this->arResultToShow[] = $this->SpecObject;

					$this->SpecProducts = new WKSpecProductsAdminPage('wk_spec_products',$this->specID);
					$this->SpecProducts->InitTable();
					$this->arResultToShow[] = $this->SpecProducts;

					$this->NewOrder = new WKNewOrderAdminPage('wk_new_order_info',$this->specID);
					$this->NewOrder->InitTable();
					$this->arResultToShow[] = $this->NewOrder;

				break;
				case 'wk_spec_products':

					$this->SpecProducts = new WKSpecProductsAdminPage('wk_spec_products',$this->specID,$this->specProductID);
					$this->SpecProducts->InitEditForm();
					$this->arResultToShow[] = $this->SpecProducts;

				break;
				case 'wk_new_order_info':

					$this->NewOrder = new WKNewOrderAdminPage('wk_new_order_info',$this->specID,$this->orderId);
					$this->NewOrder->InitEditForm();
					$this->arResultToShow[] = $this->NewOrder;

					$this->NewOrderProducts = new WKNewOrderProductsAdminPage('wk_new_order_products',$this->specID,$this->orderId);
					$this->NewOrderProducts->InitTable();
					$this->arResultToShow[] = $this->NewOrderProducts;

				break;
				case 'wk_new_order_products':

					$this->NewOrderProduct = new WKNewOrderProductsAdminPage('wk_new_order_products',$this->specID,$this->orderId,$this->newOrderProductID);
					$this->NewOrderProduct->InitEditForm();
					$this->arResultToShow[] = $this->NewOrderProduct;

				break;
			}
		}
		public function ShowResult(){
			if($this->arErrors){
				foreach ($this->arErrors as $key => $error) {
					$simbol = '"';
					$posStart = strpos($error, $simbol);
					if($posStart){
						$fieldName = substr($error, $posStart+1, -1);
						$errorText = str_replace($fieldName, Loc::getMessage($fieldName), $error);
						echo \CAdminMessage::ShowMessage($errorText);
					}else{
						echo \CAdminMessage::ShowMessage($error);
					}
				}
			}
			if($this->succsessMessage){
				echo \CAdminMessage::ShowNote(Loc::getMessage("SUCCSESS"));
			}
			if($this->pageType=="delete"){
				localRedirect("WKOpt_spec_table.php");
			}
			foreach ($this->arResultToShow as $key => $value) {
				$value->Show();
			}

		}
	}

	$Specification = new SpecEdit($_POST,$_GET);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

	$Specification->ShowResult();

	?>
	<style>
		.wk-table-row-edit{
			background-color:#cedde3;
			padding: 10px;
		}
		.adm-workarea .edit-none input {
			pointer-events:none;
			background:#b5c2c7;
		}
		form.edit-none select {
			pointer-events:none;
			background:#b5c2c7;
		}
		.wk-table-row-edit .row{
			display:flex;
			align-items: center;
		}
		.wk-table-row-edit .row p{
			padding-right:10px;
		}
	</style>
	<?
}else{
    echo \CAdminMessage::ShowMessage(Loc::getMessage("ACCSESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
