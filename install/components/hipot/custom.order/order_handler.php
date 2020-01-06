<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 18.01.2019 17:25
 * @version pre 1.0
 */

use \Hipot\BitrixUtils\IblockUtils as hiIU,
	\Hipot\BitrixUtils\SaleUtils as hiSU,
	\Hipot\BitrixUtils\HiBlock as hiBL;

$parentGood = false;

/****** превращаем элемент инфоблока 34 в заказ ******************/
AddEventHandler('iblock', 'OnAfterIBlockElementAdd', function (&$arFields) use ($basket, &$parentGood) {
	$arFields["ID"] = (int)$arFields["ID"];

	$orderIblockId = 34;
	$catalogIblockId = 7;
	$tpCatalogIblock = 18;

	if ($arFields['IBLOCK_ID'] != $orderIblockId || $arFields["ID"] <= 0) {
		return;
	}

	foreach (['iblock', 'sale', 'catalog', 'hiblock'] as $moduleId) {
		\Bitrix\Main\Loader::includeModule($moduleId);
	}

	$pr = hiIU::selectElementProperties($arFields["ID"], $orderIblockId);

	$goodsParsed = [];
	foreach ($pr['GOODS'] as $good) {
		$bMatch = preg_match('#^\[(.+?)\]\s*(.+?),\s*р\.\s*(\d+)\s*(\d+)\.00\s*шт\.\s*(\d+)\s*(RUB)#is', $good['VALUE'], $GoodParts);
		if (! $bMatch) {
			throw new \Exception('Error: no regular Expr goods ' . $good['VALUE']);
			continue;
		}
		foreach ($GoodParts as &$p) {
			if (is_string($p)) {
				$p = trim($p);
			}
		}
		unset($p);

		if (is_numeric($GoodParts['1'])) {
			$GoodId = array_splice($GoodParts, 1, 1);
			$GoodIdOne = (int)$GoodId[0];

			$baseFoodFilter = ['ID' => $GoodIdOne, 'IBLOCK_ID' => $tpCatalogIblock];
		}
		// old code wont work
		else {
			$baseFoodFilter = ['NAME' => trim($GoodParts['1']), 'IBLOCK_ID' => $catalogIblockId];
		}
		$goodIdRs = \CIBlockElement::GetList([], $baseFoodFilter, false, ['nTopCount' => 1],
			['ID', 'IBLOCK_ID', 'NAME'/*, 'CATALOG_GROUP_1', 'CATALOG_GROUP_2'*/]
		);
		while ($ar = $goodIdRs->GetNext()) {
			if ($ar['ID'] || (ToLower(trim($GoodParts['1'])) == ToLower(trim($ar['NAME'])))) {
				$goodId = $ar;
				break;
			}
		}
		if ((int)$goodId['ID'] <= 0) {
			throw new \Exception('Error: good ' . trim($GoodParts['1']) . ' not found ' . print_r($baseFoodFilter, true));
			return;
		}

		$dm = hiBL::getDataManagerByHiCode('SizeReference');
		$sizeDB = $dm::getList([
			'select' => ['*'],
			'filter' => ['UF_NAME' => $GoodParts['2']]
		])->fetch();

		$arSkuFilter = ['IBLOCK_ID' => $tpCatalogIblock, 'CML2_LINK' => $goodId['ID'], 'PROPERTY_SIZE' => $sizeDB['UF_XML_ID']];
		$goodId = \CIBlockElement::GetList([], $arSkuFilter, false, false, ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL'])->GetNext();
		$parentGood[ $goodId['ID'] ] = $goodId;

		$goodsParsed[ (int)$GoodParts[3] ] = [
			'ID' => $goodId['ID'],
			'CUSTOM_PRICE' => $GoodParts['4'],
			'CURRENCY'	   => $GoodParts['5'],
			'NAME'         => $GoodParts['1']
		];

		foreach ($basket as $b) {
			if ($b['PRODUCT_ID'] == $goodId['ID']) {
				foreach ($b as $k => $v) {
					if (! isset($goodsParsed[ (int)$GoodParts[3] ][ $k ])) {
						$goodsParsed[ (int)$GoodParts[3] ][ $k ] = $v;
					}
				}
			}
		}
	}

	$payDescr   = (trim($pr['PAY']['VALUE']) != '' ? "\n\nОплата: " . $pr['PAY']['VALUE'] : '');
	$smsNeed    =  ($pr['AGREE_STATUS_SMS']['VALUE'] ? "\n\nСогласен получать SMS" : '');
	$descr      =  $pr['COMMENT']['VALUE'] . $payDescr . $smsNeed;

	$orderProps = [
		'FIO_HIDDEN'		=> $pr['FIO']['VALUE'],
		'EMAIL'				=> $pr['EMAIL']['VALUE'],
		'PHONE'				=> $pr['PHONE']['VALUE'],
		'ADDRESS'			=> hiSU::GetFullLocationNameById($pr['CITY']['VALUE']) . ' :: ' . $pr['ADDRESS']['VALUE'],
		'NAME'				=> $pr['FIO']['VALUE'],
		'SURNAME'			=> $pr['FIO']['VALUE'],
		'USER_DESCRIPTION'	=> $descr,
		'DESCRIPTION'       => $descr,
		'PRICE'				=> $pr['FULL_PRICE']['VALUE'],
		'CURRENCY'          => \COption::GetOptionString('sale', 'default_currency'),
		'USER_ID'			=> (int)$GLOBALS['USER']->GetID(),
		'LOCATION'          => $pr['CITY']['VALUE'],
	];


	$orderId = hiSU::addOrder($orderProps, $goodsParsed, null, 1, $pr['PAY']['VALUE'], $pr['DELIVERY']['VALUE']);

	if ($orderId) {
		$GLOBALS['last_order_id'] = $orderId;
		\CIBlockElement::SetPropertyValuesEx($arFields["ID"], $orderIblockId, [
			'ORDER_ID'		        => $orderId,
			'USER_DESCRIPTION'      => $descr
		]);
	}
});
/****** // end event handler to add $el->Add **************/