<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function frameworkTables(): array
    {
        return [
            'sessions' => ['sessions'],
            'password reset tokens' => ['password_reset_tokens'],
        ];
    }

    #[DataProvider('frameworkTables')]
    public function test_migrations_create_the_tables_laravel_needs(string $table): void
    {
        $this->assertTrue(Schema::hasTable($table), "The {$table} table is missing after migrating.");
    }

    public function test_login_page_loads_with_database_sessions(): void
    {
        config(['session.driver' => 'database']);

        $this->get(route('login'))->assertOk();
    }
}
