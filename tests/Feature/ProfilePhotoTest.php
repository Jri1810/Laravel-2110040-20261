<?php

namespace Tests\Feature;

use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    private function makeJpeg(int $width = 1200, int $height = 800): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 30, 138, 239);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        ob_start();
        imagejpeg($image, null, 90);
        $binary = ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    public function test_register_membuat_token_dan_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Fajri',
            'email' => 'fajri@example.com',
            'no_hp' => '08123456789',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'no_hp']]);
    }

    public function test_upload_foto_menyimpan_webp_di_database(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->createWithContent(
            'foto.jpg',
            $this->makeJpeg()
        );

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/profile/photo', ['foto' => $file]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $photo = ProfilePhoto::where('user_id', $user->id)->first();
        $this->assertNotNull($photo, 'Foto harus tersimpan di tabel profile_photos');
        $this->assertSame('image/webp', $photo->mime);

        $binary = $photo->binary();
        $this->assertNotSame('', $binary);

        // Verifikasi hasil konversi benar-benar WebP dan berukuran 1:1 maks 512px
        $info = getimagesizefromstring($binary);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertLessThanOrEqual(512, $info[0]);
        $this->assertSame($info[0], $info[1]);
    }

    public function test_endpoint_foto_publik_mengembalikan_gambar(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->createWithContent(
            'foto.jpg',
            $this->makeJpeg()
        );

        $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/profile/photo', ['foto' => $file])
            ->assertStatus(200);

        $response = $this->get("/api/profile/photo/{$user->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/webp');
        $this->assertStringStartsWith('RIFF', $response->getContent());
    }

    public function test_user_foto_berisi_url_https_yang_bisa_diakses(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->createWithContent(
            'foto.jpg',
            $this->makeJpeg()
        );

        $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/profile/photo', ['foto' => $file]);

        $foto = $user->fresh()->foto;
        $this->assertNotNull($foto);
        $this->assertStringContainsString("/api/profile/photo/{$user->id}", $foto);
    }

    public function test_upload_menolak_file_yang_bukan_gambar(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->createWithContent('dokumen.txt', 'bukan gambar');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/profile/photo', ['foto' => $file])
            ->assertStatus(422);

        $this->assertNull(ProfilePhoto::where('user_id', $user->id)->first());
    }

    public function test_update_profil_dan_password(): void
    {
        $user = User::factory()->create(['no_hp' => '0811']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/profile', [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
                'no_hp' => '0899',
            ])
            ->assertStatus(200)
            ->assertJsonPath('user.name', 'Nama Baru');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertStatus(200);

        $this->assertTrue(password_verify('rahasia123', $user->fresh()->password));
    }
}
