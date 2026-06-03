<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorksTable extends Migration
{
    public function up()
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('work_date');
            $table->dateTime('work_start')->nullable();
            $table->dateTime('work_end')->nullable();
            $table->time('break_total')->nullable();
            $table->time('work_total')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('works');
    }
}
