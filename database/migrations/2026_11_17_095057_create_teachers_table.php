<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('nip', 18)->nullable()->unique()->comment('Nomor Induk Pegawai');
            $table->string('nuptk', 16)->nullable()->unique()->comment('Nomor Unik Pendidik dan Tenaga Kependidikan');
            $table->enum('employment_status', ['permanent', 'contract', 'honorary'])->default('contract');
            $table->date('hire_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
