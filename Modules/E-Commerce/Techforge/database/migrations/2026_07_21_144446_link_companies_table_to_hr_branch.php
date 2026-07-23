<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'ecommerce';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enable postgres_fdw
        DB::connection('ecommerce')->statement('CREATE EXTENSION IF NOT EXISTS postgres_fdw');

        // 2. Drop the local companies table if it exists
        DB::connection('ecommerce')->statement('DROP TABLE IF EXISTS companies CASCADE');

        // 3. Create the foreign server pointing to HR branch
        DB::connection('ecommerce')->statement("
            CREATE SERVER IF NOT EXISTS hr_server
            FOREIGN DATA WRAPPER postgres_fdw
            OPTIONS (host 'ep-round-truth-aonozlho.c-2.ap-southeast-1.aws.neon.tech', dbname 'neondb', port '5432')
        ");

        // 4. Map the current user to the foreign server
        // Using neondb_owner since this is the default user in neon
        DB::connection('ecommerce')->statement("
            CREATE USER MAPPING IF NOT EXISTS FOR CURRENT_USER
            SERVER hr_server
            OPTIONS (user 'neondb_owner', password 'npg_zj31fUDEpYtd')
        ");

        // 5. Import the companies table from HR server
        DB::connection('ecommerce')->statement("
            IMPORT FOREIGN SCHEMA public
            LIMIT TO (companies)
            FROM SERVER hr_server
            INTO public
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('ecommerce')->statement('DROP SERVER IF EXISTS hr_server CASCADE');
        DB::connection('ecommerce')->statement('DROP EXTENSION IF NOT EXISTS postgres_fdw CASCADE');
    }
};
