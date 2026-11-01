<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\WardrobeProject;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class WardrobeProjectAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('passwordHash')->nullable();
            $table->string('role')->nullable();
            $table->boolean('isActive')->default(true);
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
        Schema::create('wardrobe_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 40)->unique();
            $table->string('userId', 36)->nullable()->index();
            $table->string('customerName');
            $table->string('phone', 40);
            $table->string('projectName', 80);
            $table->text('note')->nullable();
            $table->json('design');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
        Schema::create('personnel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userId', 36)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('isActive')->default(true);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userId', 36)->nullable();
            $table->string('entity', 100);
            $table->string('entityId', 36)->nullable();
            $table->string('action', 50);
            $table->json('oldValue')->nullable();
            $table->json('newValue')->nullable();
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();
            $table->timestamp('createdAt')->nullable();
        });
    }

    public function test_owner_can_manage_a_project_and_another_user_cannot(): void
    {
        $owner = $this->makeUser('wardrobe-a@example.test', 'Kullanıcı A', 'staff');
        $other = $this->makeUser('wardrobe-b@example.test', 'Kullanıcı B', 'staff');
        $admin = $this->makeUser('wardrobe-admin@example.test', 'Yönetici', 'admin');

        $this->getJson('/dolap/projeler')->assertUnauthorized();
        $this->get('/dolap/projeler')->assertRedirect();

        $created = $this->actingAs($owner)->postJson('/dolap/proje', $this->payload('A projesi', '05550000001'));
        $created->assertOk();
        $created->assertJsonPath('ok', true);
        $token = $created->json('token');
        $id = $created->json('id');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/', $token);
        $this->assertNotEmpty($id);

        $ownList = $this->actingAs($owner)->getJson('/dolap/projeler?phone=05550000001');
        $ownList->assertOk();
        $ownList->assertJsonPath('projects.0.id', $id);
        $ownList->assertJsonPath('projects.0.projectName', 'A projesi');
        $this->assertStringNotContainsString($token, $ownList->getContent());
        $this->assertStringNotContainsString('05550000001', $ownList->getContent());
        $this->assertStringNotContainsString('Müşteri A', $ownList->getContent());

        $this->actingAs($owner)->getJson('/dolap/proje/'.$token)
            ->assertOk()
            ->assertJsonPath('projectName', 'A projesi')
            ->assertJsonPath('customerName', 'Müşteri A');

        $this->actingAs($owner)->getJson('/dolap/proje/kayit/'.$id)
            ->assertOk()
            ->assertJsonPath('id', $id);

        $this->actingAs($owner)->putJson('/dolap/proje/'.$token, $this->payload('A güncellendi', '05550000001'))
            ->assertOk()
            ->assertJsonPath('id', $id);

        $foreignList = $this->actingAs($other)->getJson('/dolap/projeler?phone=05550000001');
        $foreignList->assertOk();
        $foreignList->assertJsonPath('projects', []);
        $this->assertStringNotContainsString($token, $foreignList->getContent());
        $this->assertStringNotContainsString('A güncellendi', $foreignList->getContent());

        foreach (['/dolap/proje/'.$token, '/dolap/proje/kayit/'.$id] as $url) {
            $read = $this->actingAs($other)->getJson($url);
            $read->assertNotFound();
            $read->assertExactJson(['ok' => false, 'message' => 'Proje bulunamadı.']);

            $update = $this->actingAs($other)->putJson($url, $this->payload('B yazdı', '05550000002'));
            $update->assertNotFound();
            $update->assertExactJson(['ok' => false, 'message' => 'Proje bulunamadı.']);

            $delete = $this->actingAs($other)->deleteJson($url);
            $delete->assertNotFound();
            $delete->assertExactJson(['ok' => false, 'message' => 'Proje bulunamadı.']);
        }

        $missing = $this->actingAs($other)->getJson('/dolap/proje/'.Str::random(24));
        $missing->assertNotFound();
        $missing->assertExactJson(['ok' => false, 'message' => 'Proje bulunamadı.']);

        $this->assertSame('A güncellendi', WardrobeProject::query()->find($id)->projectName);
        $this->assertSame(0, AuditLog::query()->where('newValue', 'like', '%'.$token.'%')->count());
        $this->assertGreaterThan(0, AuditLog::query()->where('entity', 'wardrobe_project')->where('action', 'denied')->count());

        $adminList = $this->actingAs($admin)->getJson('/dolap/projeler');
        $adminList->assertOk();
        $this->assertStringNotContainsString('A güncellendi', $adminList->getContent());
        $this->actingAs($admin)->getJson('/dolap/proje/'.$token)->assertNotFound();

        $legacy = WardrobeProject::create([
            'token' => Str::random(24),
            'userId' => null,
            'customerName' => 'Eski kayıt',
            'phone' => '05559999999',
            'projectName' => 'Sahipsiz proje',
            'note' => null,
            'design' => ['modules' => []],
        ]);

        $this->actingAs($owner)->getJson('/dolap/projeler')->assertJsonMissing(['projectName' => 'Sahipsiz proje']);
        $this->actingAs($other)->getJson('/dolap/proje/'.$legacy->token)->assertNotFound();
        $this->actingAs($admin)->getJson('/dolap/proje/'.$legacy->token)
            ->assertOk()
            ->assertJsonPath('projectName', 'Sahipsiz proje');
        $adminLegacy = $this->actingAs($admin)->getJson('/dolap/projeler');
        $adminLegacy->assertOk();
        $this->assertStringContainsString('Sahipsiz proje', $adminLegacy->getContent());
        $this->assertStringNotContainsString($legacy->token, $adminLegacy->getContent());
        $this->assertStringNotContainsString('05559999999', $adminLegacy->getContent());
        $this->assertNull($legacy->fresh()->userId);

        $this->actingAs($owner)->deleteJson('/dolap/proje/kayit/'.$id)
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->actingAs($owner)->getJson('/dolap/proje/'.$token)->assertNotFound();
        $this->assertNull(WardrobeProject::query()->find($id));
    }

    private function makeUser(string $email, string $name, string $role): User
    {
        $user = new User([
            'email' => $email,
            'name' => $name,
            'role' => $role,
            'isActive' => true,
        ]);
        $user->password = 'test-password';
        $user->save();

        return $user;
    }

    /** @return array<string, mixed> */
    private function payload(string $projectName, string $phone): array
    {
        return [
            'customerName' => 'Müşteri A',
            'phone' => $phone,
            'projectName' => $projectName,
            'note' => 'deneme',
            'design' => ['modules' => []],
        ];
    }
}
