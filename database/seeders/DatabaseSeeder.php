<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Product;
use App\Models\AccountListing;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['nickname' => 'admin'], ['name' => 'Neverland Admin', 'password' => 'password', 'role' => 'admin']);
        $memberNames = [
            ['Dimas Pratama', 'dimasprtm'], ['Nadia Aulia', 'nadiaa'], ['Raka Wijaya', 'raka-w'],
            ['Adit Nugraha', 'aditn'], ['Bima Saputra', 'bimafc'], ['Cahyo Ramadhan', 'cahyora'],
            ['Daffa Alamsyah', 'daffaal'], ['Eka Putra', 'ekaputra'], ['Fajar Hidayat', 'fajarhx'],
            ['Galih Prakoso', 'galihp'], ['Hafiz Maulana', 'hafizmaul'], ['Ilham Akbar', 'ilhamx'],
            ['Jovan Kurnia', 'jovank'], ['Kevin Ardiansyah', 'kevinard'], ['Luthfi Hakim', 'luthfih'],
            ['Miko Pratama', 'mikop'], ['Naufal Rizky', 'naufalr'], ['Oscar Wijaya', 'oscarw'],
            ['Putra Aditya', 'putraad'], ['Qori Ananda', 'qorian'], ['Rendi Firmansyah', 'rendif'],
            ['Surya Mahendra', 'suryam'], ['Tegar Wibowo', 'tegarw'], ['Ubay Fauzan', 'ubayf'],
            ['Vino Saputra', 'vinos'], ['Wahyu Setiawan', 'wahyus'], ['Yoga Pranata', 'yogap'],
            ['Zaki Ramadhan', 'zakir'], ['Alma Safitri', 'almas'], ['Bella Kirana', 'bellak'],
            ['Citra Lestari', 'citral'], ['Dinda Maharani', 'dindam'],
        ];
        $members = collect($memberNames)->map(fn (array $member) => tap(User::updateOrCreate(['nickname' => $member[1]], ['name' => $member[0], 'password' => 'password', 'role' => 'member']), function (User $user): void {
            $user->photo_path ??= 'image/contohmember.png';
            $user->save();
        }));

        $teamDefinitions = [
            ['slug' => 'neverland-01', 'name' => 'NVL1', 'code' => 'NVL1', 'division' => 'Pro Division', 'tone' => 'blue'],
            ['slug' => 'neverland-02', 'name' => 'NVL2', 'code' => 'NVL2', 'division' => 'Challenger', 'tone' => 'coral'],
            ['slug' => 'neverland-03', 'name' => 'NVL3', 'code' => 'NVL3', 'division' => 'Academy', 'tone' => 'purple'],
            ['slug' => 'neverland-04', 'name' => 'NVL4', 'code' => 'NVL4', 'division' => 'Pro Division', 'tone' => 'ink'],
            ['slug' => 'neverland-05', 'name' => 'NVL5', 'code' => 'NVL5', 'division' => 'Development', 'tone' => 'lime'],
            ['slug' => 'neverland-06', 'name' => 'NVL6', 'code' => 'NVL6', 'division' => 'Open Division', 'tone' => 'blue'],
            ['slug' => 'neverland-07', 'name' => 'NVL7', 'code' => 'NVL7', 'division' => 'Open Division', 'tone' => 'coral'],
            ['slug' => 'neverland-08', 'name' => 'NVL8', 'code' => 'NVL8', 'division' => 'Academy', 'tone' => 'purple'],
        ];
        foreach ($teamDefinitions as $definition) {
            Team::updateOrCreate(['slug' => $definition['slug']], [...$definition, 'game' => 'Total Football', 'logo_path' => 'image/logo.png']);
        }

        $teams = Team::whereIn('slug', collect(range(1, 8))->map(fn (int $number) => 'neverland-'.str_pad($number, 2, '0', STR_PAD_LEFT)))->orderBy('slug')->get();
        $members->chunk(4)->each(function ($chunk, int $index) use ($teams): void {
            $team = $teams->get($index);
            if ($team) {
                $team->members()->sync($chunk->values()->mapWithKeys(fn (User $member, int $position) => [$member->id => ['position' => $position === 0 ? 'Captain' : 'Member']])->all());
            }
        });

        $open = Tournament::updateOrCreate(['slug' => 'neverland-open-04'], [
            'name' => 'Neverland Open #04', 'description' => 'Terbuka untuk seluruh member Neverland. Wajib hadir tepat waktu dan menjaga sportivitas.', 'game' => 'Total Football', 'starts_at' => '2025-10-18', 'ends_at' => '2025-10-20',
            'format' => 'knockout', 'max_teams' => 32, 'prize_pool' => 15000000, 'status' => 'open', 'bracket' => [
                'format' => 'knockout', 'generated_at' => now()->toISOString(), 'teams' => ['dimasprtm', 'nadiaa'], 'groups' => [],
                'rounds' => [['name' => 'final', 'matches' => [['home' => 'dimasprtm', 'away' => 'nadiaa', 'home_score' => null, 'away_score' => null, 'status' => 'pending']]]],
            ],
        ]);
        $open->participants()->sync($members->mapWithKeys(fn (User $member) => [$member->id => ['registered_at' => now()]])->all());
        $slots = $members->pluck('nickname')->all();
        $size = 1;
        while ($size < count($slots)) { $size *= 2; }
        $slots = array_pad($slots, $size, 'BYE');
        $rounds = [];
        while (count($slots) > 1) {
            $matches = collect(array_chunk($slots, 2))->map(fn (array $pair) => ['home' => $pair[0], 'away' => $pair[1], 'home_score' => null, 'away_score' => null, 'status' => 'pending'])->all();
            $rounds[] = ['name' => $size === 2 ? 'final' : ($size === 4 ? 'semifinals' : 'round_of_'.$size), 'matches' => $matches];
            $slots = array_fill(0, count($matches), 'TBD');
            $size = intdiv($size, 2);
        }
        $open->update(['bracket' => ['format' => 'knockout', 'generated_at' => now()->toISOString(), 'teams' => $members->pluck('nickname')->all(), 'groups' => [], 'rounds' => $rounds]]);

        Tournament::updateOrCreate(['slug' => 'neverland-league-fall-split'], [
            'name' => 'Neverland League: Fall Split', 'description' => 'Format liga untuk menguji konsistensi permainan dan kedalaman roster.', 'game' => 'Total Football', 'starts_at' => '2025-11-02', 'ends_at' => '2025-11-16',
            'format' => 'group_knockout', 'max_teams' => 16, 'prize_pool' => 25000000, 'status' => 'open', 'bracket' => ['format' => 'group_knockout', 'teams' => []],
        ]);

        Product::updateOrCreate(['slug' => 'neverland-match-jersey'], ['name' => 'Neverland Match Jersey', 'description' => 'Jersey matchday resmi Neverland.', 'price' => 249000, 'stock' => 25, 'variants' => [['name' => 'Ukuran', 'values' => ['S', 'M', 'L', 'XL']], ['name' => 'Nama punggung', 'values' => ['Tanpa nama', 'Custom']]], 'sort_order' => 1, 'is_active' => true]);
        Product::updateOrCreate(['slug' => 'neverland-training-kit'], ['name' => 'Neverland Training Kit', 'description' => 'Training kit untuk sesi latihan dan scrim.', 'price' => 179000, 'stock' => 40, 'variants' => [['name' => 'Ukuran', 'values' => ['S', 'M', 'L', 'XL']], ['name' => 'Warna', 'values' => ['Hitam', 'Putih']]], 'sort_order' => 2, 'is_active' => true]);
        Product::updateOrCreate(['slug' => 'neverland-cap'], ['name' => 'Neverland Core Cap', 'description' => 'Cap hitam minimal untuk matchday.', 'price' => 99000, 'stock' => 30, 'variants' => [['name' => 'Model', 'values' => ['Snapback', 'Dad cap']]], 'sort_order' => 3, 'is_active' => true]);

        AccountListing::updateOrCreate(['slug' => 'tf-elite-dimas'], [
            'name' => 'Total Football Elite · Dimas',
            'description' => 'Akun kompetitif dengan koleksi item dan progres siap lanjut.',
            'rank' => 'Elite Division',
            'price' => 450000,
            'owner_nickname' => 'dimasprtm',
            'status' => 'available',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        AccountListing::updateOrCreate(['slug' => 'tf-pro-nadia'], [
            'name' => 'Total Football Pro · Nadia',
            'description' => 'Akun siap main untuk pemain yang ingin langsung masuk matchday.',
            'rank' => 'Pro Division',
            'price' => 650000,
            'owner_nickname' => 'nadiaa',
            'status' => 'available',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        AccountListing::updateOrCreate(['slug' => 'tf-challenger-raka'], [
            'name' => 'Total Football Challenger · Raka',
            'description' => 'Progress stabil, cocok untuk melanjutkan push rank bersama squad.',
            'rank' => 'Challenger',
            'price' => 300000,
            'owner_nickname' => 'raka-w',
            'status' => 'available',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        unset($admin);
    }
}
