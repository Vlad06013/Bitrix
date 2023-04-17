<?
use Bitrix\Main\Entity;
use Bitrix\wkopt;

require_once($this->GetPath(true)."/lib/model/ORM_Classes.php");
Bitrix\wkopt\WK_SpecTable::getEntity()->createDbTable();
Bitrix\wkopt\WK_SpecProductsTable::getEntity()->createDbTable();
Bitrix\wkopt\WK_NewOrderInfoTable::getEntity()->createDbTable();
Bitrix\wkopt\WK_NewOrderProductsTable::getEntity()->createDbTable();
Bitrix\wkopt\WK_SpecTimeDeliveryListTable::getEntity()->createDbTable();
Bitrix\wkopt\WK_GroupRightsTable::getEntity()->createDbTable();
?>