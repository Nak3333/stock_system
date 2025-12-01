<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * ROLES & PERMISSIONS
         */
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->text('description')->nullable();
            $table->timestampsTz(); // 👈 add created_at & updated_at
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 100)->unique();  // e.g. PRODUCT_CREATE
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestampsTz(); // 👈 add created_at & updated_at
        });

        /**
         * USERS & AUTH
         */
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);   // You can rename to "password" if you like
            $table->string('full_name', 150)->nullable();
            $table->string('email', 150)->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();                 // created_at, updated_at with time zone
        });

        Schema::create('auth_logins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users');
            $table->timestampTz('login_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(true);
            // optional: if you also want created_at/updated_at for login logs:
            // $table->timestampsTz();
        });

        /**
         * Many-to-many: users <-> roles
         */
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('role_id')->constrained('roles');
            $table->primary(['user_id', 'role_id']);
        });

        /**
         * Many-to-many: roles <-> permissions
         */
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('permission_id')->constrained('permissions');
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('auth_logins');
        Schema::dropIfExists('users');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
