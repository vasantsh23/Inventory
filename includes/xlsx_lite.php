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
 * Scope: flat data tables (strings/numbers), one sheet, optional
 * clickable links. No formulas, multiple sheets, or rich formatting —
 * which is all this app's import/export needs.
 */

declare(strict_types=1);

final class XlsxWriter
{
    /**
     * Stream an XLSX file to the browser and exit.
     * @param string $filename e.g. "users-export.xlsx"
     * @param string[] $headers column headers
     * @param array<int, array<int, string>> $rows row data, same column count as $headers
     * @param array{text_cols?: int[], links?: array<int, array<int, string>>} $opts
     *        text_cols: column indexes always written as text (e.g. certificate
     *        numbers, so Excel never shows 2.2E+11); links: [rowIndex][colIndex]
     *        => absolute URL, written as a clickable hyperlink (blue, underlined)
     */
    public static function download(string $filename, array $headers, array $rows, array $opts = []): void
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
        [$sheet, $sheetRels] = self::sheetXml($headers, $rows, $opts);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        if ($sheetRels !== null) {
            $zip->addEmptyDir('xl/worksheets/_rels');
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheetRels);
        }

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

    /** @return array{0: string, 1: ?string} [sheet XML, sheet relationships XML or null] */
    private static function sheetXml(array $headers, array $rows, array $opts = []): array
    {
        $textCols = array_flip(array_map('intval', $opts['text_cols'] ?? []));
        $links = $opts['links'] ?? [];
        $hyperlinks = [];
        $rels = [];

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
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
                $url = $links[$r][$i] ?? null;
                $style = '';
                if ($url !== null && $url !== '' && $val !== '' && count($hyperlinks) < 65000) {
                    $rid = 'rId' . (count($rels) + 1);
                    $rels[] = '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" '
                        . 'Target="' . self::xmlEscape($url) . '" TargetMode="External"/>';
                    $hyperlinks[] = '<hyperlink ref="' . $ref . '" r:id="' . $rid . '"/>';
                    $style = ' s="2"';
                }
                // Numbers stay numbers, except: forced-text columns, leading
                // zeros, and whole numbers of 12+ digits (certificate numbers
                // and the like), which Excel would otherwise show as 2.2E+11
                // and round past 15 digits.
                $isNumber = $val !== '' && is_numeric($val) && !isset($textCols[$i])
                    && !preg_match('/^0\d/', $val) && !preg_match('/^-?\d{12,}$/', $val);
                if ($isNumber) {
                    $xml .= '<c r="' . $ref . '"' . $style . '><v>' . self::xmlEscape($val) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
                        . self::xmlEscape($val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        if ($hyperlinks !== []) {
            $xml .= '<hyperlinks>' . implode('', $hyperlinks) . '</hyperlinks>';
        }
        $xml .= '</worksheet>';

        $relsXml = $rels === [] ? null
            : '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
              . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
              . implode('', $rels) . '</Relationships>';
        return [$xml, $relsXml];
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
        // Cell formats: 0 = default, 1 = bold (header row), 2 = hyperlink (blue, underlined).
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><u/><sz val="11"/><color rgb="FF0563C1"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" xfId="0" applyFont="1"/>'
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

        // The first non-empty row is the header. Data rows keep keys
        // numbered so that key + 2 = the row number shown in Excel (as
        // used in import messages), even when blank rows are skipped.
        $headerKey = array_key_first($rows);
        $headerRow = $rows[$headerKey];
        unset($rows[$headerKey]);
        $data = [];
        foreach ($rows as $excelRow => $cells) {
            $data[$excelRow - 2] = $cells;
        }
        return [$headerRow, $data];
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

        $position = 0;
        foreach ($sheet->sheetData->row as $row) {
            $position++;
            // Excel's own row number (r="7"); blank rows are often left out
            // of the file entirely, so counting rows isn't enough.
            $excelRow = isset($row['r']) && ctype_digit((string)$row['r']) ? (int)$row['r'] : $position;
            $position = $excelRow;
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
            $rows[$excelRow] = $aligned;
        }

        return $rows;
    }
}
