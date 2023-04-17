<?
namespace Bitrix\wkopt;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main;
\CModule::IncludeModule('sale');

class WKUtils {
    public static function GetUsersCustomValues(){
		$rsUsers = \CUser::GetList(false,false,false,['FIELDS'=>array('ID','NAME','LAST_NAME')]);
        while($arUser = $rsUsers->Fetch()){
            $arUsersName[] = $arUser;
        }
        foreach ($arUsersName as $key => $user) {
            $arUsersList["USERS_REFERENCE"]["reference"][]= "[".$user["ID"]."] ".$user['NAME'];
            $arUsersList["USERS_REFERENCE"]["reference_id"][]= $user["ID"];
            $arUsersList["TABLE_REF"][$user["ID"]]= array(
                "NAME"=>"[".$user["ID"]."] ".$user['NAME'],
                "DETALE_PAGE"=>'/bitrix/admin/user_edit.php?lang=ru&ID='.$user["ID"],
            );

        }
		return $arUsersList;
    }
    public static function getClassNameByTableName($tableName){
        $arClasses = array(
            'wk_spec'=>'\Bitrix\wkopt\WK_SpecTable',
            'wk_spec_products'=>'\Bitrix\wkopt\WK_SpecProductsTable',
            'wk_new_order_info'=>'\Bitrix\wkopt\WK_NewOrderInfoTable',
            'wk_new_order_products'=>'\Bitrix\wkopt\WK_NewOrderProductsTable',
            'wk_spec_delivery_timelist'=>'\Bitrix\wkopt\WK_SpecTimeDeliveryListTable',
            'wk_group_rights'=>'\Bitrix\wkopt\WK_GroupRightsTable'

        );
        return $arClasses[$tableName];
    }
    public static function GetProductsCustomValues($filter=false){//filter

        $arProducts = WKOptSpecification::GetCatalogProducts($filter);

        foreach ($arProducts as $key => $product) {
        $detailUrl = "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=".$product["IBLOCK_ID"]."&type=1c_catalog&lang=ru&ID=".$product["ID"];

            $arProductList["PRODUCT_REFERENCE"]["reference"][]= "[".$product["ID"]."] ".$product['NAME'];
            $arProductList["PRODUCT_REFERENCE"]["reference_id"][]= $product["ID"];
            $arProductList["PRODUCT_CHAIN"][$product["EXTERNAL_ID"]]= $product["ID"];
            $arProductList["TABLE_REF"][$product["EXTERNAL_ID"]]= array(
                "NAME"=>"[".$product["ID"]."] ".$product['NAME'],
                "DETALE_PAGE"=>$detailUrl,
            );
        }

		return $arProductList;
    }
    public static function GetSpecificationsCustomValues(){//filter

        $result = WKOptSpecification::getAllItems('wk_spec');
        foreach ($result as $key => $value) {

            $specificationList["SPECIFICATIONS_REFERENCE"]["reference_id"][]= $value["ID"];
            $specificationList["SPECIFICATIONS_REFERENCE"]["reference"][] ="[".$value["ID"]."] ".$value["CAREER"].' '.$value["DELIVERY_TO"];
            $specificationList["TABLE_REF"][$value["ID"]]['NAME'] = "[".$value["ID"]."] ".$value["CAREER"].' '.$value["DELIVERY_TO"];
        }
           
		return $specificationList;
    }
    public static function GetTimeList(){
        $timeList = \Bitrix\wkopt\WKOptSpecification::getAllItems("wk_spec_delivery_timelist");
        foreach ($timeList as $key => $value) {
            $timeList["LIST_REFERENCE"]["reference_id"][]=$value["ID"];
            $timeList["LIST_REFERENCE"]["reference"][] = "[".$value["ID"]."] С ".$value["TIME_FROM"]." До ".$value["TIME_TO"];
            $timeList["TABLE_REF"][$value["ID"]]['NAME'] =  "C ".$value["TIME_FROM"]." До ".$value["TIME_TO"];

        }
		return $timeList;
      
    }
    public static function GetStatusVals(){
        $arStatuses = array(
           "CN"=>array(
               'NAME' =>Loc::getMessage("CN"),
           ),
           "CO"=>array(
            'NAME' =>Loc::getMessage("CO"),
           ),
           "CW"=>array(
            'NAME' =>Loc::getMessage("CW"),
           ),
        );
		return $arStatuses;
      
    }
}
?>
