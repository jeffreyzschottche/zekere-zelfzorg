<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('care_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('alert_cursor')->default(0);
        });
        foreach (DB::table('care_reviews')->get(['id', 'care_execution_id', 'reviewed_at']) as $review) {
            $observed = DB::table('care_alerts')->where('care_execution_id', $review->care_execution_id)
                ->where('created_at', '<=', $review->reviewed_at)->max('id');
            DB::table('care_reviews')->where('id', $review->id)->update(['alert_cursor' => $observed ?? 0]);
        }
        Schema::table('care_reviews', function (Blueprint $table) {
            $table->unique(['care_execution_id', 'alert_cursor']);
            $table->dropUnique(['care_execution_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('care_reviews')->groupBy('care_execution_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Terugdraaien zou eerdere beoordelingen onverenigbaar maken met het oude schema.');
        }
        Schema::table('care_reviews', function (Blueprint $table) {
            $table->unique('care_execution_id');
            $table->dropUnique(['care_execution_id', 'alert_cursor']);
            $table->dropColumn('alert_cursor');
        });
    }
};
