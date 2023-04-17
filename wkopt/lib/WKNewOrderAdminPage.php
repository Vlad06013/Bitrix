<?
namespace Bitrix\wkopt;
use Bitrix\wkopt\WKOptSpecification;
use Bitrix\wkopt\WKAdminPage;
use Bitrix\wkopt\WKUtils;
use Bitrix\Main\Localization\Loc;

class WKNewOrderAdminPage extends WKAdminPage
{
	function __construct($tableName,$specID=false,$newOrderID=false){
		$this->tableName = $tableName;
		$this->specID = $specID;
		$this->newOrderID = $newOrderID;

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
		$customUserValue = WKUtils::GetUsersCustomValues();
		$customSpecValue = WKUtils::GetSpecificationsCustomValues();
		$customTimeValue = WKUtils::GetTimeList();
		$classOrm = WKUtils::getClassNameByTableName($this->tableName);
    	$this->cultureList = $classOrm::getList($this->queryFilter);
		while($this->newOrder = $this->cultureList->fetch()){
			$this->specID = $this->newOrder['SPEC_ID'];
			$this->rowID=$this->newOrder['ID'];

			$this->arrRows[$this->newOrder['ID']]=array(
				'DB' => $this->newOrder,
				'CUSTOM' => array(
					"STATUS_DELIVERY"=>Loc::getMessage($this->newOrder['STATUS_DELIVERY']),
					"USER_ID"=> '<a href="'.$customUserValue["TABLE_REF"][$this->newOrder['USER_ID']]["DETALE_PAGE"].'">'.$customUserValue["TABLE_REF"][$this->newOrder['USER_ID']]["NAME"].'</a>',
					"SPEC_ID"=> $customSpecValue["TABLE_REF"][$this->newOrder['SPEC_ID']]["NAME"],
					"TIME_DELIVERY"=> $customTimeValue["TABLE_REF"][$this->newOrder['TIME_DELIVERY']]["NAME"],
				)
			);
			self::GetTableRowAction();
		}
		if(!$this->newOrder){
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

		if($this->newOrder){
			$this->arActionsRows[$this->rowID]["EDIT"] = array(
                "ICON"=>"edit",
                "DEFAULT"=>true,
                "TEXT"=>"Открыть",
                "ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=$this->specID&newOrderID=$this->rowID&action=update")
            );
			$this->arActionsRows[$this->rowID]['DELETE'] = array(
				"ICON"=>"delete",
				"TEXT"=>"Удалить",
				"ACTION"=>"if(confirm('Удалить запись?')) ".$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&ID=".$this->specID."&action=delete")
			);
		}else{
			$this->rowID=0;
		}
		$this->arActionsRows[$this->rowID]["ADD"] = array(
			"ICON"=>"add",
			"TEXT"=>"Добавить",
			"ACTION"=>$this->lAdmin->ActionRedirect("WKOpt_spec_edit.php?tableID=$this->tableName&specID=$this->specID&action=add")
		);
	}
    public function getFormFieldsParams(){
		$customTimeListVals = WKUtils::GetTimeList();
        $customSpecVals = WKUtils::GetSpecificationsCustomValues();
        $customUserVals = WKUtils::GetUsersCustomValues();
        $statusDelivery = WKUtils::GetStatusVals();
        
		$this->arFieldsParams = array(
			"ID" => array(
				"READONLY" => true,
				"TYPE" => "TEXT",
			),
			"TIME_DELIVERY" => array(
				"READONLY" => false,
				"TYPE" => "LIST",
                "CUSTOM_VALUE" => $customTimeListVals["LIST_REFERENCE"],

			),
			"DATE_DELIVERY" => array(
				"READONLY" => false,
				"TYPE" => "CALENDAR",
			),
			"DATE_CREATE" => array(
				"READONLY" => true,
				"TYPE" => "CALENDAR",
			),
			"COMMENT" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
            "SPEC_ID" => array(
				"READONLY" => true,
				"TYPE" => "LIST",
                "CUSTOM_VALUE" => $customSpecVals["SPECIFICATIONS_REFERENCE"],
                // "CUSTOM_CHAIN" => true,

			),
            "USER_GETTER_NAME" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
            "USER_GETTER_PHONE" => array(
				"READONLY" => false,
				"TYPE" => "INPUT",
			),
            "STATUS_DELIVERY" => array(
				"READONLY" => true,
				"TYPE" => "TEXT",
                "CUSTOM_VALUE" =>$statusDelivery,
                "CUSTOM_CHAIN" => true,

			),
            "USER_ID" => array(
				"READONLY" => false,
				"TYPE" => "LIST",
                "CUSTOM_VALUE" => $customUserVals["USERS_REFERENCE"],
			),
		);
		// var_dump($this->specID);
	}
	public function InitAddForm(){
		self::getFormFieldsParams();
		unset($this->arFieldsParams["ID"]);
		unset($this->arFieldsParams["STATUS_DELIVERY"]);
		unset($this->arFieldsParams["DATE_CREATE"]);


		foreach ($this->arFieldsParams as $coloumnName => $value) {
			if($coloumnName=="SPEC_ID"){
				$this->formFields[$coloumnName] = array(
					"NAME" => Loc::getMessage($coloumnName),
					"VALUE" => $this->specID
				);
			}else{
				$this->formFields[$coloumnName] = array(
					"NAME" => Loc::getMessage($coloumnName),
					"VALUE" => ''
				);
			}
			if(array_key_exists($coloumnName, $this->arFieldsParams)){
				$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
				// if($athis->rFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
					// $this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"][$this->specID]['NAME'];
				// }
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
			'ID'=>$this->newOrderID
		);
		$className = WKUtils::getClassNameByTableName($this->tableName);

		$this->dataForm = $className::getList($filter);
		while ($result = $this->dataForm->fetch()){
			foreach ($result as $coloumnName => $value) {
				$this->formFields[$coloumnName] = array(
					"NAME" => Loc::getMessage($coloumnName),
					"VALUE" => $value
				);
				if($coloumnName=='STATUS_DELIVERY'){
					$this->statusCode= $value;
				}
				if(array_key_exists($coloumnName, $this->arFieldsParams)){
					$this->formFields[$coloumnName] += $this->arFieldsParams[$coloumnName];
                    if($this->arFieldsParams[$coloumnName]["CUSTOM_CHAIN"]){
                        $this->formFields[$coloumnName]["VALUE"]= $this->arFieldsParams[$coloumnName]["CUSTOM_VALUE"][$value]['NAME'];
                    }
				}
			}
		}
		$this->formFields["btns"]=array(
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
	
		self::CreateBTNsByStatusDelivery();
		parent::InitForm();
    }
	public function DoPrivateFields(){
		foreach ($this->formFields as $key => $value) {
			// if($value["READONLY"]){
				?><pre><?var_dump($key);?></pre><?
			// }
		}
	}

	public function CreateBTNsByStatusDelivery()
	{
		switch ($this->statusCode) {
			case 'CN':
				$this->formFields[]=array(
					'TYPE'=>"BUTTONS",
					'ARR_BTNS' => array(
						'3'=>array(
							"TYPE_BTN" => "SUBMIT",
							"NAME" => 'CHECK_ORDER',
							"VALUE" => 'Оформить заказ',
						),
						'4'=>array(
							"TYPE_BTN" => "SUBMIT",
							"NAME" => 'CONFIRM_ORDER',
							"VALUE" => 'Подтвердить заказ',
						),
					)
				);
				break;
			case 'CW':
				$this->formFields[]=array(
					'TYPE'=>"BUTTONS",
					'ARR_BTNS' => array(
						'4'=>array(
							"TYPE_BTN" => "SUBMIT",
							"NAME" => 'CONFIRM_ORDER',
							"VALUE" => 'Подтвердить заказ',
						)
					)
				);
			break;
			case 'CO':
				unset($this->formFields["btns"]);
				self::DoPrivateFields();
			break;
		}
	}
}