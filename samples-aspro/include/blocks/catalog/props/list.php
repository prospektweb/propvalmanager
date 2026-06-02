<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true ) die();
use Bitrix\Main\Localization\Loc;
//options from TSolution\Functions::showBlockHtml
$arOptions = $arConfig['PARAMS'];
$arProp = $arConfig['ITEM'] ?? [];

// item class
$itemClassList = ['properties__item'];
if ($arOptions['IS_ITEM_PROP']) {
	$itemClassList[] = 'js-prop-replace';
} else {
	$itemClassList[] = 'js-prop';
}
if ($arOptions['ITEM_CLASSES']) {
	$itemClassList[] = $arOptions['ITEM_CLASSES'];
}
if ($arOptions['FONT_CLASSES']) {
	$itemClassList[] = $arOptions['FONT_CLASSES'];
}
if ($arOptions['TEXT_CLASSES']) {
	$itemClassList[] = $arOptions['TEXT_CLASSES'];
}

// title class
$titleClassList = ['properties__title properties__item--inline js-prop-title'];

// value class
$valueClassList = ['properties__value color_222 properties__item--inline js-prop-value'];

$itemClassList = TSolution\Utils::implodeClasses($itemClassList);
$titleClassList = TSolution\Utils::implodeClasses($titleClassList);
$valueClassList = TSolution\Utils::implodeClasses($valueClassList);
?>

<div class="<?=$itemClassList;?>">
	<div class="<?=$titleClassList;?>"><?=$arProp['NAME'] ?? '#PROP_TITLE#';?><?
		if ($arOptions["SHOW_HINTS"] && $arProp['HINT']):
		?><div class="hint hint--down">
				<span class="hint__icon rounded bg-theme-hover border-theme-hover bordered"><i>?</i></span>
				<div class="tooltip"><?=$arProp["HINT"];?></div>
			</div><?
		endif;
?></div>
	<span class="properties__hr properties__item--inline">:</span>
	<div class="<?=$valueClassList;?>"><?=$arProp['DISPLAY_VALUE'] ? implode(', ', (array)$arProp['DISPLAY_VALUE']) : '#PROP_VALUE#';?></div>
</div>
