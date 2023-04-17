<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;


$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = mb_substr($strPath2Lang, 0, mb_strlen($strPath2Lang) - mb_strlen("/install/index.php"));

Loc::loadMessages($strPath2Lang. '/install.php');

Class wkopt extends CModule
{


	public function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__.'/version.php');

		$this->MODULE_ID = "wkopt";
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = Loc::getMessage("WKOpt_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("WKOpt_INSTALL_DESCRIPTION");
	}
	
	public function GetRightsList(){
     
        $arRights = array(
            'A'=>Loc::getMessage("NOT_ACCSESS"),
            'B'=>Loc::getMessage("CREATE_ORDER"),
            'C'=>Loc::getMessage("CONFIRM_ORDER"),
            'D'=>Loc::getMessage("GET_DOCUMENTS"),
            'E'=>Loc::getMessage("READ_DOCUMENTS"),
            'F'=>Loc::getMessage("USERS_SETTINGS"),
            'G'=>Loc::getMessage("MODULE_SETTINGS")
        );
        foreach ($arRights as $key => $value) {
            $rights["SELECT_BOX"]["reference"][]= "[".$key."] ".$value;
            $rights["SELECT_BOX"]["reference_id"][]= $key;
        }
        return $rights;
    }
	public function GetUsersGroupRights(){
        $selectBox = array();
        $rsGroups = \CGroup::GetList(($by="id"), ($order="asc"), array());
        while($res = $rsGroups->fetch()){
            $selectBox["SELECT_BOX"]["reference"][]= "[".$res["ID"]."] ".$res['NAME'];
            $selectBox["SELECT_BOX"]["reference_id"][]= $res["ID"];
        }
            $rights = self::GetUsersGroupItems();
            if($rights){

                $selectBox["VALS_DB"] = $rights;
            }
        return $selectBox;
    }
	public function GetUsersGroupItems(){
        $groupRights =\Bitrix\wkopt\WKOptSpecification::getAllItems("wk_group_rights",false,array('GROUP_ID' => 'ASC'));
        if($groupRights){
            foreach ($groupRights as $key => $value) {
                $rights[]=array(
                    "ID"=> $value["ID"],
                    "RIGHTS"=> $value["RIGHTS"],
                    "GROUP_ID"=> $value["GROUP_ID"]
                );
            }
        }
        return $rights;
    }
	function InstallDB($install_wizard = true)
	{

		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		$clearInstall = false;
		if(!$DB->Query("SELECT 'x' FROM wk_spec", true))
		{
			$clearInstall = true;
			require_once($this->GetPath(true)."/install/db/ORM/install.php");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}
		RegisterModule($this->MODULE_ID);
		Loader::includeModule('wkopt');		
		$result = Bitrix\wkopt\WKOptSpecification::AddNewItem('wk_group_rights',array("GROUP_ID"=>'1','RIGHTS'=>'G'));

		return true;
	}

	function UnInstallDB($request)
	{
		global $APPLICATION;

		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if($request["deletedata"] == "Y")
		{
			$this->errors = $DB->RunSQLBatch($this->GetPath(true)."/install/db/".$DBType."/uninstall.sql");
			if($this->errors !== false)
			{
				$APPLICATION->ThrowException(implode("", $this->errors));
				return false;
			}
		}

		return true;
	}

	function InstallEvents()
	{
		return true;
	}
	function GetPath($notDocumentRoot = false)
	{
		if(!$notDocumentRoot){
			return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
		} else{
			return dirname(__DIR__);
		}
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{

		$filename = $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/WKOpt_spec_edit.php";
		$data = '<?require_once("'.$this->GetPath(true).'/admin/WKOpt_spec_edit.php")?>';
		file_put_contents ($filename , $data);


		// CopyDirFiles($this->GetPath(true)."/admin/menu.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/admin/menu.php",  true, false);
		// CopyDirFiles($this->GetPath(true)."/include.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/include.php",  true, false);
		// CopyDirFiles($this->GetPath(true)."/admin/WKOpt_spec_table.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/WKOpt_spec_table.php",  true, true);
		// CopyDirFiles($this->GetPath(true)."/admin/WKOpt_spec_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/WKOpt_spec_edit.php",  true, true);

		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($this->GetPath(true)."/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		return true;
	}

	function DoInstall()
	{
		$this->InstallFiles();
		$this->InstallDB(false);
	}

	function DoUninstall()
	{
		$this->UnInstallFiles();
		global $APPLICATION;
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if($request["step"]<2){
			$APPLICATION->IncludeAdminFile(Loc::getMessage("WKOpt_DELETE_ACTION").' '.$this->MODULE_ID, $this->GetPath(true)."/install/unstep1.php");
		}
		elseif($request["step"]==2){
			UnRegisterModule($this->MODULE_ID);
			$this->UnInstallDB($request);

			$APPLICATION->IncludeAdminFile($this->MODULE_ID, $this->GetPath(true)."/install/unstep2.php");

		}

	}
}
?>
