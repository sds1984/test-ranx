<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Iblock;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use \Bitrix\Main\Data\Cache;

Loader::IncludeModule("iblock");

define('TTL', 60 * 60 * 24);

$arElements = [];
$arSectionIds = [];
$result = [];
$page = 0;
$pageSize = 10;

$news = Iblock\Elements\ElementNewsTable::getList(array(
	'select' => array(
		'ID',
		'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
		'NAME',
		'PREVIEW_PICTURE',
		'IBLOCK_SECTION_ID',
		'DATE_CREATE',
		'TAGS',
		'AUTHOR_' => 'AUTHOR.ELEMENT'
	),
	'filter' => array(
		'>=DATE_CREATE' => '01.01.2015',
		'<=DATE_CREATE' => '31.12.2025',
	),
	'limit' => $pageSize,
	'offset' => $page * $pageSize,
	'cache' => array(
		'ttl' => TTL,
		"cache_joins" => true,
	),
));

while ($news_element = $news->fetch()) 
{
	if (!in_array($news_element['IBLOCK_SECTION_ID'], $arSectionIds) && !is_null($news_element['IBLOCK_SECTION_ID']))
		$arSectionIds[] = $news_element['IBLOCK_SECTION_ID'];
	
	$arElements[] = $news_element;
}

$sections = SectionTable::getList(array(
	'filter' => array('ID' => $arSectionIds),
	'select' => array('ID', 'NAME')
));

while ($section = $sections->fetch()) {
	$arSections[$section['ID']] = $section['NAME'];
}

foreach ($arElements as $element) {

	$date = new DateTime();
	$date->setTimestamp(strtotime($element['DATE_CREATE']));
	$intlFormatter = new IntlDateFormatter('ru_RU', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
	$intlFormatter->setPattern('d MMMM Y H:mm');

	$result[] = array(
		'ID' => $element['ID'],
		'URL' => CIBlock::ReplaceDetailUrl($element['DETAIL_PAGE_URL'], $element, false, 'E') . $element['ID'],
		'IMAGE' => $element['PREVIEW_PICTURE'],
		'NAME' => $element['NAME'],
		'SECTION_NAME' => $arSections[$element['IBLOCK_SECTION_ID']],
		'DATE' => $intlFormatter->format($date),
		'AUTHOR' => $element['AUTHOR_NAME'],
		'TAGS' => $element['TAGS']
	);
}

echo "<pre>";
print_r(json_encode($result, JSON_UNESCAPED_UNICODE));
echo "</pre>";