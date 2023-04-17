<?
namespace Bitrix\wkopt;
use Bitrix\wkopt\WKOptSpecification;
use Bitrix\wkopt\WKAdminPage;
use Bitrix\wkopt\WKUtils;
use Bitrix\Main\Localization\Loc;

class WKNewOrderProductsAdminPage extends WKAdminPage
{
	function __construct($tableName,$specID=false,$newOrderID=false,$newOrderProductID=false){
		$this->tableName = $tableName;
		$this->specID = $specID;
		$this->newOrderID = $newOrderID;
		$this->newOrderProductID = $newOrderProductID;

		parent::__construct($this->tableName);
	}
    public function InitTable(){

		$this->queryFilter =array(
			'order' => array(),
			'count_total' => true,
			'offset' => $this->nav->getOffset(),
			'limit' => $this->nav->getLimit(),
		);
        $this->queryFilter["filter"]=array('ORDER_ID'=>$this->newOrderID);
		$this->GetTableHeaders();
		$customProductsVals = WKUtils::GetProductsCustomValues();
		foreach ($customProductsVals["PRODUCT_CHAIN"] as $key => $value) {
			$arProductsChain[$value] = $key;
		}

		$classOrm = WKUtils::getClassNameByTableName($this->tableName);
    	$this->cultureList = $classOrm::getList($this->queryFilter);
		while($this->newOrderProducts = $this->cultureList->fetch()){
			$this->rowID=$this->newOrderProducts['ID'];
			$this->arrRows[$this->newOrderProducts['ID']]=array(
				'DB' => $this->newOrderProducts,
				'CUSTOM' => array(
					"PRODUCT_ID"=> '<a href="'.$customProductsVals["TABLE_REF"][$arProductsChain[$this->newOrderProducts['PRODUCT_ID']]]["DETALE_PAGE"].'">'.$customProductsVals["TABLE_REF"][$arProductsChain[$this->newOrderProducts['PRODUCT_ID']]]["NAME"].'</a>',
				)
			);
			self::GetTableRowAction();
		}
		if(!$this->newOrderProducts){

			self::GetTableRowAction();
		}
		
		parent::InitTable();
	
	}
    public function GetTableHeaders(){
		$arFields= $this->obTable->GetTableFields(false, true);
		foreach ($arFields as $key => $value) {
			$this->headers[] = array(
				'id' => $key,
				'content' => Loc::getMessage($key),
				'default' => true
			);
		}
	}
    public function GetTableRowAction(){
		if($this->newOrderProducts){
			$this->arActionsRows[$this->rowID]["EDIT"] = array(
                "ICON"=>"edit",
                "DEFAULT"=>true,
                "TEXT"=>"Открыть",
                "ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=$this->specID&newOrderID=$this->newOrderID&newOrderProductID=$this->rowID&action=update")
            );
			$this->arActionsRows[$this->rowID]['DELETE'] = array(
				"ICON"=>"delete",
				"TEXT"=>"Удалить",
				"ACTION"=>"if(confirm('Удалить запись?')) ".$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&ID=".$this->specID."&action=delete")
			);
		}else {
			$this->rowID=0;
		}
		$this->arActionsRows[$this->rowID]["ADD"] = array(
			"ICON"=>"add",
			"TEXT"=>"Добавить",
			"ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=$this->specID&newOrderID=$this->newOrderID&action=add")
		);

	}
	public function getFormFieldsParams(){
		$specProducts = WKOptSpecification::getProductsBySpecId($this->specID);
		$specOrderProducts = WKOptSpecification::GetNewOrderProductsBySpec($this->specID);

		foreach ($specProducts as $externalID => $value) {
			if(!$this->newOrderProductID){

				if(!array_key_exists($value["BITRIX_ID"],$specOrderProducts)){
					$arSpecProductsIds[]=$value["BITRIX_ID"];
				}
			}else{
				foreach ($specOrderProducts as $orderProductkey => $orderProductValue) {
					if($orderProductValue["ID"] ==$this->newOrderProductID ){
						$arSpecProductsIds[]=$orderProductkey;//нельзя менять
					}
				}
			}
		}
		if($arSpecProductsIds){
			$customProductsVals = WKUtils::GetProductsCustomValues(array('ID'=>$arSpecProductsIds));
		}
		$this->arFieldsParams = array(
			"ID" => array(
				"READONLY" => false,
				"TYPE" => "TEXT",
			),
			"QUANTITY" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
			"PRODUCT_ID" => array(
				"READONLY" => false,
				"TYPE" => "LIST",
				"CUSTOM_VALUE" => $customProductsVals["PRODUCT_REFERENCE"],
			),
			"ORDER_ID" => array(
				"READONLY" => true,
				"TYPE" => "INPUT",
				"CUSTOM_VALUE" => $this->newOrderID,
				'CUSTOM_CHAIN'=>true
			),
		);
	}
    public function InitEditForm(){
		self::getFormFieldsParams();
		$this->arFieldsParams["PRODUCT_ID"]["READONLY"]=true;
		$filter['filter']=array(
			'ID'=>$this->newOrderProductID
		);
		$className = WKUtils::getClassNameByTableName($this->tableName);
		$this->dataForm = $className::getList($filter);
		while ($result = $this->dataForm->fetch()){
			foreach ($result as $coloumnName => $value) {

				$this->formFields[$coloumnName] = array(
					"NAME" => Loc::getMessage($coloumnName),
					"VALUE" => $value
				);
				if(array_key_exists($coloumnName, $this->arFieldsParams)){
					$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
                    if($this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
                        $this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"];
                    }
				}
			}
		}

		$this->formFields[]=array(
			'TYPE'=>"BUTTONS",
			'ARR_BTNS' => array(
				'0'=>array(
					"TYPE_BTN" => "SUBMIT",
					"NAME" => 'save',
					"VALUE" => 'Сохранить',
				),
				'1'=>array(
					"TYPE_BTN" => "SUBMIT",
					"NAME" => 'deleteCheked',
					"VALUE" => 'Удалить',
				),
				'2'=>array(
					"TYPE_BTN" => "hidden",
					"NAME" => 'ACTION',
					"VALUE" => 'UPDATE',
				)
			)
		);
		parent::InitForm();
    }
	public function InitAddForm(){
		self::getFormFieldsParams();
		unset($this->arFieldsParams["ID"]);
		$this->formFields['btns']=array(
			'TYPE'=>"BUTTONS",
			'ARR_BTNS' => array(
				'save'=>array(
					"TYPE_BTN" => "SUBMIT",
					"NAME" => 'save',
					"VALUE" => 'Сохранить',
				),
				'ACTION'=>array(
					"TYPE_BTN" => "hidden",
					"NAME" => 'ACTION',
					"VALUE" => 'ADD',
				)
			)
		);


		if(empty($this->arFieldsParams["PRODUCT_ID"]["CUSTOM_VALUE"])){
			$this->arFieldsParams["PRODUCT_ID"]=array(
				"READONLY" => true,
				"TYPE" => "TEXT",
				"CUSTOM_VALUE" =>Loc::getMessage('ALL_PRODUCTS_IN_ORDER'),
				'CUSTOM_CHAIN'=>true
			);
			unset($this->arFieldsParams["QUANTITY"]);
			$this->formFields['btns']["ARR_BTNS"]["save"]["ADDITIONAL_PARAMS"]="disabled";
		}
		foreach ($this->arFieldsParams as $coloumnName => $value) {
			$this->formFields[$coloumnName] = array(
				"NAME" => Loc::getMessage($coloumnName),
				"VALUE" => ''
			);
			if(array_key_exists($coloumnName, $this->arFieldsParams)){
				$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
				if($this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
					$this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"];
				}
			}
		}

		
		parent::InitForm();

	}
}