<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfilePhoto;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

class ProfileController extends Controller
{
    public function __construct(private ProfilePhotoService $photoService) {}

    // 1. Update Nama, Email, dan Nomor HP
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'no_hp' => 'required|string|max:20',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'no_hp' => $validated['no_hp'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diperbarui.',
            'user' => $user->fresh(),
        ], 200);
    }

    // 2. Ganti Kata Sandi dengan Verifikasi Kata Sandi Lama
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini yang Anda masukkan salah.',
            ], 422);
        }

        // Cast 'hashed' di Model User otomatis mengenkripsi password baru
        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
        ], 200);
    }

    // 3. Upload & Konversi Foto Profil (Validasi MIME ketat & Maksimal 5 MB)
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120', // 5120 KB = 5 MB
            ],
        ], [
            'foto.required' => 'Silakan pilih foto profil terlebih dahulu.',
            'foto.image' => 'File yang diunggah harus berupa gambar.',
            'foto.mimes' => 'Ekstensi gambar hanya mendukung JPG, JPEG, PNG, atau WebP.',
            'foto.mimetypes' => 'Tipe MIME file tidak diizinkan oleh server.',
            'foto.max' => 'Ukuran foto maksimal adalah 5 MB.',
        ]);

        $user = $request->user();

        try {
            // Crop 1:1 + 512px + konversi WebP, hasilnya binary di memori
            $optimized = $this->photoService->optimize($request->file('foto'));

            // Disimpan di database: filesystem Render ephemeral (hilang saat restart)
            ProfilePhoto::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'mime' => $optimized['mime'],
                    'data' => base64_encode($optimized['binary']),
                ]
            );

            // Simpan URL publik + versi (cache-busting) agar HP langsung ambil foto baru
            $fotoUrl = url("/api/profile/photo/{$user->id}").'?v='.time();
            $user->update(['foto' => $fotoUrl]);

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diunggah dan dioptimasi.',
                'user' => $user->fresh(),
                'foto_url' => $fotoUrl,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal memproses foto profil.',
            ], 422);
        }
    }

    // 4. Sajikan Foto Profil dari Database (publik, tanpa token)
    public function showPhoto(Request $request, int $user)
    {
        $photo = ProfilePhoto::where('user_id', $user)->first();

        if (! $photo) {
            return response()->json([
                'success' => false,
                'message' => 'Foto profil tidak ditemukan.',
            ], 404);
        }

        $binary = $photo->binary();

        return response($binary, 200, [
            'Content-Type' => $photo->mime ?: 'image/webp',
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
