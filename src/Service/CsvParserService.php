<?php

namespace App\Service;

use App\Entity\Publisher;
use App\Entity\Wholesaler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvParserService
{
    private const CSV_ROWS_LIMIT = 100000;

    private static array $EAN_HEADER_NAMES = ['EAN', 'Ean'];
    private static array $PUBLISHER_HEADER_NAMES = ['Publisher', 'Producent'];
    private static array $CATEGORY_HEADER_NAMES = ['Category', 'Nazwa kategorii'];
    private static array $INSTOCK_HEADER_NAMES = ['InStock', 'Stan'];
    private static array $PRODUCT_ID_HEADER_NAMES = ['IDProduct', 'Kod towaru'];

    public function parseFile(UploadedFile $file, Wholesaler $wholesaler): array
    {
        $parsedData = [];

        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $separator = $this->detectSeparator($handle);
            $header = fgetcsv($handle, self::CSV_ROWS_LIMIT, $separator);
            $headerIndexes = $this->getHeaderIndexes($header);

            if ($this->hasRequiredIndexes($headerIndexes)) {
                while (($row = fgetcsv($handle, self::CSV_ROWS_LIMIT, $separator)) !== false) {
                    if ($this->isValidRow($row, $headerIndexes)) {
                        foreach ($wholesaler->getPublishers() as $publisher) {
                            if ($this->publisherIsInRow($publisher, $row[$headerIndexes['publisherIndex']],
                                $row[$headerIndexes['categoryIndex']])) {
                                $parsedData[] = [
                                    'IdProduct' => $row[$headerIndexes['idProductIndex']],
                                    'EAN' => $row[$headerIndexes['eanIndex']],
                                    'Category' => $row[$headerIndexes['categoryIndex']],
                                    'FilePublisher' => $row[$headerIndexes['publisherIndex']],
                                    'InStock' => $row[$headerIndexes['inStockIndex']],
                                    'Filename' => $file->getClientOriginalName(),
                                ];
                                break;
                            }
                        }
                    }
                }
            }

            fclose($handle);
        }

        return $parsedData;
    }

    private function detectSeparator($handle): string
    {
        $firstLine = fgets($handle);
        rewind($handle);
        return (strpos($firstLine, ';') !== false) ? ';' : ',';
    }

    private function getHeaderIndexes(array $header): array
    {
        $clearHeader = array_map(fn($h) => preg_replace('/^\xEF\xBB\xBF/', '', trim($h)), $header);

        $headerMappings = [
            'eanIndex' => self::$EAN_HEADER_NAMES,
            'publisherIndex' => self::$PUBLISHER_HEADER_NAMES,
            'categoryIndex' => self::$CATEGORY_HEADER_NAMES,
            'inStockIndex' => self::$INSTOCK_HEADER_NAMES,
            'idProductIndex' => self::$PRODUCT_ID_HEADER_NAMES,
        ];

        $indexes = [];

        foreach ($headerMappings as $indexName => $names) {
            $indexes[$indexName] = null;
            foreach ($names as $name) {
                $index = array_search($name, $clearHeader);
                if ($index !== false) {
                    $indexes[$indexName] = $index;
                    break;
                }
            }
        }

        return $indexes;
    }

    private function hasRequiredIndexes(array $indexes): bool
    {
        return $indexes['eanIndex'] !== null
            && $indexes['publisherIndex'] !== null
            && $indexes['inStockIndex'] !== null;
    }

    private function isValidRow(array $row, array $indexes): bool
    {
        return isset($row[$indexes['eanIndex']], $row[$indexes['publisherIndex']], $row[$indexes['inStockIndex']]);
    }

    private function publisherIsInRow(Publisher $publisher, $rowPublisher, $rowCategory): bool
    {
        return strcasecmp($publisher->getName(), $rowPublisher) === 0
            || strcasecmp($publisher->getName(), $rowCategory) === 0;
    }
}
