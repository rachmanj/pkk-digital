@extends('layouts.app', [
    'title' => 'Pengguna — Buku PKK Digital',
    'header' => 'Pengguna',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('pengguna.create') }}" class="btn btn-primary btn-sm">Tambah pengguna</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Nama pengguna</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Pokja</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                            <td>{{ $user->pokja?->kode ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('pengguna.edit', $user) }}" class="btn btn-outline-secondary btn-sm">Ubah</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
