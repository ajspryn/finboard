<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NormalizeKotaNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-kota-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize inconsistent kota names in pembiayaans table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting kota name normalization...');

        // Mapping untuk normalisasi nama kota
        $normalizationMap = [
            // Bogor variations
            'KABUPATEN BOGOR' => 'BOGOR',
            'KAB. BOGOR' => 'BOGOR',
            'KAB BOGOR' => 'BOGOR',
            'KOTA BOGOR' => 'BOGOR',

            // Jakarta variations
            'JAKARTA TIMUR' => 'JAKARTA TIMUR',
            'KOTA JAKARTA TIMUR' => 'JAKARTA TIMUR',
            'KOTA ADM JAKARTA SELATAN' => 'JAKARTA SELATAN',
            'KOTA ADM JAKARTA UTARA' => 'JAKARTA UTARA',

            // Depok variations
            'KOTA DEPOK' => 'DEPOK',

            // Bekasi variations
            'KOTA BEKASI' => 'BEKASI',

            // Tangerang variations
            'KOTA TANGERANG SELATAN' => 'TANGERANG SELATAN',
            'TANGERANG SELATAN' => 'TANGERANG SELATAN',

            // Bandung variations
            'KOTA BANDUNG' => 'BANDUNG',

            // Yogyakarta variations
            'KOTA YOGYAKARTA' => 'YOGYAKARTA',
            'YOGYAKARTA' => 'YOGYAKARTA',

            // Sleman variations
            'KAB SLEMAN' => 'SLEMAN',
            'SLEMAN' => 'SLEMAN',

            // Magelang variations
            'KAB MAGELANG' => 'MAGELANG',
            'KOTA MAGELANG' => 'MAGELANG',

            // Mojokerto variations
            'KAB MOJOKERTO' => 'MOJOKERTO',
            'KOTA MOJOKERTO' => 'MOJOKERTO',

            // Kediri variations
            'KAB KEDIRI' => 'KEDIRI',
            'KOTA KEDIRI' => 'KEDIRI',

            // Ponorogo variations
            'KAB PONOROGO' => 'PONOROGO',

            // Bantul variations
            'KAB BANTUL' => 'BANTUL',

            // Gunung Kidul variations
            'KAB GUNUNG KIDUL' => 'GUNUNG KIDUL',

            // Kulon Progo variations
            'KAB KULON PROGO' => 'KULON PROGO',

            // Purworejo variations
            'KAB PURWOREJO' => 'PURWOREJO',

            // Grobogan variations
            'KAB GROBOGAN' => 'GROBOGAN',

            // Klaten variations
            'KAB KLATEN' => 'KLATEN',

            // Sragen variations
            'KAB SRAGEN' => 'SRAGEN',

            // Wonogiri variations
            'KAB WONOGIRI' => 'WONOGIRI',

            // Nganjuk variations
            'KAB NGANJUK' => 'NGANJUK',

            // Cirebon variations
            'CIREBON' => 'CIREBON',
            'KAB CIREBON' => 'CIREBON',

            // Cianjur variations
            'CIANJUR' => 'CIANJUR',
            'KAB CIANJUR' => 'CIANJUR',

            // Lebak variations
            'LEBAK' => 'LEBAK',
            'KAB LEBAK' => 'LEBAK',

            // Buleleng variations
            'KAB BULELENG' => 'BULELENG',

            // Denpasar variations
            'KOTA DENPASAR' => 'DENPASAR',

            // Additional KAB variations that need normalization
            'KAB BADUNG' => 'BADUNG',
            'KAB BANDUNG' => 'BANDUNG',
            'KAB BANGKALAN' => 'BANGKALAN',
            'KAB BANGLI' => 'BANGLI',
            'KAB BANYUWANGI' => 'BANYUWANGI',
            'KAB BLITAR' => 'BLITAR',
            'KAB BLORA' => 'BLORA',
            'KAB BONDOWOSO' => 'BONDOWOSO',
            'KAB BOYOLALI' => 'BOYOLALI',
            'KAB BREBES' => 'BREBES',
            'KAB GARUT' => 'GARUT',
            'KAB GIANYAR' => 'GIANYAR',
            'KAB GRESIK' => 'GRESIK',
            'KAB JEMBER' => 'JEMBER',
            'KAB JEMBRANA' => 'JEMBRANA',
            'KAB JOMBANG' => 'JOMBANG',
            'KAB KARANGANYAR' => 'KARANGANYAR',
            'KAB KARANGASEM' => 'KARANGASEM',
            'KAB KEBUMEN' => 'KEBUMEN',
            'KAB KENDAL' => 'KENDAL',
            'KAB KLUNGKUNG' => 'KLUNGKUNG',
            'KAB LAMONGAN' => 'LAMONGAN',
            'KAB LOMBOK BARAT' => 'LOMBOK BARAT',
            'KAB LOMBOK TIMUR' => 'LOMBOK TIMUR',
            'KAB LUMAJANG' => 'LUMAJANG',
            'KAB MADIUN' => 'MADIUN',
            'KAB MALANG' => 'MALANG',
            'KAB MANGGARAI TIMUR' => 'MANGGARAI TIMUR',
            'KAB NGAWI' => 'NGAWI',
            'KAB PACITAN' => 'PACITAN',
            'KAB PASURUAN' => 'PASURUAN',
            'KAB PEMALANG' => 'PEMALANG',
            'KAB PINRANG' => 'PINRANG',
            'KAB PROBOLINGGO' => 'PROBOLINGGO',
            'KAB PURBALINGGA' => 'PURBALINGGA',
            'KAB SAMPANG' => 'SAMPANG',
            'KAB SEMARANG' => 'SEMARANG',
            'KAB SIDOARJO' => 'SIDOARJO',
            'KAB SITUBONDO' => 'SITUBONDO',
            'KAB SUMBA BARAT DAYA' => 'SUMBA BARAT DAYA',
            'KAB SUMBAWA' => 'SUMBAWA',
            'KAB SUMENEP' => 'SUMENEP',
            'KAB TABANAN' => 'TABANAN',
            'KAB TANGERANG' => 'TANGERANG',
            'KAB TEGAL' => 'TEGAL',
            'KAB TEMANGGUNG' => 'TEMANGGUNG',
            'KAB TRENGGALEK' => 'TRENGGALEK',
            'KAB TUBAN' => 'TUBAN',
            'KAB TULANG BAWANG BARAT' => 'TULANG BAWANG BARAT',
            'KAB TULUNGAGUNG' => 'TULUNGAGUNG',
            'KAB WONOSOBO' => 'WONOSOBO',
        ];

        $totalUpdated = 0;

        foreach ($normalizationMap as $oldName => $newName) {
            $count = \DB::table('pembiayaans')
                ->where('kota', $oldName)
                ->update(['kota' => $newName]);

            if ($count > 0) {
                $this->info("Updated {$count} records: '{$oldName}' -> '{$newName}'");
                $totalUpdated += $count;
            }
        }

        $this->info("Normalization completed. Total records updated: {$totalUpdated}");

        // Show summary of remaining duplicates
        $this->showRemainingDuplicates();
    }

    private function showRemainingDuplicates()
    {
        $this->info("\nChecking for remaining duplicates...");

        $duplicates = \DB::table('pembiayaans')
            ->select('kecamatan', 'kota', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->groupBy('kecamatan', 'kota')
            ->havingRaw('COUNT(*) > 0')
            ->orderBy('kecamatan')
            ->get();

        $grouped = $duplicates->groupBy('kecamatan')->filter(function ($group) {
            return $group->count() > 1;
        });

        if ($grouped->isEmpty()) {
            $this->info('No more duplicates found!');
        } else {
            $this->warn('Remaining duplicates:');
            foreach ($grouped as $kecamatan => $cities) {
                $this->line("Kecamatan: {$kecamatan}");
                foreach ($cities as $city) {
                    $this->line("  - Kota: '{$city->kota}' ({$city->count} records)");
                }
            }
        }
    }
}
