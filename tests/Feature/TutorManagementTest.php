<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('migrate');
});

test('guest cannot access tutor management page', function () {
    $response = $this->get('/admin/tutor');
    $response->assertRedirect('/login');
});

test('authenticated user can view tutor management page with active tutors list', function () {
    $user = User::factory()->make(['id' => 1, 'name' => 'Admin Operasional', 'email' => 'admin@racer-robotic.id']);

    $response = $this->actingAs($user)->get('/admin/tutor');

    $response->assertStatus(200);
    $response->assertSee('Data & Manajemen Akun Tutor', false);
    $response->assertSee('Total Tutor Terdaftar');
    $response->assertSee('Tutor Aktif Mengajar');
    $response->assertSee('Sesi Mengajar Hari ini');
    $response->assertSee('Kapasitas Sesi Mengajar');
    $response->assertSee('Tambah Tutor');
    $response->assertSee('Budi Santoso, S.T.');
    $response->assertSee('Dina Pratiwi, S.Kom.');
    $response->assertSee('Rizky Pratama, M.T.');
    $response->assertSee('TTR-001');
    $response->assertSee('TTR-002');
    $response->assertSee('TTR-003');
    $response->assertSee('Kak Budi');
    $response->assertSee('Kak Dina');
    $response->assertSee('Kak Rizky');
    $response->assertSee('Level 1: Building (TK/SD)');
    $response->assertSee('Level 2: Sensor (SD/SMP)');
    $response->assertSee('Level 3: Controller (SMP/SMA)');
});

test('validation fails when submitting empty tutor form', function () {
    $user = User::factory()->make(['id' => 1, 'name' => 'Admin Operasional', 'email' => 'admin@racer-robotic.id']);

    $response = $this->actingAs($user)->post('/admin/tutor', []);

    $response->assertSessionHasErrors([
        'nama_gelar',
        'whatsapp',
        'email',
        'tautan_meeting',
        'jenjang_modul',
        'penempatan',
        'kata_sandi_sementara',
    ]);
});

test('validation succeeds and redirects when submitting valid tutor form', function () {
    $user = User::factory()->make(['id' => 1, 'name' => 'Admin Operasional', 'email' => 'admin@racer-robotic.id']);

    $response = $this->actingAs($user)->post('/admin/tutor', [
        'nama_gelar' => 'Ahmad Fauzi, S.T.',
        'whatsapp' => '+62 813-4567-8901',
        'email' => 'ahmad.fauzi@racer.id',
        'tautan_meeting' => 'https://meet.google.com/rcr-fauzi',
        'jenjang_modul' => 'Level 3: Controller (SMP/SMA)',
        'penempatan' => 'Lab B - Robotics Hub',
        'max_kelas_minggu' => '12 Sesi Kelas / Minggu',
        'preferensi_mengajar' => 'Akhir Pekan + Sore Hari',
        'kata_sandi_sementara' => 'RacerTutor2026!',
    ]);

    $response->assertRedirect('/admin/tutor');
    $response->assertSessionHas('success');
});

test('newly created tutor can login with email and temporary password to tutor portal', function () {
    $admin = User::factory()->make(['id' => 1, 'name' => 'Admin', 'username' => 'admin', 'email' => 'admin@raccer.com']);

    $tutorEmail = 'budi.baru@racer.id';
    $tempPassword = 'PasswordTutorBaru2026!';

    // Buat tutor baru via admin
    $createResponse = $this->actingAs($admin)->post('/admin/tutor', [
        'nama_gelar' => 'Budi Baru, S.Kom.',
        'whatsapp' => '081299887766',
        'email' => $tutorEmail,
        'tautan_meeting' => 'https://meet.google.com/budi-baru',
        'jenjang_modul' => 'Level 1: Building (TK/SD)',
        'penempatan' => 'Lab A - Jakarta Pusat',
        'max_kelas_minggu' => '10 Sesi',
        'preferensi_mengajar' => 'Sabtu & Minggu',
        'kata_sandi_sementara' => $tempPassword,
    ]);
    $createResponse->assertRedirect('/admin/tutor');

    // Logout admin
    auth()->logout();

    // Login sebagai tutor baru menggunakan email
    $loginResponse = $this->post('/login', [
        'username' => $tutorEmail,
        'password' => $tempPassword,
    ]);

    $loginResponse->assertRedirect(route('tutor'));
    expect(auth()->check())->toBeTrue();
    expect(session('role'))->toBe('Tutor');
});
