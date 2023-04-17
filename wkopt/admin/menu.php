<?
IncludeModuleLangFile(__FILE__);
use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use \Bitrix\wkopt\WKOptSpecification;

CModule::IncludeModule('wkopt');
$moduleId = 'wkopt';
if (WKOptSpecification::userHaveModuleSettingsAccsess()){

    EventManager::getInstance()->addEventHandler("main", "OnBuildGlobalMenu", function (&$arGlobalMenu, &$arModuleMenu) {
        $aMenu['global_menu_custom'] = [
            'menu_id' => 'custom',
            'text' => 'Web-kaktus',
            'title' => 'Web-kaktus',
            'url' => 'settingss.php?lang=ru',
            'sort' => 1000,
            'items_id' => 'global_menu_custom',
            'help_section' => 'custom',
            'items' => [
                [
                    'parent_menu' => 'global_menu_custom',
                    'sort'        => 10,
                    'url'         => 'WKOpt_spec_edit.php',
                    'text'        => 'Кабинет оптового покупателя1',
                    'title'       => 'wkopt',
                    'icon'        => 'fav_menu_icon',
                    'page_icon'   => 'fav_menu_icon',
                ],
            ],
        ];
        return $aMenu;
    });
}
