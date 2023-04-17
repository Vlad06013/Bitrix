<?

namespace Bitrix\wkopt;
    require_once(dirname(__DIR__)."/lib/model/ORM_Classes.php");

    use Bitrix\Main\Entity;
    use Bitrix\Main\Type;
    use Bitrix\Main;
    use \Bitrix\Main\Entity\Query;
    use \Bitrix\Main\Type\Date;
    use \Bitrix\Main\Loader;
    use Bitrix\Main\Localization\Loc;
    use Bitrix\wkopt\WKUtils;
   \CModule::includeModule('wkopt');

    use \Bitrix\CModule;
    use Bitrix\wkopt;
    use Bitrix\Main\ModuleManager;

class WKOptSpecification {
    function __construct($tableId, $specId=false){

        $this->tableId = $tableId;
        $this->specId = $specId;
        $this->catalogIblockID = \COption::GetOptionInt('wkopt',"catalogID");
    }

    public function getCatalogIblock(){
        $catalogIblockID = \COption::GetOptionInt('wkopt',"catalogID");
        if($catalogIblockID){
            return $catalogIblockID;
        } else {
            return false;
        }
    }
    public function GetTimeList($filter=false){
        $timeList = self::getAllItems("wk_spec_delivery_timelist",$filter);
        return $timeList;
    }
    public function getProductsBySpecId($specId){
       $currency= \CCurrency::GetBaseCurrency();

        $catalogIblockID = self::getCatalogIblock();
        if($catalogIblockID){
            $arProducts = array();
            $query = WK_SpecProductsTable::query();
            $query->where('SPEC_ID', $specId)->
            setSelect(["*"]);
            $db_res = $query->exec();
            while($products= $db_res->fetch()) {
                $arProducts[$products['ID_1C']] = $products;
                if($products['ID_1C']){
                    $productXMLId[] = $products['ID_1C'];
                }
            }
            if($productXMLId){
                if(Loader::IncludeModule("iblock")){
                    $arFilter = Array("XML_ID" => $productXMLId,"IBLOCK_ID"=>$catalogIblockID, "ACTIVE" => "Y");
                    $arCatalogProducts = self::GetCatalogProducts($arFilter);

                    foreach($arCatalogProducts as $value)
                    {
                        $resSection = \CIBlockSection::GetByID($value['IBLOCK_SECTION_ID']);
                        if($section = $resSection->fetch()){
                            $arProducts[$value["XML_ID"]]["SECTION_NAME"]=$section['NAME'];
                            $arProducts[$value["XML_ID"]]["SECTION_CODE"]=$section['CODE'];
                        }

                        $arProducts[$value["XML_ID"]]["NAME"]=$value['NAME'];
                        $arProducts[$value["XML_ID"]]["IBLOCK_SECTION_ID"]=$value['IBLOCK_SECTION_ID'];
                        $arProducts[$value["XML_ID"]]["BITRIX_ID"]=$value['ID'];
                        $arProducts[$value["XML_ID"]]["IBLOCK_ID"]= $value["IBLOCK_ID"];
                        $arProducts[$value["XML_ID"]]["URL"] ="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=".$value["IBLOCK_ID"]."&type=1c_catalog&lang=ru&ID=".$value['ID'];
                        $arProducts[$value["XML_ID"]]["PREVIEW_PICTURE"]=$value['PICTURE_SRC'];
                        $arProducts[$value["XML_ID"]]["MEASURE"]=$value['MEASURE'];
                        $arProducts[$value["XML_ID"]]["CURRENCY"]=$currency;
                   
                    }

                }
            }

            return $arProducts;
        }
    }
    public function userCanDo($rights){
        $canDo=false;
        $filter = array(
            'RIGHTS'=>$rights
        );
        $result = self::getAllItems('wk_group_rights',$filter);
        foreach ($result as $key => $value) {
            $arGroupIds[]=$value['GROUP_ID'];
        }
        if ($arGroupIds &&  \CSite::InGroup($arGroupIds)){
            $canDo = true;
        }

        return $canDo;    
    }
    public function userCanCreateOrder(){
        return self::userCanDo('B');
    }
    public function userCanReadDocuments(){
        return self::userCanDo('E');
    }
    public function userCanConfirmOrders(){
        return self::userCanDo('C');
    }
    public function userCanEditProfiles(){
        return self::userCanDo('F');
    }
    public function userCanGetDocuments(){
        return self::userCanDo('D');
    }
    public function userHaveModuleSettingsAccsess(){
        return self::userCanDo('G');
    }
    public function GetNewOrderInfo($filter){
        if($filter){
            $res = WK_NewOrderInfoTable::getList(array(
                'select' => array('*',"SPEC"),
                "filter"=> $filter
            ));
            if($result = $res->fetchAll())
            {
                $orderInfo=$result;
            }
        }
        return $orderInfo;
    }
    public function GetNewOrderProductsBySpec($specId,$userId=false){
        if($specId){
            $filter = array(
                "ORDER_INFO.SPEC_ID"=>$specId,
                "ORDER_INFO.STATUS_DELIVERY"=>"CN"
            );
            if($userId){
                $filter["ORDER_INFO.USER_ID"] = $userId;
            }
        $res = WK_NewOrderProductsTable::getList(array(
                'select' => array('*','ORDER_INFO'),
                "filter" => $filter
                // array(
                //     "ORDER_INFO.SPEC_ID"=>$specId,
                //     "ORDER_INFO.STATUS_DELIVERY"=>"CN"
                // )
            ));
            while($result = $res->fetch())
            {
                $arProducts[$result["PRODUCT_ID"]]=$result;
            }

        }
        return $arProducts;
    }
    public function editNewOrder($newOrderId,$arProducts,$arPropsNewOrder=false){
        $arNewOrderProducts= self::GetNewOrderProductsByOrder($newOrderId);

        foreach($arNewOrderProducts as $productKeyID => $productItem){
            if(array_key_exists($productKeyID,$arProducts)){
                $resUpdate= self::UpdateItem("wk_new_order_products", $arNewOrderProducts[$productKeyID]["ID"],array("QUANTITY"=> $arProducts[$productKeyID]));
                $arDeltaProducts[$productKeyID] = $productItem["QUANTITY"] - $arProducts[$productKeyID];
            }else{
                $resultDelete = self::DeleteItem("wk_new_order_products",$arNewOrderProducts[$productKeyID]["ID"]);
                $arDeltaProducts[$productKeyID] = $productItem["QUANTITY"];
            }
            $specId = $productItem["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_SPEC_ID"];
        }
        foreach($arProducts as $productKeyID => $productQuantity){
            if(!array_key_exists($productKeyID,$arNewOrderProducts)){
                $arProps = array(
                    'QUANTITY'=>$productQuantity,
                    'PRODUCT_ID'=>$productKeyID,
                    'ORDER_ID'=>$newOrderId
                );
                $resultAdd = self::AddNewItem("wk_new_order_products",$arProps);
                $arDeltaProducts[$productKeyID] = $arNewOrderProducts[$productKeyID]["QUANTITY"] - $productQuantity;
            }
        } 
        if($arDeltaProducts){
            $resSync = self::synchNewOrderChanges($specId,$newOrderId,$arDeltaProducts,$arPropsNewOrder);
        }
        return $resSync;
    }
    public function deleteOrder($newOrderId,$needSync=true){

        $classNameOrderProducts = WKUtils::getClassNameByTableName('wk_new_order_products');
        $arNewOrderProducts= self::GetNewOrderProductsByOrder($newOrderId);

        if($arNewOrderProducts){

            foreach ($arNewOrderProducts as $id => $value) {
                if($needSync){
                    $arDeltaProducts[$id] = $value['QUANTITY'];
                }
                $result = $classNameOrderProducts::delete($value["ID"]);
            }
        }
        $spec = self::GetNewOrderInfo(array('ID'=>$newOrderId));
        $specId = $spec[0]["SPEC_ID"];
            
        $classNameOrderInfo = WKUtils::getClassNameByTableName('wk_new_order_info');
        $result = $classNameOrderInfo::delete($newOrderId);

        if($arDeltaProducts && $needSync){
            $result = self::synchNewOrderChanges($specId,$newOrderId,$arDeltaProducts,false,$deleteOrder=true);
        }
        return $result;
    }
    public function synchNewOrderChanges($specId,$newOrderId=false, $arDeltaProducts, $arPropsNewOrder,$updateOrderParams=false){

        $arProducts = self::getProductsBySpecId($specId);

        foreach ($arProducts as $key => $value) {

            $availableQuant= $value['AVAILABLE_QUANTITY'];
            $reservedQuant= $value['RESERVED_QUANTITY'];

            $reservedQuant -= $arDeltaProducts[$value["BITRIX_ID"]];
            $availableQuant += $arDeltaProducts[$value["BITRIX_ID"]];

            $arProps = array(
                'AVAILABLE_QUANTITY'=> $availableQuant,
                'RESERVED_QUANTITY'=>$reservedQuant
            );

            $arSpecProducts = self::UpdateItem("wk_spec_products",$value["ID"],$arProps);
        }

        if($arSpecProducts->isSuccess() && !$updateOrderParams){
            if($arPropsNewOrder["DATE_DELIVERY"]){
                $arPropsNewOrder["DATE_DELIVERY"] = self::FormatDate($arPropsNewOrder["DATE_DELIVERY"]);
                $orderInfo = self::UpdateItem("wk_new_order_info",$newOrderId,$arPropsNewOrder);
                $result = $orderInfo->isSuccess();
            }else{
                $result = $arSpecProducts;
            }

        }else {
            $result = $arSpecProducts;
        }

        return $result;

    }
    public function UpdateSpecProducts($arProps){
        if($arProps){
            $oldItems = self::getAllItems('wk_spec_products',array('ID'=>$arProps["ID"]));
            $deltaQuantity = $oldItems[0]["MAX_QUANTITY"]-$arProps["MAX_QUANTITY"];
            $newAvaliableQuant = $oldItems[0]["AVAILABLE_QUANTITY"]-$deltaQuantity;
            $arProps['AVAILABLE_QUANTITY']=$newAvaliableQuant;
            $result = self::UpdateItem('wk_spec_products',$arProps["ID"],$arProps);

            if($oldItems[0]["ID_1C"]!=$arProps["ID_1C"]){

               $newSpecProductCatalog = self::GetCatalogProducts(array("EXTERNAL_ID"=>$arProps["ID_1C"]));
               $oldSpecProductCatalog = self::GetCatalogProducts(array("EXTERNAL_ID"=>$oldItems[0]["ID_1C"]));

               $newSpecProductBitrixId =$newSpecProductCatalog[0]['ID'];
               $oldSpecProductBitrixId =$oldSpecProductCatalog[0]['ID'];

                $newOrderInfo = self::GetNewOrderInfo(array('SPEC_ID'=>$arProps["SPEC_ID"]));
                foreach ($newOrderInfo as $key => $newOrder) {
                    $arIdsNerOrder[]=$newOrder["ID"];
                }
                $newOrderProducts = self::getAllItems('wk_new_order_products',array('ORDER_ID'=>$arIdsNerOrder));
                foreach ($newOrderProducts as $key => $value) {
                    if($value["PRODUCT_ID"] == $oldSpecProductBitrixId){
                        $arPropsNewOrderProduct = array('PRODUCT_ID'=>$newSpecProductBitrixId);
                        $result = self::UpdateItem('wk_new_order_products',$value["ID"],$arPropsNewOrderProduct);
                    }
                }
            }
        }
        return $result;
    }
    public function DeleteSpecProducts($rowId){
        if($rowId){

            $arProduct = self::getAllItems('wk_spec_products',array('ID'=>$rowId));
            $catalogProduct = self::GetCatalogProducts(array('EXTERNAL_ID'=>$arProduct[0]['ID_1C']));
            $arProduct = self::getAllItems('wk_new_order_products',array('PRODUCT_ID'=>$catalogProduct[0]["ID"]));
            $result = self::DeleteOrderProducts($arProduct[0]["ID"]);
            $className = WKUtils::getClassNameByTableName("wk_spec_products");
            $result = $className::delete($rowId); 
        }

        return $result;

    }
    public function DeleteOrderProducts($rowId){
        if($rowId){
            $arProduct = self::getAllItems('wk_new_order_products',array('ID'=>$rowId));
            $newOrderId = $arProduct[0]["ORDER_ID"];
            $newOrderInfo =self::GetNewOrderInfo(array('ID'=>$newOrderId));
            
            $specId = $newOrderInfo[0]["SPEC_ID"];
                   
            $className = WKUtils::getClassNameByTableName("wk_new_order_products");
            $result = $className::delete($rowId); 
            if($newOrderInfo[0]['STATUS_DELIVERY']=='CW'){
                $arDeltaProducts[$arProduct[0]['PRODUCT_ID']] = $arProduct[0]['QUANTITY'];
                $result= self::synchNewOrderChanges($specId,false,$arDeltaProducts,false,false);
            }

        }

        return $result;

    }
    public function AddOrderProducts($arProps){
        if($arProps){

            $newOrderInfo = self::GetNewOrderInfo(array('ID'=>$arProps["ORDER_ID"]));
            $catalogProduct = self::GetCatalogProducts(array('ID'=>$arProps['PRODUCT_ID']));
            
            $specId = $newOrderInfo[0]["SPEC_ID"];
            $avaliableQuantProduct = self::GetAvaliableQuantity($specId,$catalogProduct[0]["EXTERNAL_ID"]);
            if( ($arProps['QUANTITY'] <= $avaliableQuantProduct[0]["AVAILABLE_QUANTITY"]) &&
                ($arProps['QUANTITY'] > 0)){
                   
                $className = WKUtils::getClassNameByTableName("wk_new_order_products");
                $result = $className::add($arProps); 
                if($newOrderInfo[0]["STATUS_DELIVERY"] == "CW"){
                    $arDeltaProducts[$arProps['PRODUCT_ID']] = $arProps['QUANTITY']*(-1);
                    $result= self::synchNewOrderChanges($specId,$arProps["ORDER_ID"], $arDeltaProducts,false);
                }
            }else{
                $msg = Loc::getMessage("WRONG_QUANTITY");
                $result = new Entity\AddResult();
                $result->addError(new Entity\EntityError($msg));
            }
        }
        return $result;
    }
    public function GetNewOrderProductsByOrder($orderId){
        if($orderId){
            $res = WK_NewOrderProductsTable::getList(array(
                'select' => array('*','ORDER_INFO'),
                "filter" => array(
                    "ORDER_ID"=>$orderId
                )
            ));
            while($result = $res->fetch())
            {
                $arProducts[$result["PRODUCT_ID"]]=$result;
                $arProductIds[]=$result["PRODUCT_ID"];
            }
            if($arProductIds){
                $products = self::GetCatalogProducts(array('ID'=> $arProductIds));
                foreach ($products as $key => $value) {
                    $arProducts[$value["ID"]]["PICTURE_SRC"] = $value["PICTURE_SRC"];
                    $arProducts[$value["ID"]]["MEASURE"] = $value["MEASURE"];
                    $arProducts[$value["ID"]]["NAME"] = $value["NAME"];
                    $arProducts[$value["ID"]]["EXTERNAL_ID"] = $value["EXTERNAL_ID"];

                    $resSection = \CIBlockSection::GetByID($value["IBLOCK_SECTION_ID"]);
                    if($ar_res = $resSection->GetNext()){
                        $arProducts[$value["ID"]]["DETAIL_PAGE_URL"] = $ar_res['SECTION_PAGE_URL'];
                    }
                }
            }else{
                $arProducts = false;
            }
        }

        return $arProducts;
    }
    public function GetCatalogProducts($arFilter = array()){
        if(Loader::IncludeModule("iblock")){
            if(!$arFilter["IBLOCK_ID"]){
                $arFilter["IBLOCK_ID"] = self::getCatalogIblock();
            }
            $arSelect = Array();
            $my_elements =  \CIBlockElement::GetList(
                Array("ID" => "ASC"),
                $arFilter,
                false,
                false,
                $arSelect
            );
            while($ar_fields = $my_elements->GetNext())
            {

                $arFile = \CFile::GetFileArray($ar_fields["PREVIEW_PICTURE"]);
                if($arFile){
                    $ar_fields["PICTURE_SRC"] = $arFile["SRC"];
                }
                $arMeasure = \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure($ar_fields["ID"]); 
                $ar_fields["MEASURE"] = $arMeasure[$ar_fields["ID"]]["MEASURE"]["SYMBOL_RUS"];
                $ar_fields["EXTERNAL_ID"] = $ar_fields["EXTERNAL_ID"];
                $arProducts[]=$ar_fields;
            }
        }

        return $arProducts;
    }
    public function CheckOrder($orderId){

        $arNewOrderProducts= self::GetNewOrderProductsByOrder($orderId);
        $orderInfo = self::GetNewOrderInfo(array('ID'=>$newOrderId));
        
        foreach($arNewOrderProducts as $productKeyID => $productItem){
            $arDeltaProducts[$productKeyID] = $productItem["QUANTITY"]*(-1);
            $specId = $productItem["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_SPEC_ID"];
        }
        if($arDeltaProducts){
            $resSync = self::synchNewOrderChanges($specId,$orderId,$arDeltaProducts,false);
        }
        if($resSync->isSuccess()){
            $result = self::UpdateItem('wk_new_order_info', $orderId ,array('STATUS_DELIVERY'=>'CW'));
        }
        return $result;
    }
    public function UpdateNewOrderProductsQuant($arProps){
        
        $arNewOrderProduct= self::GetNewOrderProductsByOrder($arProps["ORDER_ID"]);
        $specId = $arNewOrderProduct[$arProps["PRODUCT_ID"]]["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_SPEC_ID"];
        $avaliableQuant= self::GetAvaliableQuantity($specId,$arNewOrderProduct[$arProps["PRODUCT_ID"]]["EXTERNAL_ID"]);
        if($avaliableQuant){
            if($avaliableQuant[0]["AVAILABLE_QUANTITY"] >= ($arProps["QUANTITY"]-$avaliableQuant[0]["RESERVED_QUANTITY"])){

                $result = self::UpdateItem("wk_new_order_products",$arProps["ID"],$arProps);
                $arDeltaProducts[$arProps["PRODUCT_ID"]] = $arProps["QUANTITY"]-$avaliableQuant[0]["RESERVED_QUANTITY"];

                if($arDeltaProducts && $arNewOrderProduct[$arProps["PRODUCT_ID"]]["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_STATUS_DELIVERY"]=='CW'){
                    $arDeltaProducts[$arProps["PRODUCT_ID"]] = $arNewOrderProduct[$arProps["PRODUCT_ID"]]["QUANTITY"] - $arProps["QUANTITY"];
                    $resSync = self::synchNewOrderChanges($specId,$arProps["ORDER_ID"],$arDeltaProducts,false,false);
                }
            }else{
                $msg = Loc::getMessage("WRONG_QUANTITY");
                $result = new Entity\AddResult();
                $result->addError(new Entity\EntityError($msg));
            }
            return $result;
        }
    }
    public function UpdateItem($tableId,$rowID=false,$arProps=false){

        if($arProps && $rowID && $tableId){

            $className = WKUtils::getClassNameByTableName($tableId);
            $result = $className::update($rowID,$arProps);
        }
        return $result;
    }
    public function GetAvaliableQuantity($specID, $productId_1C){
        $specProduct = self::getAllItems("wk_spec_products",array(
            "SPEC_ID"=> $specID, 
            "ID_1C"=> $productId_1C
        ));

        return $specProduct;
    }
    public function ConfirmOrder($newOrderId){
        $orderProducts = self::GetNewOrderProductsByOrder($newOrderId);
        $orderInfo = self::GetNewOrderInfo(array('ID'=>$newOrderId));
        $specID = $orderInfo[0]["SPEC_ID"];
        $specProducts = self::getProductsBySpecId($specID);

        $orderComment = $orderInfo[0]['COMMENT'];

        $arTimeDelivery = self::GetTimeList(array('ID'=>$orderInfo[0]['TIME_DELIVERY']))[0];
        $timeDelivery = 'С '.$arTimeDelivery['TIME_FROM'].' До '.$arTimeDelivery['TIME_TO'];
        $dateDelivery = $orderInfo[0]['DATE_DELIVERY'];
        $deliveryFrom= $orderInfo[0]['WKOPT_W_K__NEW_ORDER_INFO_SPEC_CAREER'];
        $deliveryTo= $orderInfo[0]['WKOPT_W_K__NEW_ORDER_INFO_SPEC_DELIVERY_TO'];
        $basket = \Bitrix\Sale\Basket::create(SITE_ID);
        foreach ($orderProducts as $key => $product){

            $basketProductParams = array(
                'PRODUCT_ID' => $product["PRODUCT_ID"],
                'NAME' => $product["NAME"],
                'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
                'PRICE' => $specProducts[$product["EXTERNAL_ID"]]["PRICE"],
                'QUANTITY' => $product["QUANTITY"],
                'CUSTOM_PRICE' => 'Y',
                'CURRENCY' => $specProducts[$product["EXTERNAL_ID"]]["CURRENCY"]
            );

            $basketItem = $basket->createItem("catalog", $product['PRODUCT_ID']);
            $basketItem->setFields($basketProductParams);
            $getterName = $product["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_USER_GETTER_NAME"];
            $getterPhone = $product["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_USER_GETTER_PHONE"];
            $userId = $product["WKOPT_W_K__NEW_ORDER_PRODUCTS_ORDER_INFO_USER_ID"];
          
        }

        $rsUser =\CUser::GetByID($userId);
        $arUser = $rsUser->Fetch();
        $contragentId = $arUser["UF_WK_ID_CONTRAGENT"];

        $order = \Bitrix\Sale\Order::create(SITE_ID, $contragentId[0]);
        $order->setPersonTypeId(1);
        $order->setBasket($basket);
        ?><pre><?//var_dump($basket);?></pre><?

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem(
            \Bitrix\Sale\Delivery\Services\Manager::getObjectById(1)
        );
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        
        foreach ($basket as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }
        
        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem(
            \Bitrix\Sale\PaySystem\Manager::getObjectById(1)
        );

        $payment->setField("SUM", $order->getPrice());
        $payment->setField("CURRENCY", $order->getCurrency());

        $order->setField(
            'COMMENTS', $orderComment.' #Предпочитаемое время доставки: //'. $timeDelivery.'#'
        );
        
        $result = $order->save();
            $order = \Bitrix\Sale\Order::load($result->getId());
        
        $propertyCollection = $order->getPropertyCollection();
        foreach ($propertyCollection as $obProp) {
            $arProp = $obProp->getProperty();
            if($arProp["CODE"] == "DELIVERY_LOCATION_TO" ){
                $obProp->setValue($deliveryTo);
            }
            if($arProp["CODE"] == "DELIVERY_LOCATION_FROM" ){
        
                $obProp->setValue($deliveryFrom);
            }
            if($arProp["CODE"] == "USER_GETTER_PHONE" ){
        
                $obProp->setValue($getterPhone);
            }
            if($arProp["CODE"] == "USER_GETTER_NAME" ){
        
                $obProp->setValue($getterName);
            }
            if($arProp["CODE"] == "DATE_DELIVERY" ){
        
                $obProp->setValue($dateDelivery);
            }
        }
        $result = $order->save();
        if($result->isSuccess()){
            self::UpdateItem('wk_new_order_info',$newOrderId,array('STATUS_DELIVERY'=>'CO'));
        }
        return $result;
    }
    public function AddNewItem($tableId=false,$arProps=false){

        if($arProps && $tableId){
            if($tableId == "wk_spec_products" && $arProps["AVAILABLE_QUANTITY"] == 0){
                $arProps["AVAILABLE_QUANTITY"] = $arProps["MAX_QUANTITY"];
            }
            $className = WKUtils::getClassNameByTableName($tableId);
            $result = $className::add($arProps);
           
        }
        return $result;

    }
   
    public function deteteSpecification($specId=false){
        if($specId){

            $specClassName = WKUtils::getClassNameByTableName('wk_spec');
            $result = $specClassName::delete($specId);

            $newOrder = self::GetNewOrderInfo(array('SPEC_ID'=>$specId));
            foreach ($newOrder as $order) {
                $newOrderProducts = self::deleteOrder($order["ID"],$needSync=false);
            }

            $specProducts = self::getProductsBySpecId($specId);
            $specProductsClassName = WKUtils::getClassNameByTableName('wk_spec_products');
            foreach ($specProducts as $key => $value) {
                $result = $specProductsClassName::delete($value['ID']);
            }
        }
        return $result;
    }
    public function DeleteItem($tableId,$rowID=false){
        if($rowID && $tableId){
            $className = WKUtils::getClassNameByTableName($tableId);
            $result = $className::delete($rowID);
        }
        return $result;
    }
    public function getAllItems($tableId,$filter=false,$order=false){
        if($tableId){
            $className = WKUtils::getClassNameByTableName($tableId);
            $query = $className::query();
            if($filter){
                $query->setFilter($filter);
            }
            if($order){
                $query->setOrder($order);
            }
            $query->setSelect(['*']);
            $db_res = $query->exec();
            if($vals= $db_res->fetchAll()){
                $arVals = $vals;
            }
            return $arVals;
        }
    }
    public function getSpecificationsByUser($userId){
        $filter = array(
            'USER_ID_1C'=>$userId,
            "ACTIVE"=>"Y"
        );
        $arSpec = self::getAllItems('wk_spec',$filter);

        return $arSpec;
    }
    public function getSpecificationsById($specId){
        $filter = array(
            'ID'=>$specId,
            "ACTIVE"=>"Y"
        );
        $arSpec = self::getAllItems('wk_spec',$filter);

        return $arSpec;
    }
    
    public function FormatDate( $dateStr = null){
        if($dateStr){

            $date= date( "d.m.Y", strtotime($dateStr));

            $formatDate =  new \Bitrix\Main\Type\Date($date);
            return $formatDate;
        }
    }
}
?>
