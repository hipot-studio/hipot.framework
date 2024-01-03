<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 06.03.2023 12:56
 * @version pre 1.0
 */
namespace Hipot\Services;

use Hipot\Utils\UUtils;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

final class SimpleXlsx
{
	private Spreadsheet $spreadsheet;
	private int $rowsAdded = 0;

	public function __construct()
	{
	}

	public function __destruct()
	{
		unset($this->spreadsheet);
	}

	/**
	 * @param array{
	 *     'spreadsheet':array{'creator':string, 'title':string, 'description':string}
	 * } $params
	 */
	public function create(array $params = []): void
	{
		// Create new Spreadsheet object
		$this->spreadsheet = new Spreadsheet();

		// Set document properties
		$this->spreadsheet->getProperties()->setCreator($params['spreadsheet']['creator'])
			->setLastModifiedBy($params['spreadsheet']['creator'])
			->setTitle($params['spreadsheet']['title'])
			->setSubject($params['spreadsheet']['title'])
			->setDescription($params['spreadsheet']['description']);

		$this->spreadsheet->setActiveSheetIndex(0)->setTitle($params['spreadsheet']['title']);
	}

	/**
	 * for styles see https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
	 *
	 * @param array $row
	 * @param array $styles
	 * @return void
	 */
	public function addRow(array $row = [], array $styles = []): void
	{
		$this->rowsAdded++;
		$column = 1;
		foreach ($row as $field => $value) {
			$cellAddress = CellAddress::fromColumnRowArray([$column++, $this->rowsAdded]);
			$this->spreadsheet->getActiveSheet()->setCellValueExplicit($cellAddress, (string)$value, DataType::TYPE_STRING);

			if (isset($styles[$field])) {
				if (isset($styles[$field]['autosize'])) {
					$this->spreadsheet->getActiveSheet()->getColumnDimension( Coordinate::stringFromColumnIndex($column) )->setAutoSize(true);
				}
				$this->spreadsheet->getActiveSheet()->getStyle($cellAddress)->applyFromArray($styles[$field]);
			}
		}
	}

	public function save(string $fileName): bool
	{
		try {
			$writer = IOFactory::createWriter($this->spreadsheet, IOFactory::WRITER_XLSX);
			$writer->save($fileName);
		} catch (\Exception $e) {
			UUtils::logException($e);
			return false;
		}
		return file_exists($fileName);
	}

	public static function getHeaderAutoStyle(): array
	{
		return [
			'font' => [
				'bold' => true,
			],
			'fill' => [
				'fillType' => Fill::FILL_SOLID,
				'startColor' => [
					'argb' => 'FFE3E3E3',
				],
			],
			'autosize' => true
		];
	}
}