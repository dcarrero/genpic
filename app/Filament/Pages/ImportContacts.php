<?php

namespace App\Filament\Pages;

use App\Models\MailCampaign;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportContacts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Importar Contactos';
    protected static ?int $navigationSort = 81;
    protected static string $view = 'filament.pages.import-contacts';

    public string $source = 'faceco';
    public string $batchName = '';
    public array $csvFile = [];

    // Resultados de la importación
    public bool $imported = false;
    public int $totalRows = 0;
    public int $newUsers = 0;
    public int $existingUsers = 0;

    // Limpieza de bounces
    public string $bouncedEmails = '';
    public bool $cleaned = false;
    public array $cleanLog = [];
    public int $optedOut = 0;
    public array $langDistribution = [];

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Importar CSV de Mailchimp')->schema([
                Forms\Components\TextInput::make('source')
                    ->label('Origen / Grupo')
                    ->required()
                    ->default('faceco')
                    ->helperText('Ej: faceco, newsletter. Se guarda en import_source del usuario.'),

                Forms\Components\TextInput::make('batchName')
                    ->label('Nombre del lote')
                    ->placeholder('faceco-2026-03-25')
                    ->helperText('Opcional. Para identificar esta importación concreta.'),

                Forms\Components\FileUpload::make('csvFile')
                    ->label('Archivo CSV')
                    ->required()
                    ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                    ->maxSize(10240) // 10MB
                    ->helperText('CSV de Mailchimp con columnas: Email Address, First Name, Last Name, CC, Timezone'),
            ])->columns(2),
        ]);
    }

    public function import(): void
    {
        $this->validate([
            'source'  => 'required|string|max:50',
            'csvFile' => 'required',
        ]);

        // Obtener el archivo subido via Filament FileUpload
        $fileRef = is_array($this->csvFile) ? (reset($this->csvFile) ?: null) : $this->csvFile;
        if (!$fileRef) {
            Notification::make()->title('Archivo no seleccionado')->danger()->send();
            return;
        }

        // Resolver path real del archivo — puede ser TemporaryUploadedFile, string path o array
        $filePath = null;

        if ($fileRef instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $filePath = $fileRef->getRealPath();
        } elseif (is_object($fileRef) && method_exists($fileRef, 'getRealPath')) {
            $filePath = $fileRef->getRealPath();
        } elseif (is_string($fileRef)) {
            // Path relativo al disco local
            $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($fileRef);
            if (!file_exists($filePath)) {
                $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path('livewire-tmp/' . basename($fileRef));
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            Log::warning('CSV file not found', ['fileRef' => is_object($fileRef) ? get_class($fileRef) : $fileRef]);
            Notification::make()->title('No se pudo leer el archivo CSV')->danger()->send();
            return;
        }

        // Verificar que es un archivo de texto, no una imagen
        $mime = mime_content_type($filePath);
        if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'])) {
            Log::warning('CSV file has wrong mime type', ['mime' => $mime, 'path' => $filePath]);
            Notification::make()->title('El archivo no es un CSV válido (tipo: ' . $mime . ')')->danger()->send();
            return;
        }

        Log::info('CSV file found', ['path' => $filePath, 'size' => filesize($filePath), 'mime' => $mime]);

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            Notification::make()->title('Error al abrir el archivo')->danger()->send();
            return;
        }

        // Eliminar BOM UTF-8 si existe
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Detectar delimitador: leer primera línea como texto
        $firstLine = fgets($handle);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        } else {
            // Rebobinar a después del BOM
            fseek($handle, 3);
        }

        if (!$firstLine) {
            fclose($handle);
            Notification::make()->title('CSV vacío o mal formado')->danger()->send();
            return;
        }

        // Auto-detectar separador: tab, coma o punto y coma
        // Contar fuera de campos entrecomillados
        $delimiter = ',';
        $tabCount = substr_count($firstLine, "\t");
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');

        if ($tabCount > $commaCount && $tabCount > $semicolonCount) {
            $delimiter = "\t";
        } elseif ($semicolonCount > $commaCount) {
            $delimiter = ';';
        }

        // Leer cabecera
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            Notification::make()->title('CSV vacío o mal formado')->danger()->send();
            return;
        }

        // Limpiar cabeceras: quitar BOM residual, comillas, espacios
        $header = array_map(function ($h) {
            return trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', $h));
        }, $header);
        $headerLower = array_map('strtolower', $header);

        // Debug: log de cabeceras detectadas
        Log::info('CSV import headers', ['delimiter' => $delimiter === "\t" ? 'TAB' : $delimiter, 'headers' => $header]);

        $emailCol = $this->findColumn($headerLower, ['email address', 'email', 'correo']);
        $firstNameCol = $this->findColumn($headerLower, ['first name', 'nombre', 'first_name']);
        $lastNameCol = $this->findColumn($headerLower, ['last name', 'apellido', 'last_name']);
        $ccCol = $this->findColumn($headerLower, ['cc', 'country', 'country code', 'pais']);
        $tzCol = $this->findColumn($headerLower, ['timezone', 'tz', 'zona horaria']);

        if ($emailCol === null) {
            fclose($handle);
            Log::warning('CSV email column not found', ['headers' => $header, 'headersLower' => $headerLower]);
            Notification::make()
                ->title('No se encontró columna de email en el CSV')
                ->body('Columnas detectadas: ' . implode(', ', $header))
                ->danger()
                ->send();
            return;
        }

        $batch = $this->batchName ?: ($this->source . '-' . now()->format('Y-m-d'));
        $new = 0;
        $existing = 0;
        $optedOut = 0;
        $rows = 0;
        $langs = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $email = trim($row[$emailCol] ?? '');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $email = strtolower($email);
            $firstName = $firstNameCol !== null ? trim($row[$firstNameCol] ?? '') : null;
            $lastName = $lastNameCol !== null ? trim($row[$lastNameCol] ?? '') : null;
            $cc = $ccCol !== null ? strtoupper(trim($row[$ccCol] ?? '')) : null;
            $tz = $tzCol !== null ? trim($row[$tzCol] ?? '') : null;
            $lang = MailCampaign::detectLanguage($cc);

            $rows++;
            $langs[$lang] = ($langs[$lang] ?? 0) + 1;

            $user = User::where('email', $email)->first();

            if ($user) {
                // Actualizar datos si no tenía
                $updates = [];
                if (!$user->import_source) $updates['import_source'] = $this->source;
                if (!$user->import_batch) $updates['import_batch'] = $batch;
                if (!$user->first_name && $firstName) $updates['first_name'] = $firstName;
                if (!$user->last_name && $lastName) $updates['last_name'] = $lastName;
                if (!$user->country_code && $cc) $updates['country_code'] = $cc;
                if (!$user->timezone && $tz) $updates['timezone'] = $tz;
                if ($updates) $user->update($updates);

                if ($user->hasOptedOutOfMarketing()) {
                    $optedOut++;
                } else {
                    $existing++;
                }
            } else {
                User::create([
                    'email'          => $email,
                    'first_name'     => $firstName ?: null,
                    'last_name'      => $lastName ?: null,
                    'country_code'   => $cc ?: null,
                    'timezone'       => $tz ?: null,
                    'locale'         => $lang,
                    'import_source'  => $this->source,
                    'import_batch'   => $batch,
                ]);
                $new++;
            }
        }

        fclose($handle);

        // Limpiar archivo temporal
        @unlink($filePath);

        $this->imported = true;
        $this->totalRows = $rows;
        $this->newUsers = $new;
        $this->existingUsers = $existing;
        $this->optedOut = $optedOut;
        $this->langDistribution = $langs;

        Log::info('CSV import completed', [
            'source' => $this->source,
            'batch'  => $batch,
            'total'  => $rows,
            'new'    => $new,
            'existing' => $existing,
            'opted_out' => $optedOut,
            'languages' => $langs,
        ]);

        Notification::make()
            ->title("Importación completada: {$rows} filas procesadas")
            ->body("{$new} nuevos, {$existing} existentes, {$optedOut} desuscritos")
            ->success()
            ->send();
    }

    private function findColumn(array $headerLower, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $idx = array_search($candidate, $headerLower, true);
            if ($idx !== false) return $idx;
        }
        return null;
    }

    /**
     * Limpiar emails devueltos (bounces).
     */
    public function cleanBounces(): void
    {
        if (empty(trim($this->bouncedEmails))) {
            Notification::make()->title('Introduce al menos un email')->warning()->send();
            return;
        }

        $emails = array_filter(
            array_map('trim', preg_split('/[\n\r,;]+/', strtolower($this->bouncedEmails))),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        );

        if (empty($emails)) {
            Notification::make()->title('No se encontraron emails válidos en el listado')->warning()->send();
            return;
        }

        $log = [];
        $deleted = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $log[] = ['email' => $email, 'action' => 'No encontrado', 'status' => 'gray'];
                $notFound++;
                continue;
            }

            // Marcar mail_sends como bounced
            \App\Models\MailSend::where('email', $email)->update([
                'status' => 'bounced',
                'error_message' => 'Bounce cleanup',
            ]);

            // Marcar opt-out marketing (no enviar más campañas)
            $user->update(['marketing_opt_out_at' => now()]);

            // Desvincular orders pendientes (nunca borrar orders — regla de negocio)
            $user->orders()
                ->whereNotIn('status', ['paid', 'processing', 'ready'])
                ->update(['user_id' => null]);

            // Desvincular sesiones
            $user->generationSessions()->update(['user_id' => null]);

            // Anonimizar usuario (conservar para FK de orders pagadas)
            $user->update([
                'email'      => "bounced_{$user->id}@anon.invalid",
                'first_name' => null,
                'last_name'  => null,
                'ip_address' => null,
                'user_agent' => null,
            ]);

            $log[] = ['email' => $email, 'action' => 'Anonimizado + desuscrito', 'status' => 'success'];
            $deleted++;
        }

        $this->cleaned = true;
        $this->cleanLog = $log;

        Log::info('Bounce cleanup completed', [
            'total'    => count($emails),
            'deleted'  => $deleted,
            'skipped'  => $skipped,
            'notFound' => $notFound,
        ]);

        Notification::make()
            ->title("Limpieza completada: {$deleted} eliminados, {$skipped} con pedidos, {$notFound} no encontrados")
            ->success()
            ->send();
    }
}
