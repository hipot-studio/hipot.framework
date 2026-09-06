<?
defined('B_PROLOG_INCLUDED') || die();
/**
 * iblock structure first look
 * @version 2.X
 * @author 2017, hipot AT icloud DOT com
 */

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 */

use Bitrix\Main\Page\Asset;

IncludeModuleLangFile(__FILE__);

$MODULE_ID = "hipot.framework";
CModule::IncludeModule($MODULE_ID);

// select
CModule::IncludeModule('iblock');

$iblocks = [];
$rs = CIBlock::GetList(['sort' => 'asc', 'name' => 'asc'], []);
while ($ib = $rs->Fetch()) {
	$iblocks[$ib['ID']] = [
		'ID' => $ib['ID'],
		'NAME' => $ib['NAME'],
		'IBLOCK_TYPE_ID' => $ib['NAME'],
	];
}

$relayProps = [];
$iblockRelayProps = [];
$allProps = [];

$rs = CIBlockProperty::GetList(['sort' => 'asc', 'NAME' => 'ASC'], [/*'PROPERTY_TYPE' => 'E'*/]);
while ($pr = $rs->Fetch()) {

	$allProps[$pr['IBLOCK_ID']][$pr['ID']] = $pr;

	if ($pr['PROPERTY_TYPE'] != 'E') {
		continue;
	}

	$relayProps[$pr['ID']] = [
		'ID' => $pr['ID'],
		'NAME' => $pr['NAME'],
		'CODE' => $pr['CODE'],
	];

	if ((int)$pr['LINK_IBLOCK_ID'] == 0) {
		$pr['LINK_IBLOCK_ID'] = 'WTF';
	}

	$iblockRelayProps[$pr['IBLOCK_ID']][] = [
		'TO' => $pr['LINK_IBLOCK_ID'],
		'LINK_ID' => $pr['ID']
	];
}

foreach ($iblocks as $ibid => $v) {
	if (!isset($iblockRelayProps[$ibid])) {
		$iblockRelayProps[$ibid] = [];
	}
}


// print
$aTabs = [
	[
		"DIV" => "edit1", "TAB" => GetMessage("hipot.framework_MAIN_TAB_SET"),
		"ICON" => "settings", "TITLE" => GetMessage("hipot.framework_MAIN_TAB_SET")
	]
];
$tabControl = new CAdminTabControl("tabControlIblockRelays" . time(), $aTabs);

$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2">

		<?
		$asset = Asset::getInstance();
		CJSCore::Init(['jquery3']);

		ob_start();
		?>
		<script type="text/javascript" data-skip-moving="true"
				src="<?= CUtil::GetAdditionalFileURL("/bitrix/js/" . $MODULE_ID . "/cytoscape/cytoscape.js") ?>"></script>

		<script type="text/javascript" data-skip-moving="true"
				src="<?= CUtil::GetAdditionalFileURL("/bitrix/js/" . $MODULE_ID . "/cytoscape/arbor.js") ?>"></script>
		<?
		$APPLICATION->oAsset->addString(ob_get_clean());
		?>


		<table width="100%" align="center">
			<tr valign="top">
				<td width="40%">
					<?
					foreach ($iblockRelayProps as $iblock => $relayEx) {
						if (!isset($iblocks[$iblock]['NAME'])) {
							continue;
						}
						?>
						<div>
							<b><?= $iblocks[$iblock]['NAME'] ?> (<?= $iblocks[$iblock]['ID'] ?>)</b>
							<?
							foreach ($relayEx as $relay) {
								?>
								<small><?= $relayProps[$relay['LINK_ID']]['NAME'] ?> [<?= $relayProps[$relay['LINK_ID']]['CODE'] ?>]</small>
								&mdash;&gt; <b><?= $iblocks[$relay['TO']]['NAME'] ?> (<?
									echo($relay['TO'] == 'WTF' ? '<font style="color:red">' : '');
									echo $relay['TO'];
									echo($relay['TO'] == 'WTF' ? '</font>' : ''); ?>)</b><br/><br/>
								<?
							}
							?>
						</div>
						<?
					}
					?>
				</td>
				<td>
					<h3><? GetMessage("hipot.framework_ALL_IB_PROPS") ?></h3>
					<? foreach ($iblocks as $id => $iblock) {
						$ibPrs = $allProps[$iblock['ID']];
						?>
						<div>
							<b><?= $iblock['NAME'] ?> (<?= $iblock['ID'] ?>)</b>

							<? if (count($ibPrs) > 0) { ?>
								<ul>
									<? foreach ($ibPrs as $pr) { ?>
										<li>
											<small><?= $pr['NAME'] ?> (<?= $pr['ID'] ?>, <b><?= $pr['CODE'] ?></b>) [<?= $pr['PROPERTY_TYPE'] ?>]</small>
										</li>
									<? } ?>
								</ul>
							<? } ?>
						</div>
					<? } ?>
				</td>
			<tr>
		</table>

		<script type="text/javascript">
			$(function () {
				var nodes = [], edges = [];
				<?
				$nodes = [];
				foreach ($iblocks as $iblock => $bl) {
					$nodes[] = ['id' => $iblock, 'name' => addslashes(str_replace(["\"", "\\", "\n", "\r"], '', $bl['NAME']) . ' (' . $bl['ID'] . ')'),
						'weight' => 65, 'height' => 174
					];
				}
				?>
				nodes = <?=CUtil::PhpToJSObject($nodes)?>;
				//console.log(nodes);

				<?
				$egles = [];
				foreach ($iblockRelayProps as $iblock => $relayEx) {
					foreach ($relayEx as $relay) {
						if (!isset($iblocks[$relay['TO']])) {
							continue;
						}

						$egles[] = [
							'data' => [
								'source' => 'n0'/*$iblock*/, 'target' => 'n1'/*$relay['TO']*/,
								'sash' => 'none', 'width' => 4, 'label' => $allProps[$iblock][ $relay['LINK_ID'] ]['CODE']
							]
						];
					}
				}?>

				edges = <?=CUtil::PhpToJSObject($egles)?>;
				//console.log(edges);
				<?
				unset($nodes, $egles);
				?>

				/**
				 * способ расстановки вершин графов
				 */
				var arbor_layout = {
					name: 'arbor',
					liveUpdate: true, // whether to show the layout as it's running
					ready: undefined, // callback on layoutready
					stop: undefined, // callback on layoutstop
					maxSimulationTime: 4000, // max length in ms to run the layout
					fit: true, // reset viewport to fit default simulationBounds
					padding: [50, 50, 50, 50], // top, right, bottom, left
					simulationBounds: undefined, // [x1, y1, x2, y2]; [0, 0, width, height] by default
					ungrabifyWhileSimulating: true, // so you can't drag nodes during layout

					// forces used by arbor (use arbor default on undefined)
					repulsion: undefined,
					stiffness: undefined,
					friction: undefined,
					gravity: true,
					fps: undefined,
					precision: undefined,

					// static numbers or functions that dynamically return what these
					// values should be for each element
					nodeMass: undefined,
					edgeLength: undefined,

					stepSize: 1, // size of timestep in simulation

					// function that returns true if the system is stable to indicate
					// that the layout can be stopped
					stableEnergy: function (energy) {
						var e = energy;
						return (e.max <= 0.5) || (e.mean <= 0.3);
					}
				};

				var options = {
					showOverlay: false,
					minZoom: 0.5,
					maxZoom: 2,
					zoomingEnabled: false,

					layout: arbor_layout,

					style: cytoscape.stylesheet()
						.selector('node').css({
							'content': 'data(name)',
							'font-family': 'consolas,monospace',
							'font-size': 12,
							'text-outline-width': 0,
							'text-outline-color': '#000',
							'text-opacity': 0.9,
							'text-valign': 'center',
							'color': '#000',
							'width': 'mapData(weight, 30, 80, 20, 50)',
							'height': 'mapData(height, 0, 200, 10, 45)',
							'border-color': '#fff',
							'background-color': '#888'
						})
						.selector(':selected').css({})
						.selector('edge').css({
							'width': 'data(width)',
							'target-arrow-shape': 'triangle',
							'source-arrow-shape': 'data(sash)',
							'line-color': '#a9cda9',
							'source-arrow-color': '#a9cda9',
							'target-arrow-color': '#a9cda9',
							'content': 'data(label)',
							'edge-text-rotation': 'autorotate',
							'font-family': 'consolas,monospace',
							'font-size': 11,
							'color': '#888'
						}),

					elements: {
						nodes: nodes,
						edges: edges
					}
				};

				window.setTimeout(function () {
					$('#graph').cytoscape(options);
				}, 2000);
			});
		</script>


		<div id="graph" class="graph_canva" style="width:100%; min-height:900px;"></div>
	</td>
</tr>

<?
$tabControl->EndTab();
$tabControl->Buttons();
$tabControl->End();
?>


