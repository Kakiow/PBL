<?php
/**
 * Writer XLSX minimal untuk laporan SIMKAS.
 * Tidak memakai dependensi eksternal: workbook disusun dari XML OOXML lalu
 * dikemas dengan ZipArchive.
 */

function xlsx_escape($teks): string
{
    return htmlspecialchars((string) $teks, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function xlsx_nama_berkas($nama_grup, $mulai, $selesai): string
{
    $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $nama_grup), '-');
    if ($slug === '') {
        $slug = 'Grup';
    }

    return 'Laporan-Kas-' . $slug . '-' . $mulai . '-sd-' . $selesai . '.xlsx';
}

function xlsx_sel(int $kolom, int $baris): string
{
    return chr(65 + $kolom) . $baris;
}

function xlsx_teks(string $sel, $teks, int $gaya): string
{
    return '<c r="' . $sel . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">'
        . xlsx_escape($teks) . '</t></is></c>';
}

function xlsx_angka(string $sel, $nilai, int $gaya): string
{
    $teks = rtrim(rtrim(number_format((float) $nilai, 2, '.', ''), '0'), '.');
    if ($teks === '' || $teks === '-0') {
        $teks = '0';
    }

    return '<c r="' . $sel . '" s="' . $gaya . '"><v>' . $teks . '</v></c>';
}

function xlsx_kosong(string $sel, int $gaya): string
{
    return '<c r="' . $sel . '" s="' . $gaya . '"/>';
}

function xlsx_baris(int $nomor, string $sel, ?int $tinggi = null): string
{
    $attr = $tinggi !== null ? ' ht="' . $tinggi . '" customHeight="1"' : '';
    return '<row r="' . $nomor . '"' . $attr . '>' . $sel . '</row>';
}

function xlsx_sheet_xml(array $meta, array $ringkasan, array $baris): string
{
    $merges = ['A1:F1', 'A2:F2', 'A3:F3', 'A4:F4', 'A6:F6'];
    $rows = [];
    $rows[] = xlsx_baris(1, xlsx_teks('A1', strtoupper((string) ($meta['judul'] ?? 'Laporan Kas')), 1), 28);
    $rows[] = xlsx_baris(2, xlsx_teks('A2', $meta['grup'] ?? '', 2), 20);
    $rows[] = xlsx_baris(3, xlsx_teks('A3', 'Periode ' . ($meta['periode'] ?? ''), 3));
    $rows[] = xlsx_baris(4, xlsx_teks('A4', 'Dicetak pada ' . ($meta['dicetak'] ?? '') . ' oleh ' . ($meta['oleh'] ?? ''), 3));
    $rows[] = xlsx_baris(6, xlsx_teks('A6', 'Ringkasan', 4));

    $ringkasanBaris = [
        ['Pemasukan', $ringkasan['pemasukan'] ?? 0, 5, 6],
        ['Pengeluaran', $ringkasan['pengeluaran'] ?? 0, 5, 6],
        ['Saldo', $ringkasan['saldo'] ?? 0, 12, 13],
    ];
    $nomor = 7;
    foreach ($ringkasanBaris as [$label, $nilai, $gayaLabel, $gayaAngka]) {
        $sel = xlsx_teks('A' . $nomor, $label, $gayaLabel);
        foreach (['B', 'C', 'D', 'E'] as $kolomKosong) {
            $sel .= xlsx_kosong($kolomKosong . $nomor, $gayaLabel);
        }
        $sel .= xlsx_angka('F' . $nomor, $nilai, $gayaAngka);
        $rows[] = xlsx_baris($nomor, $sel);
        $nomor++;
    }

    $judulKolom = ['No', 'Tanggal', 'Deskripsi', 'Kategori', 'Jenis', 'Jumlah'];
    $header = '';
    foreach ($judulKolom as $i => $teks) {
        $header .= xlsx_teks(xlsx_sel($i, 11), $teks, 7);
    }
    $rows[] = xlsx_baris(11, $header);

    $nomor = 12;
    if (!$baris) {
        $rows[] = xlsx_baris($nomor, xlsx_teks('A' . $nomor, 'Tidak ada transaksi pada periode ini.', 11));
        $merges[] = 'A' . $nomor . ':F' . $nomor;
        $terakhir = $nomor;
    } else {
        $urut = 1;
        foreach ($baris as $item) {
            $sel = xlsx_angka('A' . $nomor, $urut, 9);
            $sel .= xlsx_teks('B' . $nomor, $item['tanggal'] ?? '', 9);
            $sel .= xlsx_teks('C' . $nomor, $item['deskripsi'] ?? '', 8);
            $sel .= xlsx_teks('D' . $nomor, $item['kategori'] ?? '', 8);
            $sel .= xlsx_teks('E' . $nomor, $item['jenis'] ?? '', 9);
            $sel .= xlsx_angka('F' . $nomor, $item['jumlah'] ?? 0, 10);
            $rows[] = xlsx_baris($nomor, $sel);
            $nomor++;
            $urut++;
        }
        $terakhir = $nomor - 1;
    }

    $mergeXml = '<mergeCells count="' . count($merges) . '">';
    foreach ($merges as $merge) {
        $mergeXml .= '<mergeCell ref="' . $merge . '"/>';
    }
    $mergeXml .= '</mergeCells>';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
        . '<dimension ref="A1:F' . $terakhir . '"/>'
        . '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
        . '<pane ySplit="11" topLeftCell="A12" activePane="bottomLeft" state="frozen"/>'
        . '</sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/>'
        . '<cols>'
        . '<col min="1" max="1" width="6" customWidth="1"/>'
        . '<col min="2" max="2" width="16" customWidth="1"/>'
        . '<col min="3" max="3" width="44" customWidth="1"/>'
        . '<col min="4" max="4" width="24" customWidth="1"/>'
        . '<col min="5" max="5" width="15" customWidth="1"/>'
        . '<col min="6" max="6" width="17" customWidth="1"/>'
        . '</cols>'
        . '<sheetData>' . implode('', $rows) . '</sheetData>'
        . $mergeXml
        . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
        . '<pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/>'
        . '</worksheet>';
}

function xlsx_styles_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode=\'"Rp"#,##0;[Red]-"Rp"#,##0\'/></numFmts>'
        . '<fonts count="6">'
        . '<font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="16"/><color rgb="FF263D66"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="12"/><color rgb="FF1F2430"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FF1F2430"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '<font><i/><sz val="9"/><color rgb="FF636A76"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="4">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF263D66"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF1F6"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left style="thin"><color rgb="FFB7BEC9"/></left><right style="thin"><color rgb="FFB7BEC9"/></right>'
        . '<top style="thin"><color rgb="FFB7BEC9"/></top><bottom style="thin"><color rgb="FFB7BEC9"/></bottom><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="14">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
        . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
        . '<xf numFmtId="164" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function xlsx_kemas(array $entri): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'simkas-xlsx-');
    if ($tmp === false) {
        throw new RuntimeException('Tidak dapat membuat berkas sementara XLSX.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);
        throw new RuntimeException('Tidak dapat membuat arsip XLSX.');
    }

    foreach ($entri as $nama => $isi) {
        $zip->addFromString($nama, $isi);
    }
    $zip->close();

    $biner = file_get_contents($tmp);
    @unlink($tmp);

    if ($biner === false) {
        throw new RuntimeException('Tidak dapat membaca arsip XLSX.');
    }

    return $biner;
}

function xlsx_laporan(array $meta, array $ringkasan, array $baris): string
{
    $sheet = xlsx_sheet_xml($meta, $ringkasan, $baris);
    $styles = xlsx_styles_xml();
    $dibuat = gmdate('Y-m-d\TH:i:s\Z');

    $konten = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '</Types>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>';

    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Laporan Kas" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
        . ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"'
        . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>' . xlsx_escape($meta['judul'] ?? 'Laporan Kas') . '</dc:title>'
        . '<dc:creator>SIMKAS</dc:creator><cp:lastModifiedBy>SIMKAS</cp:lastModifiedBy>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $dibuat . '</dcterms:created>'
        . '</cp:coreProperties>';

    $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"'
        . ' xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>SIMKAS</Application>'
        . '</Properties>';

    return xlsx_kemas([
        '[Content_Types].xml' => $konten,
        '_rels/.rels' => $rels,
        'docProps/core.xml' => $core,
        'docProps/app.xml' => $app,
        'xl/workbook.xml' => $workbook,
        'xl/_rels/workbook.xml.rels' => $workbookRels,
        'xl/styles.xml' => $styles,
        'xl/worksheets/sheet1.xml' => $sheet,
    ]);
}
