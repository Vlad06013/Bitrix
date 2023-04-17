<?
namespace Bitrix\wkopt;
use Bitrix\wkopt\WKOptSpecification;
use Bitrix\wkopt\WKAdminPage;
use Bitrix\wkopt\WKUtils;
use Bitrix\Main\Localization\Loc;

class WKSpecProductsAdminPage extends WKAdminPage
{
	function __construct($tableName,$specID=false,$specProductID=false){
		$this->tableName = $tableName;
		$this->specID = $specID;
		$this->specProductID = $specProductID;
		parent::__construct($this->tableName);
	}
    public function InitTable(){

		$this->queryFilter =array(
			'order' => array(),
			'count_total' => true,
			'offset' => $this->nav->getOffset(),
			'limit' => $this->nav->getLimit(),
		);
        $this->queryFilter["filter"]=array('SPEC_ID'=>$this->specID);
		$this->GetTableHeaders();
		$customProductsValue = WKUtils::GetProductsCustomValues();
		$customSpecValues = WKUtils::GetSpecificationsCustomValues();
		$classOrm = WKUtils::getClassNameByTableName($this->tableName);
    	$this->cultureList = $classOrm::getList($this->queryFilter);
		while($this->products = $this->cultureList->fetch()){
			$this->rowID=$this->products['ID'];

			$this->arrRows[$this->products['ID']]=array(
				'DB' => $this->products,
				'CUSTOM' => array(
					"ID_1C"=> '<a href="'.$customProductsValue["TABLE_REF"][$this->products['ID_1C']]["DETALE_PAGE"].'">'.$customProductsValue["TABLE_REF"][$this->products['ID_1C']]["NAME"].'</a>',
					"SPEC_ID"=> $customSpecValues["TABLE_REF"][$this->products['SPEC_ID']]['NAME'],
				)
			);
			self::GetTableRowAction();
		}
		if(!$this->products){

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
		if($this->products){
			$this->arActionsRows[$this->rowID]["EDIT"] = array(
                "ICON"=>"edit",
                "DEFAULT"=>true,
                "TEXT"=>"Открыть",
                "ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=$this->specID&specProductID=$this->rowID&action=update")
            );
			$this->arActionsRows[$this->rowID]['DELETE'] = array(
				"ICON"=>"delete",
				"TEXT"=>"Удалить",
				"ACTION"=>"if(confirm('Удалить запись?')) ".$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=".$this->specID."&action=delete")
			);
		}else{
		$this->rowID=0;
	}
		$this->arActionsRows[$this->rowID]["ADD"] = array(
			"ICON"=>"add",
			"TEXT"=>"Добавить",
			"ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=".$this->specID."&action=add")
		);
	}
	public function InitAddForm(){
		self::getFormFieldsParams();
		unset($this->arFieldsParams["ID"]);
		unset($this->arFieldsParams["AVAILABLE_QUANTITY"]);
		unset($this->arFieldsParams["RESERVED_QUANTITY"]);
		// $flipKeys= array_flip($this->arFieldsParams["ID_1C"]["CUSTOM_CHAIN"]);

		// foreach ($this->arFieldsParams["ID_1C"]["CUSTOM_VALUE"]["reference_id"] as $key => $productID) {

			// $this->arFieldsParams["ID_1C"]["CUSTOM_VALUE"]["reference_id"][$key]=$flipKeys[$productID];

		// }
		foreach ($this->arFieldsParams as $coloumnName => $value) {

			if($coloumnName == "ID_1C"){

				$this->formFields[$coloumnName]["NAME"]= Loc::getMessage("product_ID_1C");
				$this->formFields[$coloumnName]["VALUE"]='';
			}elseif($coloumnName=="SPEC_ID"){
				$this->formFields[$coloumnName]["NAME"]=Loc::getMessage($coloumnName);
				$this->formFields[$coloumnName]["VALUE"]=$this->specID;
			}else{

				$this->formFields[$coloumnName] = array(
					"NAME" => Loc::getMessage($coloumnName),
					"VALUE" => ''
				);
			}
			if(array_key_exists($coloumnName, $this->arFieldsParams)){
				$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
				if($this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
					$this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"]['NAME'];
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
					"TYPE_BTN" => "hidden",
					"NAME" => 'ACTION',
					"VALUE" => 'ADD',
				)
			)
		);
		parent::InitForm();

	}
    public function getFormFieldsParams(){
		$specProducts = WKUtils::GetProductsCustomValues();
		$cusomSpecListVals = WKUtils::GetSpecificationsCustomValues();
		$arFlip = array_flip($specProducts["PRODUCT_CHAIN"]);
		foreach ($specProducts["PRODUCT_REFERENCE"]["reference_id"] as $key => $value) {
			$specProducts["PRODUCT_REFERENCE"]["reference_id"][$key]=$arFlip[$value];
		}

		$this->arFieldsParams = array(
			"ID" => array(
				// "READONLY" => true,
				"TYPE" => "TEXT",
			),
			"ID_1C" => array(
				"READONLY" => false,
				"TYPE" => "LIST",
                "CUSTOM_VALUE" => $specProducts["PRODUCT_REFERENCE"],
                // "CUSTOM_CHAIN" => $specProducts["PRODUCT_CHAIN"],
			),
			"MAX_QUANTITY" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
			"AVAILABLE_QUANTITY" => array(
				"READONLY" => true,
				"TYPE" => "INPUT",
			),
			"RESERVED_QUANTITY" => array(
				"READONLY" => true,
				"TYPE" => "INPUT",
			),
			"SPEC_ID" => array(
				"READONLY" => true,
				"TYPE" => "LIST",
				"CUSTOM_VALUE" => $cusomSpecListVals["SPECIFICATIONS_REFERENCE"],
			),
			"PRICE" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
		);
	}

    public function InitEditForm(){
		self::getFormFieldsParams();
		$filter['filter']=array(
			'ID'=>$this->specProductID
		);
		$className = WKUtils::getClassNameByTableName($this->tableName);
		$this->dataForm = $className::getList($filter);
		while ($result = $this->dataForm->fetch()){
			foreach ($result as $coloumnName => $value) {
				if($coloumnName=="ID_1C"){
					$this->formFields[$coloumnName]["NAME"]=Loc::getMessage("product_ID_1C");
					$this->formFields[$coloumnName]["VALUE"]=$value;
				}else{
					$this->formFields[$coloumnName] = array(
						"NAME" => Loc::getMessage($coloumnName),
						"VALUE" => $value
					);
				}
				if(array_key_exists($coloumnName, $this->arFieldsParams)){
					$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
                    if($this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
                        $this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"][$value];
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
}