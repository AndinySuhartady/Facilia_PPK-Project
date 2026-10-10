<?php

/** CSV UTF-8 untuk Excel Indonesia: BOM, pemisah titik koma, dan petunjuk sep. */
function exportCsv(string $filename, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBFsep=;\r\n");
    if ($rows) {
        fputcsv($output, array_keys($rows[0]), ';', '"', '', "\r\n");
        foreach ($rows as $row) {
            // Teks pengguna tidak boleh menjadi formula ketika dibuka di Excel.
            foreach ($row as &$value) {
                if (is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value)) $value = "'" . $value;
            }
            unset($value);
            fputcsv($output, $row, ';', '"', '', "\r\n");
        }
    }
    fclose($output);
    exit;
}

/** ZIP tanpa ekstensi tambahan. XLSX berisi XML Office Open XML yang sebenarnya. */
function buildExportZip(array $files): string
{
    $body = ''; $directory = ''; $count = 0;
    foreach ($files as $name => $contents) {
        $offset = strlen($body); $size = strlen($contents); $crc = crc32($contents);
        $body .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 33, $crc, $size, $size, strlen($name), 0) . $name . $contents;
        $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 33, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
        $count++;
    }
    return $body . $directory . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($directory), strlen($body), 0);
}

function buildExportXlsx(array $rows): string
{
    $xml = static function ($value): string {
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value) ?? '';
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };
    $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
    $table = $rows ? array_merge([array_keys($rows[0])], array_map('array_values', $rows)) : [['Tidak ada data pada periode ini']];
    foreach ($table as $index => $row) {
        $sheet .= '<row r="' . ($index + 1) . '">';
        foreach ($row as $value) {
            if ($index > 0 && (is_int($value) || is_float($value))) {
                $sheet .= '<c t="n"><v>' . $value . '</v></c>';
            } else {
                $sheet .= '<c t="inlineStr"><is><t xml:space="preserve">' . $xml($value) . '</t></is></c>';
            }
        }
        $sheet .= '</row>';
    }
    $sheet .= '</sheetData></worksheet>';
    return buildExportZip([
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Rekap" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml' => $sheet,
    ]);
}

function exportXlsx(string $filename, array $rows): void
{
    $contents = buildExportXlsx($rows);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($contents));
    echo $contents;
    exit;
}
