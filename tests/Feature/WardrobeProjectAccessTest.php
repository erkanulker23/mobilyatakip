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

    public function test_design_bounds_reject_a_module_past_the_wall_and_keep_a_valid_one(): void
    {
        $owner = $this->makeUser('wardrobe-bounds@example.test', 'Ölçü', 'staff');
        $created = $this->actingAs($owner)->postJson('/dolap/proje', $this->payload('Ölçü', '05550000009'));
        $created->assertOk();
        $token = $created->json('token');
        $id = $created->json('id');

        $tooWide = $this->payload('Ölçü', '05550000009');
        $tooWide['design'] = [
            'wallWidth' => 180,
            'modules' => [[
                'id' => 'bad',
                'product' => 'tek-raf',
                'kind' => 'wardrobe',
                'wall' => 'front',
                'width' => 200,
                'offset' => 0,
            ]],
        ];
        $this->actingAs($owner)->putJson('/dolap/proje/'.$token, $tooWide)->assertStatus(422);
        $this->assertSame('Ölçü', WardrobeProject::query()->find($id)->projectName);

        $unknown = $this->payload('Ölçü', '05550000009');
        $unknown['design'] = [
            'modules' => [[
                'id' => 'ghost',
                'product' => 'olmayan-modul',
                'width' => 45,
                'offset' => 0,
            ]],
        ];
        $this->actingAs($owner)->putJson('/dolap/proje/'.$token, $unknown)
            ->assertStatus(422)
            ->assertJsonFragment(['Bilinmeyen modül kimliği.']);

        $good = $this->payload('180 duvar', '05550000009');
        $good['design'] = [
            'wallWidth' => 180,
            'leftWall' => 300,
            'rightWall' => 300,
            'roomHeight' => 260,
            'height' => 250,
            'depth' => 60,
            'modules' => [
                [
                    'id' => 'a',
                    'product' => 'cift-raf',
                    'kind' => 'wardrobe',
                    'wall' => 'front',
                    'width' => 90,
                    'offset' => 0,
                    'height' => 250,
                ],
                [
                    'id' => 'b',
                    'product' => 'custom',
                    'glb' => 'y26_tek_01',
                    'kind' => 'wardrobe',
                    'wall' => 'front',
                    'width' => 90,
                    'offset' => 90,
                    'height' => 250,
                ],
            ],
        ];
        $this->actingAs($owner)->putJson('/dolap/proje/'.$token, $good)->assertOk();
        $saved = WardrobeProject::query()->find($id);
        $this->assertSame('180 duvar', $saved->projectName);
        $this->assertCount(2, $saved->design['modules']);

        $corner = $this->payload('Köşe', '05550000009');
        $corner['design'] = [
            'wallWidth' => 320,
            'leftWall' => 300,
            'rightWall' => 280,
            'modules' => [[
                'id' => 'kose',
                'product' => 'custom',
                'glb' => 'kose_2',
                'kind' => 'corner',
                'cornerSide' => 'left',
                'wall' => 'front',
                'width' => 90,
                'offset' => 10000,
                'height' => 250,
            ]],
        ];
        $this->actingAs($owner)->putJson('/dolap/proje/'.$token, $corner)->assertOk();
        $this->assertSame(10000, (int) WardrobeProject::query()->find($id)->design['modules'][0]['offset']);

        $sentinel = $this->payload('Bozuk', '05550000009');
        $sentinel['design'] = [
            'wallWidth' => 320,
            'modules' => [[
                'id' => 'bad-offset',
                'product' => 'tek-raf',
                'kind' => 'wardrobe',
                'wall' => 'front',
                'width' => 45,
                'offset' => 10000,
            ]],
        ];
        $this->actingAs($owner)->putJson('/dolap/proje/'.$token, $sentinel)
            ->assertStatus(422)
            ->assertJsonFragment(['Bozuk konum verisi.']);
        $this->assertSame('Köşe', WardrobeProject::query()->find($id)->projectName);
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
