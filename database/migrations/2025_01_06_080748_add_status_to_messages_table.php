<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_sent')->default(false);
            $table->boolean('is_delivered')->default(false);
            $table->boolean('is_seen')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('seen_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_sent', 'is_delivered', 'is_seen', 'delivered_at', 'seen_at']);
        });
    }
};
