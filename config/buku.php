<?php

return [

    'agenda_surat_masuk' => [
        'judul' => 'Agenda Surat Masuk',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'TANGGAL SURAT', 'key' => 'tanggal_surat'],
            ['label' => 'TANGGAL TERIMA', 'key' => 'tanggal_terima'],
            ['label' => 'NO SURAT DITERIMA', 'key' => 'no_surat'],
            ['label' => 'DARI', 'key' => 'dari'],
            ['label' => 'PERIHAL', 'key' => 'perihal'],
            ['label' => 'LAMPIRAN', 'key' => 'lampiran'],
            ['label' => 'DITERUSKAN KEPADA', 'key' => 'diteruskan_kepada'],
            ['label' => 'STATUS TL', 'key' => 'status_tindak_lanjut'],
        ],
    ],

    'agenda_surat_keluar' => [
        'judul' => 'Agenda Surat Keluar',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'NO SURAT', 'key' => 'no_surat'],
            ['label' => 'TANGGAL SURAT', 'key' => 'tanggal_surat'],
            ['label' => 'KEPADA', 'key' => 'kepada'],
            ['label' => 'PERIHAL', 'key' => 'perihal'],
            ['label' => 'LAMPIRAN', 'key' => 'lampiran'],
            ['label' => 'TEMBUSAN', 'key' => 'tembusan'],
        ],
    ],

    'daftar_hadir' => [
        'judul' => 'Daftar Hadir',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'NAMA', 'key' => 'nama'],
            ['label' => 'ALAMAT', 'key' => 'alamat'],
            ['label' => 'JABATAN', 'key' => 'jabatan'],
            ['label' => 'KETERANGAN', 'key' => 'keterangan'],
            ['label' => 'TANDA TANGAN', 'key' => 'tanda_tangan'],
        ],
    ],

    'buku_kegiatan' => [
        'judul' => 'Buku Kegiatan',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'TANGGAL', 'key' => 'tanggal'],
            ['label' => 'NAMA', 'key' => 'nama'],
            ['label' => 'JABATAN', 'key' => 'jabatan'],
            ['label' => 'TEMPAT', 'key' => 'tempat'],
            ['label' => 'URAIAN', 'key' => 'uraian'],
            ['label' => 'TANDA TANGAN', 'key' => 'tanda_tangan'],
        ],
    ],

    'notulen' => [
        'judul' => 'Notulen Rapat',
        'tipe' => 'notulen',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [],
    ],

    'daftar_anggota' => [
        'judul' => 'Daftar Anggota',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Bendahara'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'NAMA', 'key' => 'nama'],
            ['label' => 'JABATAN', 'key' => 'jabatan'],
            ['label' => 'JENIS KELAMIN', 'key' => 'jenis_kelamin'],
            ['label' => 'TEMPAT LAHIR', 'key' => 'tempat_lahir'],
            ['label' => 'TANGGAL LAHIR', 'key' => 'tanggal_lahir'],
            ['label' => 'UMUR', 'key' => 'umur'],
            ['label' => 'STATUS', 'key' => 'status'],
            ['label' => 'ALAMAT', 'key' => 'alamat'],
            ['label' => 'PENDIDIKAN', 'key' => 'pendidikan'],
            ['label' => 'PEKERJAAN', 'key' => 'pekerjaan'],
            ['label' => 'KETERANGAN', 'key' => 'keterangan'],
        ],
    ],

    'daftar_anggota_tp_pkk' => [
        'judul' => 'Daftar Anggota TP PKK dan Kader',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Bendahara'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'NO REG. TP PKK', 'key' => 'no_registrasi_tp_pkk'],
            ['label' => 'NAMA', 'key' => 'nama'],
            ['label' => 'JENIS KELAMIN', 'key' => 'jenis_kelamin'],
            ['label' => 'ANGGOTA TP PKK', 'key' => 'dalam_keanggotaan_tp_pkk'],
            ['label' => 'KADER UMUM', 'key' => 'kader_umum'],
            ['label' => 'KADER KHUSUS', 'key' => 'kader_khusus'],
            ['label' => 'TANGGAL LAHIR', 'key' => 'tanggal_lahir'],
            ['label' => 'UMUR', 'key' => 'umur'],
            ['label' => 'STATUS', 'key' => 'status'],
            ['label' => 'ALAMAT', 'key' => 'alamat'],
            ['label' => 'PENDIDIKAN', 'key' => 'pendidikan'],
            ['label' => 'PEKERJAAN', 'key' => 'pekerjaan'],
            ['label' => 'KETERANGAN', 'key' => 'keterangan'],
        ],
    ],

    'buku_tamu' => [
        'judul' => 'Buku Tamu',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'TANGGAL', 'key' => 'tanggal'],
            ['label' => 'NAMA TAMU', 'key' => 'nama_tamu'],
            ['label' => 'ALAMAT', 'key' => 'alamat'],
            ['label' => 'KEPERLUAN', 'key' => 'keperluan'],
            ['label' => 'TUJUAN', 'key' => 'tujuan'],
            ['label' => 'TANDA TANGAN', 'key' => 'tanda_tangan'],
        ],
    ],

    'buku_kunjungan' => [
        'judul' => 'Buku Kunjungan',
        'tipe' => 'tabel',
        'tanda_tangan' => [
            ['peran' => 'Ketua'],
            ['peran' => 'Sekretaris'],
        ],
        'kolom' => [
            ['label' => 'NO', 'key' => 'no'],
            ['label' => 'TANGGAL', 'key' => 'tanggal'],
            ['label' => 'NAMA', 'key' => 'nama'],
            ['label' => 'JABATAN', 'key' => 'jabatan'],
            ['label' => 'LOKASI KUNJUNGAN', 'key' => 'lokasi_kunjungan'],
            ['label' => 'JENIS KEGIATAN', 'key' => 'jenis_kegiatan'],
            ['label' => 'KETERANGAN', 'key' => 'keterangan'],
            ['label' => 'TANDA TANGAN', 'key' => 'tanda_tangan'],
        ],
    ],

];
