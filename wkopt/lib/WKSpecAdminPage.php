<?
namespace Bitrix\wkopt;
use Bitrix\wkopt\WKOptSpecification;
use Bitrix\wkopt\WKAdminPage;
use Bitrix\wkopt\WKUtils;
use Bitrix\Main\Localization\Loc;

class WKSpecAdminPage extends WKAdminPage
{
	function __construct($tableName,$specID=false){
		$this->tableName = $tableName;
		$this->specID = $specID;


		parent::__construct($this->tableName);
	}
	public function getFormFieldsParams(){

        $statusDelivery = WKUtils::GetStatusVals();
		$customUserListValues = WKUtils::GetUsersCustomValues();

		$this->arFieldsParams = array(
			"ID" => array(
				"READONLY" => true,
				"TYPE" => "TEXT",
			),
			"ID_1C" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
			"USER_ID_1C" => array(
				"READONLY" => false,
				"TYPE" => "LIST",
				"CUSTOM_VALUE" =>$customUserListValues["USERS_REFERENCE"],
			),
			"CAREER" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
			"DELIVERY_TO" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
			"VERSION" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
			"DATE_CREATE" => array(
				"READONLY" => true,
				"TYPE" => "CALENDAR",
			),
			"STATUS" => array(
				"READONLY" => true,
				"TYPE" => "TEXT",
				"CUSTOM_VALUE" =>$statusDelivery,
				"CUSTOM_CHAIN" =>true,
			),
			"PICKUP" => array(
				"READONLY" => false,
				"TYPE" => "CHECKBOX",
			),
			"ACTIVE" => array(
				"READONLY" => false,
				"TYPE" => "CHECKBOX",
			),
		);
	}
	public function InitAddForm(){
		self::getFormFieldsParams();
		unset($this->arFieldsParams['STATUS']);
		unset($this->arFieldsParams['ID']);
		unset($this->arFieldsParams['DATE_CREATE']);
		foreach ($this->arFieldsParams as $coloumnName => $value) {

			$this->formFields[$coloumnName] = array(
				"NAME" => Loc::getMessage($coloumnName),
				"VALUE" => ''
			);
			if(array_key_exists($coloumnName, $this->arFieldsParams)){
				$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
				if($athis->rFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
					$this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"][$valueDB]['NAME'];
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

	public function InitEditForm(){
		self::getFormFieldsParams();
		$filter['filter']=array(
			'ID'=>$this->specID
		);
		$className = WKUtils::getClassNameByTableName($this->tableName);

		$this->dataForm = $className::getList($filter);
		while ($result = $this->dataForm->fetch()){

			foreach ($result as $coloumnName => $valueDB) {
				$this->formFields[$coloumnName] = array(
					"NAME" => Loc::getMessage($coloumnName),
					"VALUE" => $valueDB
				);
				if(array_key_exists($coloumnName, $this->arFieldsParams)){
					$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
					if($this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
                        $this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"][$valueDB]['NAME'];
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
	public function InitTable(){

		$this->queryFilter =array(
			'order' => array(),
			'count_total' => true,
			'offset' => $this->nav->getOffset(),
			'limit' => $this->nav->getLimit(),
		);
		if($this->filterValues){
			$this->queryFilter["filter"]=$this->filterValues;
		}
		$this->GetTableHeaders();
		$customUserValue = WKUtils::GetUsersCustomValues();
		$classOrm =WKUtils::getClassNameByTableName($this->tableName);

    	$this->cultureList = $classOrm::getList($this->queryFilter);
		while($this->specification = $this->cultureList->fetch()){

			$this->specID = $this->specification['ID'];
			$this->arrRows[$this->specification['ID']]=array(
				'DB' => $this->specification,
				'CUSTOM' => array(
					Loc::getMessage("PRODUCTS") => self::GetProductsColoumn(),
					"STATUS"=>Loc::getMessage($this->specification['STATUS']),
					"USER_ID_1C"=> '<a href="'.$customUserValue["TABLE_REF"][$this->specification['USER_ID_1C']]["DETALE_PAGE"].'">'.$customUserValue["TABLE_REF"][$this->specification['USER_ID_1C']]["NAME"].'</a>',
				)
			);
			self::GetTableRowAction();
		}
		if(!$this->specification){

			self::GetTableRowAction();
		}
		parent::InitTable();
	
	}
    public function GetTableRowAction(){
		if($this->specification){
			$this->arActionsRows[$this->specID]["EDIT"] = array(
                "ICON"=>"edit",
                "DEFAULT"=>true,
                "TEXT"=>"Открыть",
                "ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=$this->specID&action=update")
            );
			$this->arActionsRows[$this->specID]['DELETE'] = array(
				"ICON"=>"delete",
				"TEXT"=>"Удалить",
				"ACTION"=>"if(confirm('Удалить запись?')) ".$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&ID=".$this->specID."&action=delete")
			);
		}else{
			$this->specID=0;
		}
		$this->arActionsRows[$this->specID]["ADD"] = array(
			"ICON"=>"add",
			"TEXT"=>"Добавить",
			"ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&action=add")
		);

	}
	public function GetProductsColoumn(){
        $arProducts = WKOptSpecification::getProductsBySpecId($this->specID);

        if($arProducts){

            foreach ($arProducts as $key => $value) {
                $htmProductsStr =$htmProductsStr."<a href=".$value["URL"]."><p>[".$value["BITRIX_ID"]."]".$value['NAME']."</p></a>";
            }
        } else {
            $htmProductsStr = false;
        }
        return $htmProductsStr;
    }
	public function setFilterParams(){
		$specItems = WKUtils::GetSpecificationsCustomValues();
		$customUserValue = WKUtils::GetUsersCustomValues();

		$this->filterFieldArr = array(
			"ID" => array(
				"TYPE" => "LIST",
				"VALUE" => $specItems["SPECIFICATIONS_REFERENCE"]
			),
			"ID_1C" => array("TYPE" => "INPUT"),
			"USER_ID_1C" => array(
				"TYPE" => "LIST",
				"VALUE" => $customUserValue["USERS_REFERENCE"]
			),
			"CAREER" => array("TYPE" => "INPUT"),
			"DELIVERY_TO" => array("TYPE" => "INPUT"),
			"PICKUP" => array("TYPE" => "CHECKBOX"),
			"ACTIVE" => array("TYPE" => "CHECKBOX"),
			"VERSION" => array("TYPE" => "INPUT"),
		);

		$this->filterLang = array();

		foreach ($this->filterFieldArr as $key => $value) {
			$this->filterLang[] = Loc::getMessage($value);
		}
		parent::InitFilter();
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
		$this->headers[] = array(
			'id' => Loc::getMessage("PRODUCTS"),
			'content' => Loc::getMessage("PRODUCTS"),
			'default' => true
		);
	}
}

?>
