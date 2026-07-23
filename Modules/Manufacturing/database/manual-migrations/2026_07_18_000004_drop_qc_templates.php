<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'manufacturing';

    public function up(): void
    {
        if (Schema::connection('manufacturing')->hasTable('qc_results')) {
            DB::connection('manufacturing')->statement(
                'ALTER TABLE qc_results DROP CONSTRAINT IF EXISTS qc_results_check_id_fkey'
            );
        }

        Schema::connection('manufacturing')->dropIfExists('qc_templates');
    }

    public function down(): void
    {
        // Intentionally left blank — qc_templates is retired in favor of
        // config('nexora.benchmarkTargets').
    }
};
