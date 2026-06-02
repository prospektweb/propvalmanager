<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?//$arOptions from \Aspro\Functions\CAsproPremier::showBlockHtml?>
<?
use Bitrix\Main\Localization\Loc;

$arOptions = $arConfig['PARAMS'];
$arItem = $arConfig['ITEM'];
$itemProps = $arItem['CHARACTERISTICS'] || $arItem['PROPS'] || $arItem['OFFER_PROP'];
$itemProps = $arItem['CHARACTERISTICS'] ?: $arItem['PROPS'];

$propIterator = 0;
$maxVisibleProps = $arOptions['VISIBLE_PROP_COUNT'] ?? PHP_INT_MAX;
$bShowTabLink = false;

$wrapperClassList = ['properties properties__container js-offers-prop gap gap--'.$arOptions['GAP_SIZE'].' grid-list grid-list--items '];
if ($arOptions['WRAPPER_CLASSES']) {
	$wrapperClassList[] = $arOptions['WRAPPER_CLASSES'];
}
if ($arOptions['HIDE_MOBILE'] !== false) {
	$wrapperClassList[] = 'compact-hidden-t600';
}

if(!$arOptions['IS_DETAIL'] && (!$itemProps && !$arItem['OFFER_PROP'])) {
    $wrapperClassList[] = 'hidden';
}

$wrapperClassList = TSolution\Utils::implodeClasses($wrapperClassList);?>

<?if ($arOptions['IS_DETAIL']):?>
    <div class="properties-wrapper grid-list__item">
<?endif;?>

		<div class="<?=$wrapperClassList;?>"<?=$arOptions['VISIBLE_PROP_COUNT'] ? ' data-visible_prop_count="'.$arOptions['VISIBLE_PROP_COUNT'].'"': '';?>>
			<?if ($arOptions['TITLE']):?>
				<div class="fw-500 font_14 color_222">
					<?=$arOptions['TITLE'];?>
				</div>
			<?endif;?>
			<?
			if ($itemProps) {
				foreach ($itemProps as $arProp) {
					if (empty($arProp['VALUE'])) continue;

					++$propIterator;

					if ($propIterator <= $maxVisibleProps) {
						TSolution\Functions::showBlockHtml([
							'FILE' => 'catalog/props/list.php',
							'ITEM' => $arProp,
							'PARAMS' => $arOptions + ['IS_ITEM_PROP' => true],
						]);
					}					
				}
			}

			if ($arItem['OFFER_PROP']) {
				foreach ($arItem['OFFER_PROP'] as $arProp) {
					if (empty($arProp['VALUE'])) continue;

					++$propIterator;

					if ($propIterator <= $maxVisibleProps) {
						TSolution\Functions::showBlockHtml([
							'FILE' => 'catalog/props/list.php',
							'ITEM' => $arProp,
							'PARAMS' => $arOptions,
						]);
					}
				}
			}
			?>
		</div>

		<?
		$bShowTabLink = $propIterator > $maxVisibleProps;
		?>

<?if ($arOptions['IS_DETAIL']):?>
		<span class="catalog-detail__pseudo-link-chars properties__show-more link-opacity-color link-opacity-color--hover pointer font_13 mt mt--8 <?=($bShowTabLink ? '' : ' hidden')?>">
			<span class="choise dotted" data-block="char"><?=Loc::getMessage('MORE_CHAR_BOTTOM'); ?></span>
		</span>
    </div>
<?endif;?>
