@extends('layouts.app', [
    'title' => 'Jejak Audit — Buku PKK Digital',
    'header' => 'Jejak Audit',
])

@section('page_content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('audit.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="log_name">Model / log</label>
                    <select name="log_name" id="log_name" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($logNames as $name)
                            <option value="{{ $name }}" @selected($filters['log_name'] === $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="user_id">Pengguna</label>
                    <select name="user_id" id="user_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Log</th>
                        <th>Peristiwa</th>
                        <th>Pengguna</th>
                        <th>Subjek</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td>{{ $activity->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td>
                            <td>{{ $activity->log_name }}</td>
                            <td>{{ $activity->description }}</td>
                            <td>
                                @if ($activity->causer)
                                    {{ $activity->causer->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ class_basename($activity->subject_type ?? '') }}
                                #{{ $activity->subject_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">Belum ada aktivitas tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())
            <div class="card-footer">{{ $activities->links() }}</div>
        @endif
    </div>
@endsection
