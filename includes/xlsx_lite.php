<?php
/**
 * xlsx_lite.php
 * Minimal, dependency-free XLSX reader and writer.
 *
 * Why not PhpSpreadsheet? This app targets shared hosting (e.g.
 * BigRock/cPanel) that typically has no Composer/SSH access, so it
 * can't pull in a third-party library. XLSX is just a zip of XML
 * files — PHP's built-in ZipArchive and SimpleXML extensions (both
 * standard on virtually every PHP install) are enough to read and
 * write simple, single-sheet tabular spreadsheets.
 *
 * Scope: flat data tables (strings/numbers), one sheet. No styling,
 * formulas, multiple sheets, or rich formatting — which is all this
 * app's import/export needs.
 */

declare(strict_types=1);

final class XlsxWriter
{
    /**
     * Stream an XLSX file to the browser and exit.
     * @param string $filename e.g. "users-export.xlsx"
     * @param string[] $headers column headers
     * @param array<int, array<int, string>> $rows row data, same column count as $headers
     */
    public static function download(string $filename, array $headers, array $rows): void
    {
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            exit('The PHP zip extension is required for Excel export and is not enabled on this server.');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $opened = $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            http_response_code(500);
            exit('Could not create the Excel file (zip error code ' . $opened . ').');
        }

        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($headers, $rows));

        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($filename) . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

    private static function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'export.xlsx';
        return str_ends_with(strtolower($name), '.xlsx') ? $name : $name . '.xlsx';
    }

    private static function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - $mod, 26);
        }
        return $letter;
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function sheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetData>';

        $xml .= '<row r="1">';
        foreach ($headers as $i => $h) {
            $ref = self::colLetter($i) . '1';
            $xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve">'
                . self::xmlEscape((string)$h) . '</t></is></c>';
        }
        $xml .= '</row>';

        foreach ($rows as $r => $row) {
            $rowNum = $r + 2;
            $xml .= '<row r="' . $rowNum . '">';
            foreach ($row as $i => $val) {
                $ref = self::colLetter($i) . $rowNum;
                $val = (string)$val;
                if ($val !== '' && is_numeric($val) && !preg_match('/^0\d/', $val)) {
                    $xml .= '<c r="' . $ref . '"><v>' . self::xmlEscape($val) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                        . self::xmlEscape($val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        // Two cell formats: 0 = default, 1 = bold (used for the header row).
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><name val="Calibri"/><b/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}

final class XlsxReader
{
    /**
     * Read the first sheet of an XLSX file.
     * @return array{0: string[], 1: array<int,array<int,string>>} [headerRow, dataRows]
     */
    public static function read(string $filepath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP zip extension is required to import Excel files.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid .xlsx spreadsheet.');
        }

        $sheetPath = self::resolveFirstSheetPath($zip);
        $sharedStrings = self::readSharedStrings($zip);

        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Could not read the spreadsheet contents.');
        }
        $zip->close();

        $rows = self::parseSheet($sheetXml, $sharedStrings);
        if ($rows === []) {
            return [[], []];
        }

        $headerRow = array_shift($rows);
        return [$headerRow, $rows];
    }

    private static function resolveFirstSheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml'; // best-effort fallback
        }

        $wb = simplexml_load_string($workbookXml);
        $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheetNode = $wb->xpath('//m:sheets/m:sheet[1]')[0] ?? null;
        if ($sheetNode === null) {
            return 'xl/worksheets/sheet1.xml';
        }
        $rid = (string)$sheetNode->attributes('r', true)['id'];

        $rels = simplexml_load_string($relsXml);
        foreach ($rels->Relationship as $rel) {
            if ((string)$rel['Id'] === $rid) {
                $target = ltrim((string)$rel['Target'], '/');
                return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    /** @return string[] index => shared string value */
    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $sst = simplexml_load_string($xml);
        $out = [];
        foreach ($sst->si as $si) {
            $out[] = self::extractText($si);
        }
        return $out;
    }

    /**
     * Concatenate all <t> text within a node (handles both a direct <t>
     * and rich-text runs <r><t>...</t></r>). Uses plain property/child
     * traversal rather than xpath() — SimpleXML's xpath() requires
     * namespace-prefixed queries against a default-namespaced document,
     * while property/child access is namespace-agnostic and works
     * directly against XLSX's default-namespaced XML.
     */
    private static function extractText(SimpleXMLElement $node): string
    {
        $text = '';
        foreach ($node->children() as $child) {
            $name = $child->getName();
            if ($name === 't') {
                $text .= (string)$child;
            } elseif ($name === 'r') {
                foreach ($child->children() as $inner) {
                    if ($inner->getName() === 't') {
                        $text .= (string)$inner;
                    }
                }
            }
        }
        return $text;
    }

    private static function colLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    /** @return array<int, array<int, string>> */
    private static function parseSheet(string $xml, array $sharedStrings): array
    {
        $sheet = simplexml_load_string($xml);
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
                $colIndex = $m ? self::colLettersToIndex($m[1]) : count($rowData);

                $type = (string)$cell['t'];
                if ($type === 's') {
                    $idx = (int)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($cell->is) ? self::extractText($cell->is) : '';
                } elseif ($type === 'b') {
                    $value = ((string)$cell->v === '1') ? 'TRUE' : 'FALSE';
                } else {
                    $value = (string)$cell->v;
                }

                $rowData[$colIndex] = $value;
            }

            if ($rowData === []) {
                continue;
            }
            $maxIdx = max(array_keys($rowData));
            $aligned = [];
            for ($i = 0; $i <= $maxIdx; $i++) {
                $aligned[$i] = $rowData[$i] ?? '';
            }
            $rows[] = $aligned;
        }

        return $rows;
    }
}
