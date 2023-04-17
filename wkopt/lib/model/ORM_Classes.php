<?
namespace Bitrix\wkopt;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Type\Date;
use Bitrix\Main;
use \Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Fields\Relations;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::IncludeModule('sale');

class WK_SpecTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'wk_spec';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\ReferenceField(
                'SPEC_PRODUCTS',
               WK_SpecProductsTable::class,
                ['=this.ID' => 'ref.SPEC_ID']
            ),

            new Entity\StringField('ID_1C', array(
            )),
            new Entity\StringField('USER_ID_1C', array(
            )),
            new Entity\StringField('CAREER', array(
                'required' => true
            )),
            new Entity\StringField('DELIVERY_TO', array(
                'required' => true
            )),
            new Entity\StringField('VERSION', array(
            )),
            new Entity\DateField('DATE_CREATE', array(
                'nullable' => false,
                'default_value'=> new Type\Date
            )),
            new Entity\StringField('STATUS', array(
                'default_value'=>'CW'
            )),
            new Entity\BooleanField('PICKUP', array(
                'values' => array('N', 'Y'),
                'default_value'=> "N"
            )),
            new Entity\BooleanField('ACTIVE', array(
                'values' => array('N', 'Y'),
                'default_value'=> "N"
            )),
        );
    }
}

class WK_SpecProductsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'wk_spec_products';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),

            new Entity\StringField('ID_1C', array(
                'required' => true
            )),
            new Entity\IntegerField('MAX_QUANTITY', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\IntegerField('AVAILABLE_QUANTITY', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\IntegerField('RESERVED_QUANTITY', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\StringField('SPEC_ID', array(
                'required' => true
            )),
            new Entity\FloatField('PRICE', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
        );
    }
}


class WK_NewOrderInfoTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'wk_new_order_info';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),

            new Entity\IntegerField('TIME_DELIVERY', array(
                'nullable' => true,
            )),
            new Entity\ReferenceField(
                'TIME',
                WK_SpecTimeDeliveryList::class,
                ['=this.TIME_DELIVERY' => 'ref.ID']
            ),

            new Entity\DateField('DATE_DELIVERY', array(
                'nullable' => true,
                'default_value'=> null
            )),
            new Entity\DateField('DATE_CREATE', array(
                'nullable' => false,
                'default_value'=> new Type\Date
            )),
            new Entity\TextField('COMMENT', array(
            )),
            new Entity\IntegerField('SPEC_ID', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\ReferenceField(
                'SPEC',
                WK_SpecTable::class,
                ['=this.SPEC_ID' => 'ref.ID']
            ),
           
            new Entity\StringField('USER_GETTER_NAME', array(
            )),
            new Entity\StringField('USER_GETTER_PHONE', array(
            )),
            
            new Entity\StringField('STATUS_DELIVERY', array(
                'default_value'=>'CN'
            )),
            new Entity\IntegerField('USER_ID', array(
                'nullable' => true,
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
        );
    }
}

class WK_NewOrderProductsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'wk_new_order_products';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
             new Entity\IntegerField('QUANTITY', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\IntegerField('PRODUCT_ID', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\IntegerField('QUANTITY', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            new Entity\IntegerField('ORDER_ID', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\RegExp('/^[\d]+$/')
                    );
                }
            )),
            
            new Entity\ReferenceField(
                'ORDER_INFO',
                WK_NewOrderInfoTable::class,
                ['=this.ORDER_ID' => 'ref.ID']
            ),
        );
    }
}

class WK_SpecTimeDeliveryListTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'wk_spec_delivery_timelist';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\IntegerField('TIME_FROM', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\Range(1, 24)
                    );
                }
            )),
            new Entity\IntegerField('TIME_TO', array(
                'validation' => function() {
                    return array(
                        new Entity\Validator\Range(1, 24)
                    );
                }
            )),
        );
    }
}
class WK_GroupRightsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'wk_group_rights';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\IntegerField('GROUP_ID', array(
            )),
            // new Entity\StringField('GROUP_NAME', array(
            // )),
            new Entity\StringField('RIGHTS', array(
            )),
            // new Entity\BooleanField('STATUS', array(
            //     'nullable' => true,
            //     'values' => array('N', 'Y'),
            //     'default_value'=> "N"
            // )),
        ); 
    }
}

// AddMessage2Log(WK_NewOrderProductsTable::getEntity()->compileDbTableStructureDump());
 ?>
