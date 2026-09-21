<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ResetApplicationData
{
    /**
     * @var list<string>
     */
    private const PRESERVED_PUBLIC_FILES = [
        'image/logo.png',
        'image/contohmember.png',
        'image/banner/banner1.png',
    ];

    public function handle(): void
    {
        DB::transaction(function (): void {
            foreach ([
                'team_user',
                'tournament_team',
                'tournament_user',
                'account_listing_images',
                'orders',
                'media_assets',
                'sponsors',
                'account_listings',
                'products',
                'tournaments',
                'teams',
                'visitor_presences',
                'password_reset_tokens',
                'sessions',
                'cache_locks',
                'cache',
                'jobs',
                'job_batches',
                'failed_jobs',
            ] as $table) {
                DB::table($table)->delete();
            }

            DB::table('users')->where('role', '<>', 'admin')->delete();
        });

        $files = array_values(array_filter(
            Storage::disk('public')->allFiles(),
            fn (string $path): bool => ! in_array($path, self::PRESERVED_PUBLIC_FILES, true)
                && ! str_ends_with($path, '.gitignore'),
        ));

        if ($files !== []) {
            Storage::disk('public')->delete($files);
        }
    }
}
