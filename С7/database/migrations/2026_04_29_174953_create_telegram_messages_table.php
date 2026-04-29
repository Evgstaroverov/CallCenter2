<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('message_id')->unique();
            $table->bigInteger('chat_id');
            $table->bigInteger('user_id')->nullable();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->text('text')->nullable();
            $table->string('message_type')->default('text');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_replied')->default(false);
            $table->text('reply_text')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_messages');
    }
};