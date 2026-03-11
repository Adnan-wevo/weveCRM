<?php

namespace Modules\AccessControl\Livewire\Permissions;

use App\Imports\AccessControl\PermissionsImport;
use App\Jobs\AccessControl\ExportPermissionsJob;
use App\Jobs\AccessControl\ImportPermissionsJob;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AccessControl\Events\PermissionRecordChanged;

class ImportExportModal extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $show = false;

    /** @var 'idle'|'preview' */
    public string $step = 'idle';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $uploadedFile = null;

    /**
     * @var array<int, array{data: array<string, mixed>, errors: array<string, array<int, string>>, valid: bool}>
     */
    public array $previewRows = [];

    public int $validCount = 0;

    public int $invalidCount = 0;

    /** @var 'queued'|'done'|'failed'|'' */
    public string $importStatus = '';

    public string $importJobId = '';

    public int $importFailureCount = 0;

    /** @var 'queued'|'done'|'failed'|'' */
    public string $exportStatus = '';

    public string $exportJobId = '';

    public string $exportUrl = '';

    public string $exportFilename = '';

    #[On('open-import-export-permissions')]
    public function open(): void
    {
        $this->authorize('access-control.permissions.import-export');
        $this->reset(
            'step',
            'uploadedFile',
            'previewRows',
            'validCount',
            'invalidCount',
            'importStatus',
            'importJobId',
            'importFailureCount',
            'exportStatus',
            'exportJobId',
            'exportUrl',
            'exportFilename',
        );
        $this->resetValidation();
        $this->show = true;
    }

    public function previewImport(): void
    {
        $this->authorize('access-control.permissions.import-export');

        $this->validate([
            'uploadedFile' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:10240'],
        ]);

        $this->previewRows = [];
        $this->validCount = 0;
        $this->invalidCount = 0;

        $rows = Excel::toCollection(new PermissionsImport, $this->uploadedFile)->first();

        if ($rows === null || $rows->isEmpty()) {
            $this->addError('uploadedFile', __('The file is empty or could not be read.'));

            return;
        }

        foreach ($rows as $i => $row) {
            $rowArray = $row->toArray();
            $errors = PermissionsImport::validateRow($rowArray, $i + 2);

            if (empty($errors)) {
                $this->validCount++;
            } else {
                $this->invalidCount++;
            }

            $this->previewRows[] = [
                'data' => $rowArray,
                'errors' => $errors,
                'valid' => empty($errors),
            ];
        }

        $this->step = 'preview';
    }

    public function backToIdle(): void
    {
        $this->step = 'idle';
        $this->previewRows = [];
        $this->validCount = 0;
        $this->invalidCount = 0;
        $this->uploadedFile = null;
    }

    public function confirmImport(): void
    {
        $this->authorize('access-control.permissions.import-export');

        if ($this->validCount === 0) {
            $this->dispatch('notify', type: 'warning', message: __('No valid rows to import.'));

            return;
        }

        $tenantId = tenancy()->initialized ? tenant('id') : null;
        $base = $tenantId ? "{$tenantId}/access-control/permissions/imports" : 'access-control/permissions/imports';
        $path = $this->uploadedFile->store($base, config('filesystems.exports_disk', 'local'));
        $this->importJobId = Str::uuid()->toString();
        $this->importStatus = 'queued';

        ImportPermissionsJob::dispatch($path, $this->importJobId, (int) auth()->id());

        $this->step = 'idle';
        $this->previewRows = [];
        $this->dispatch('notify', type: 'info', message: __('Import queued. Processing in background.'));
    }

    public function export(): void
    {
        $this->authorize('access-control.permissions.import-export');

        $this->exportJobId = Str::uuid()->toString();
        $this->exportStatus = 'queued';

        ExportPermissionsJob::dispatch($this->exportJobId, (int) auth()->id(), tenancy()->initialized ? tenant('id') : null);

        $this->dispatch('notify', type: 'info', message: __('Export queued. Your file will be ready shortly.'));
    }

    public function checkJobStatuses(): void
    {
        if ($this->importStatus === 'queued') {
            $result = Cache::store(config('cache.default'))->get("import_job_{$this->importJobId}");

            if ($result !== null) {
                $this->importStatus = $result['status'];
                $this->importFailureCount = $result['failure_count'] ?? 0;

                if ($result['status'] === 'done') {
                    $this->dispatch('permission-saved');

                    $msg = $this->importFailureCount > 0
                        ? __('Import complete. :count row(s) skipped due to validation errors.', ['count' => $this->importFailureCount])
                        : __('Import completed successfully.');

                    $this->dispatch('notify', type: 'success', message: $msg);

                    $pending = broadcast(new PermissionRecordChanged);
                    if (request()->hasHeader('X-Socket-ID')) {
                        $pending->toOthers();
                    }
                } elseif ($result['status'] === 'failed') {
                    $this->dispatch('notify', type: 'error', message: __('Import failed: :msg', ['msg' => $result['message'] ?? '']));
                }
            }
        }

        if ($this->exportStatus === 'queued') {
            $result = Cache::store(config('cache.default'))->get("export_job_{$this->exportJobId}");

            if ($result !== null) {
                $this->exportStatus = $result['status'];

                if ($result['status'] === 'done') {
                    $this->exportUrl = $result['url'];
                    $this->exportFilename = $result['filename'];
                    $this->dispatch('notify', type: 'success', message: __('Export ready. Click to download.'));
                } elseif ($result['status'] === 'failed') {
                    $this->dispatch('notify', type: 'error', message: __('Export failed: :msg', ['msg' => $result['message'] ?? '']));
                }
            }
        }
    }

    public function render(): View
    {
        return view('accesscontrol::livewire.permissions.import-export-modal');
    }
}
