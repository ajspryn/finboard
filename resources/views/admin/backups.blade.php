@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Database Backups</h1>

    <form method="POST" action="{{ route('admin.backups.create') }}">
        @csrf
        <button class="btn btn-primary" type="submit">Create Backup</button>
    </form>

    <hr />

    <h4>Upload backup file (to import)</h4>
    <div class="card mb-4">
        <div class="card-body">
            <form id="uploadForm" method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Select backup (.sql, .gz)</label>
                    <input id="backupInput" class="form-control" type="file" name="backup_file" accept=".sql,.gz,.sql.gz" required />
                </div>
                <div class="mb-3 form-check">
                    <input id="autoRestore" class="form-check-input" type="checkbox" name="auto_restore" value="1" />
                    <label class="form-check-label" for="autoRestore">Auto-restore after upload (destructive)</label>
                </div>
                <div id="selectedFile" class="mb-3 text-muted">No file selected</div>
                <div class="mb-3">
                    <button id="uploadBtn" class="btn btn-warning" type="button">Upload</button>
                    <div id="uploadProgress" class="progress mt-2" style="height: 18px; display:none">
                        <div id="uploadProgressBar" class="progress-bar" role="progressbar" style="width:0%">0%</div>
                    </div>
                </div>
                <div class="text-muted">Note: uploaded file will be saved to <strong>storage/backups</strong>. Use Restore button to import (or enable auto-restore).</div>
            </form>
        </div>
    </div>

    <hr />

    <h3>Available backups</h3>
    <div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr><th>File</th><th>Size</th><th>Modified</th><th>Actions</th></tr>
        </thead>
        <tbody>
        @foreach($files as $file)
            <tr>
                <td>{{ $file['name'] }}</td>
                <td>{{ number_format($file['size']/1024, 2) }} KB</td>
                <td>{{ date('Y-m-d H:i:s', $file['mtime']) }}</td>
                <td>
                    <a class="btn btn-secondary" href="{{ route('admin.backups.download', ['file' => $file['name']]) }}">Download</a>
                    <form method="POST" action="{{ route('admin.backups.restore') }}" style="display:inline" onsubmit="return confirm('Restore will DROP and recreate the database. Continue?');">
                        @csrf
                        <input type="hidden" name="file" value="{{ $file['name'] }}" />
                        <button class="btn btn-danger" type="submit">Restore</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>

    <div class="row">
        <div class="col-12 d-flex justify-content-center">
            <nav aria-label="Backups pagination">
                {{ $files->onEachSide(1)->links('pagination::bootstrap-4') }}
            </nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('backupInput');
    const selected = document.getElementById('selectedFile');
    const uploadBtn = document.getElementById('uploadBtn');
    const form = document.getElementById('uploadForm');
    const progress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('uploadProgressBar');

    function humanSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024, sizes = ['B','KB','MB','GB','TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }

    input.addEventListener('change', function(e){
        const f = input.files[0];
        if (!f) {
            selected.textContent = 'No file selected';
            return;
        }
        selected.textContent = `Selected: ${f.name} (${humanSize(f.size)})`;
    });

    uploadBtn.addEventListener('click', function(){
        const f = input.files[0];
        if (!f) { alert('Choose a file first'); return; }
        const auto = document.getElementById('autoRestore').checked ? '1' : '';
        if (auto && !confirm('Auto-restore will DROP your database. Are you sure?')) return;

        const formData = new FormData();
        formData.append('backup_file', f);
        if (auto) formData.append('auto_restore', '1');
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.upload.addEventListener('progress', function(e){
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                progress.style.display = 'block';
                progressBar.style.width = pct + '%';
                progressBar.textContent = pct + '%';
            }
        });
        xhr.onreadystatechange = function(){
            if (xhr.readyState === 4) {
                progress.style.display = 'none';
                progressBar.style.width = '0%';
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.status === 'uploaded') {
                        alert('Upload complete: ' + res.file + (res.restore ? ' (restore: '+res.restore+')' : ''));
                        location.reload();
                        return;
                    }
                } catch (err) {
                    // fallback: reload to show flash messages
                    location.reload();
                }
            }
        };
        xhr.send(formData);
    });
});
</script>
@endpush
