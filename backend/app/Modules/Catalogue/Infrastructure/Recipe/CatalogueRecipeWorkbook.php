<?php

namespace App\Modules\Catalogue\Infrastructure\Recipe;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class CatalogueRecipeWorkbook
{
    public function __construct(private readonly string $path) {}

    /**
     * @return array<string, list<array<string, string>>>
     */
    public function sheets(): array
    {
        $zip = new ZipArchive();
        if ($zip->open($this->path) !== true) {
            throw new RuntimeException('Le classeur de recette est illisible.');
        }

        try {
            $shared = $this->sharedStrings($zip);
            $workbook = $this->xml($zip->getFromName('xl/workbook.xml'));
            $sheets = [];
            foreach ($workbook->sheets->sheet as $sheet) {
                $name = (string) $sheet['name'];
                $file = 'xl/worksheets/sheet'.((string) $sheet['sheetId']).'.xml';
                $sheets[$name] = $this->table($this->xml($zip->getFromName($file)), $shared);
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $source = $zip->getFromName('xl/sharedStrings.xml');
        if ($source === false) {
            return [];
        }

        $strings = [];
        foreach ($this->xml($source)->si as $item) {
            $value = isset($item->t) ? (string) $item->t : '';
            foreach ($item->r as $run) {
                $value .= (string) $run->t;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param  list<string>  $shared
     * @return list<array<string, string>>
     */
    private function table(SimpleXMLElement $sheet, array $shared): array
    {
        $header = null;
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = $this->cells($row, $shared);
            if ($this->blank($cells)) {
                continue;
            }
            if ($header === null) {
                if (! $this->isHeader($cells)) {
                    continue;
                }
                $header = $cells;
                continue;
            }

            $record = [];
            foreach ($header as $index => $name) {
                if ($name === '') {
                    continue;
                }
                $record[$name] = trim($cells[$index] ?? '');
            }
            if (($record[$header[0]] ?? '') !== '') {
                $rows[] = $record;
            }
        }

        return $rows;
    }

    /**
     * @param  list<string>  $shared
     * @return array<int, string>
     */
    private function cells(SimpleXMLElement $row, array $shared): array
    {
        $cells = [];
        foreach ($row->c as $cell) {
            $column = 0;
            foreach (str_split((string) preg_replace('/[0-9]/', '', (string) $cell['r'])) as $letter) {
                $column = ($column * 26) + (ord($letter) - 64);
            }
            $type = (string) $cell['t'];
            $value = isset($cell->v) ? (string) $cell->v : '';
            if ($type === 's') {
                $value = $shared[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = isset($cell->is->t) ? (string) $cell->is->t : '';
            }
            $cells[$column - 1] = $value;
        }

        return $cells;
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function isHeader(array $cells): bool
    {
        return array_intersect($cells, [
            'reference', 'oeuvre', 'code', 'selection', 'type', 'cas',
        ]) !== [];
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function blank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function xml(string|false $source): SimpleXMLElement
    {
        if ($source === false) {
            throw new RuntimeException('Une feuille du classeur est absente.');
        }

        $source = preg_replace('/xmlns(:[A-Za-z0-9]+)?="[^"]*"/', '', $source) ?? $source;
        $source = preg_replace('/\s[A-Za-z0-9]+:([A-Za-z0-9]+)="/', ' $1="', $source) ?? $source;
        $source = preg_replace('/<(\/?)[A-Za-z0-9]+:/', '<$1', $source) ?? $source;
        $xml = simplexml_load_string($source);
        if ($xml === false) {
            throw new RuntimeException('Une feuille du classeur est illisible.');
        }

        return $xml;
    }
}
