<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileDeviceTokensTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_device_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('fcm_token', 512);
            $table->string('platform', 20);
            $table->string('device_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('fcm_token', 'mobile_device_tokens_fcm_token_unique');
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_device_tokens');
    }
}
