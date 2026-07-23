<?php

namespace Modules\BusinessIntelligence\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstallBusinessIntelligenceSchema extends Command
{
    protected $signature = 'bi:install-schema';
    protected $description = 'Creates the dedicated, client-scoped BI tables without touching ITSM or module databases.';

    public function handle(): int
    {
        if (! config('database.connections.business_intelligence.url')) {
            $this->warn('BUSINESS_INTELLIGENCE_DB_URL is not configured; BI snapshot tables were skipped.');
            return self::SUCCESS;
        }

        $schema = Schema::connection('business_intelligence');

        if (! $schema->hasTable('bi_snapshots')) {
            $schema->create('bi_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('source')->default('live-dashboard');
                $table->json('payload');
                $table->timestamp('captured_at');
                $table->timestamps();
                $table->unique(['client_id', 'source']);
            });
        }

        if (! $schema->hasTable('bi_ai_conversations')) {
            $schema->create('bi_ai_conversations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('role', 16);
                $table->text('message');
                $table->boolean('used_ai')->default(false);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('bi_ai_reports')) {
            $schema->create('bi_ai_reports', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('report_type');
                $table->json('payload');
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }

        $this->info('Business Intelligence schema is ready and client-scoped.');
        return self::SUCCESS;
    }
}
