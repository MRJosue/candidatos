<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TalentImportController extends Controller
{
    private const SESSION_KEY = 'talent_import.rows';

    private const STATUSES = ['active', 'inactive', 'hired', 'rejected', 'paused'];

    private const COLUMNS = [
        'first_name' => ['label' => 'Nombre', 'required' => true, 'sample' => 'Andrea'],
        'last_name' => ['label' => 'Apellido', 'required' => true, 'sample' => 'Lopez'],
        'source' => ['label' => 'Fuente', 'required' => false, 'sample' => 'LinkedIn'],
        'status' => ['label' => 'Estado', 'required' => false, 'sample' => 'active'],
        'notes' => ['label' => 'Notas internas', 'required' => false, 'sample' => 'Buen fit para vacantes remotas.'],
        'last_contacted_at' => ['label' => 'Ultimo contacto', 'required' => false, 'sample' => '2026-05-07'],
    ];

    public function create()
    {
        return view('talents.import', [
            'columns' => self::COLUMNS,
            'preview' => session(self::SESSION_KEY),
        ]);
    }

    public function layout(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Talentos');

        $labels = array_column(self::COLUMNS, 'label');
        $samples = array_column(self::COLUMNS, 'sample');

        $sheet->fromArray($labels, null, 'A1');
        $sheet->fromArray($samples, null, 'A2');

        foreach (array_values(self::COLUMNS) as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($letter)->setWidth($this->columnWidth($column['label']));

            if ($column['required']) {
                $sheet->getStyle("{$letter}1:{$letter}100")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF3C7');
            }
        }

        $highestColumn = Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '111827']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A2:{$highestColumn}100")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->freezePane('A2');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instrucciones');
        $instructions->fromArray([
            ['Campo', 'Obligatorio', 'Notas'],
            ['Nombre', 'Si', 'Texto, maximo 120 caracteres.'],
            ['Apellido', 'Si', 'Texto, maximo 120 caracteres.'],
            ['Estado', 'No', 'Si lo dejas vacio se guardara como active. Valores permitidos: active, inactive, hired, rejected, paused.'],
            ['Datos profesionales', 'No aplica', 'Capturalos en el CV para evitar duplicar informacion del talento.'],
            ['Ultimo contacto', 'No', 'Usa formato YYYY-MM-DD o una fecha de Excel.'],
        ]);
        $instructions->getColumnDimension('A')->setWidth(30);
        $instructions->getColumnDimension('B')->setWidth(16);
        $instructions->getColumnDimension('C')->setWidth(80);
        $instructions->getStyle('A1:C1')->getFont()->setBold(true);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'layout-talentos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'talents_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $rows = $this->readRows($request->file('talents_file')->getRealPath());
        } catch (\Throwable) {
            return redirect()->route('talents.import')
                ->withErrors(['talents_file' => 'No pudimos leer el archivo. Descarga el layout e intenta cargarlo de nuevo.']);
        }

        $preview = $this->buildPreview($rows);

        session([self::SESSION_KEY => $preview]);

        return redirect()->route('talents.import')->with('status', 'Archivo procesado. Revisa la previsualizacion antes de cargar.');
    }

    public function store(Request $request)
    {
        $preview = session(self::SESSION_KEY);

        if (! $preview || $preview['has_errors']) {
            return redirect()->route('talents.import')
                ->withErrors(['talents_file' => 'Carga un archivo valido y revisa los errores antes de guardar.']);
        }

        DB::transaction(function () use ($preview, $request): void {
            foreach ($preview['rows'] as $row) {
                $request->user()->talents()->create($row['data']);
            }
        });

        $count = $preview['valid_count'];
        session()->forget(self::SESSION_KEY);

        return redirect()->route('talents.index')->with('status', "{$count} postulantes cargados.");
    }

    private function readRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $rows = [];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = [];

            foreach (array_keys(self::COLUMNS) as $index => $field) {
                $columnIndex = $index + 1;
                $value = $sheet->getCell([$columnIndex, $rowNumber])->getValue();
                $row[$field] = $this->normalizeCellValue($value);
            }

            if (collect($row)->filter(fn ($value) => filled($value))->isNotEmpty()) {
                $rows[] = ['number' => $rowNumber, 'data' => $row];
            }
        }

        return $rows;
    }

    private function buildPreview(array $rows): array
    {
        $previewRows = collect($rows)->map(function (array $row): array {
            $data = $this->prepareRowData($row['data']);
            $validator = Validator::make($data, $this->rules(), $this->messages(), $this->attributes());

            return [
                'number' => $row['number'],
                'data' => $validator->passes() ? $this->castRowData($data) : $data,
                'errors' => $validator->errors()->all(),
            ];
        })->values();

        return [
            'rows' => $previewRows->all(),
            'total_count' => $previewRows->count(),
            'valid_count' => $previewRows->filter(fn (array $row) => count($row['errors']) === 0)->count(),
            'error_count' => $previewRows->filter(fn (array $row) => count($row['errors']) > 0)->count(),
            'has_errors' => $previewRows->contains(fn (array $row) => count($row['errors']) > 0) || $previewRows->isEmpty(),
        ];
    }

    private function prepareRowData(array $data): array
    {
        $data['status'] = strtolower((string) ($data['status'] ?? '')) ?: 'active';
        $data['last_contacted_at'] = $this->parseDate($data['last_contacted_at'] ?? null);

        return $data;
    }

    private function castRowData(array $data): array
    {
        return $data;
    }

    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
            'last_contacted_at' => ['nullable', 'date'],
        ];
    }

    private function messages(): array
    {
        return [
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'email' => 'El campo :attribute debe ser un email valido.',
            'in' => 'El campo :attribute contiene un valor no permitido.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'max' => 'El campo :attribute supera la longitud permitida.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
            'required' => 'El campo :attribute es obligatorio.',
            'size' => 'El campo :attribute debe tener :size caracteres.',
        ];
    }

    private function attributes(): array
    {
        return collect(self::COLUMNS)
            ->mapWithKeys(fn (array $column, string $field) => [$field => $column['label']])
            ->all();
    }

    private function normalizeCellValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function splitList(?string $value): ?array
    {
        if (! filled($value)) {
            return null;
        }

        return str($value)
            ->replace(["\r\n", "\r"], "\n")
            ->replace("\n", ',')
            ->explode(',')
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function parseDate(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function columnWidth(string $label): int
    {
        return max(16, min(36, strlen($label) + 6));
    }
}
