<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkshopToolsAccessTest extends TestCase
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
        Schema::create('personnel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('userId', 36)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('isActive')->default(true);
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
        });
    }

    public function test_workshop_user_can_open_plan_and_cabinet_tools(): void
    {
        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Atölye',
            'email' => 'atolye@example.test',
            'passwordHash' => bcrypt('secret'),
            'role' => 'user',
            'isActive' => true,
        ]);

        \DB::table('personnel')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'userId' => $user->id,
            'category' => 'atölye',
            'isActive' => true,
        ]);

        $this->actingAs($user)->get(route('tools.plan'))->assertOk();
        $this->actingAs($user)->get(route('tools.cabinet'))->assertOk();
        $this->actingAs($user)->get(route('wardrobe.index'))->assertOk();
        $response = $this->actingAs($user)->get(route('wardrobe.design'));
        $response->assertOk()
            ->assertSee('Modül ekle', false)
            ->assertSee('wardrobe-studio.js', false)
            ->assertDontSee('petervanderwalt/cabinet-studio', false)
            ->assertDontSee('Cabinet Studio arayüzü', false);
    }
}