<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{

    private const COLUMNS = [
        'id' => 'Nr',
        'full_name' => 'Nume Prenume',
        'email' => 'Email',
        'phone' => 'Telefon',
        'university' => 'Universitate',
        'faculty' => 'Facultate',
        'specialty' => 'Specialitate',
        'study_year' => 'Anul de studiu',
        'internship_status' => 'Status stagiu',
        'start_date' => 'Data de început',
        'end_date' => 'Data de sfârșit',
        'direction_name' => 'Direcție',
        'section_name' => 'Secție',
        'mentor' => 'Mentor',
        'internship_type' => 'Tip stagiu',
        'recommendation_source' => 'Sursă recomandare',
        'hiring_potential' => 'Potențial angajare',
        'record_created_at' => 'Data creării înregistrării',
    ];

    public function streamInternsXlsx(array $interns): never
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Interns');

        $headers = array_values(self::COLUMNS);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($interns as $intern) {
            $row = [];
            foreach (array_keys(self::COLUMNS) as $key) {
                $value = $intern[$key] ?? '';
                if ($key === 'is_active') {
                    $value = in_array($value, [true, 't', '1', 1], true) ? 'Da' : 'Nu';
                }

                $row[] = $value;
            }
            $sheet->fromArray($row, null, 'A' . $rowIndex);
            $rowIndex++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'interns_export_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
