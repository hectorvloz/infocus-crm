<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('password');
            $table->string('cliente_id')->nullable()->index()->after('role');
            $table->boolean('must_change_password')->default(false)->after('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'cliente_id', 'must_change_password']);
        });
    }
};
