<?php

namespace App\Console\Commands;

use App\Services\TemplateGenerator;
use Illuminate\Console\Command;

class GenerateProductTemplates extends Command
{
    protected $signature = 'urun:sablon-olustur
        {--haric=* : Atlanacak üst kategori kimlikleri (örn. özel günler)}
        {--en-az=2 : Bir kategoride en az kaç ürün olsun}
        {--tazele : Aynı adlı şablon varsa güncelle}';

    protected $description = 'Mevcut kataloğdan ürün şablonu türetir';

    public function handle(TemplateGenerator $generator): int
    {
        $result = $generator->generate(
            excludeParentIds: array_map('intval', (array) $this->option('haric')),
            minProducts: (int) $this->option('en-az'),
            refresh: (bool) $this->option('tazele'),
        );

        foreach (['created' => 'Açıldı', 'updated' => 'Güncellendi', 'skipped' => 'Atlandı'] as $key => $label) {
            foreach ($result[$key] as $name) {
                $this->line("  {$label}: {$name}");
            }
        }

        $this->newLine();
        $this->info(count($result['created']).' şablon açıldı, '.count($result['updated']).' şablon güncellendi.');

        return self::SUCCESS;
    }
}
