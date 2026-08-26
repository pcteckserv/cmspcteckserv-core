<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Services\Media\MediaOptimizer;

class OptimizeMediaCommand extends Command
{
    protected $signature = 'cms:media:optimize {--failed : Reotimizar apenas ficheiros com falha}';

    protected $description = 'Optimiza imagens existentes do Gestor de Media.';

    public function handle(MediaOptimizer $optimizer): int
    {
        $query = Media::query()->where('media_type', 'image');

        if ($this->option('failed')) {
            $query->where('optimization_status', Media::STATUS_FAILED);
        }

        $count = 0;
        $query->chunkById(50, function ($items) use ($optimizer, &$count): void {
            foreach ($items as $media) {
                $optimizer->optimize($media);
                $count++;
            }
        });

        $this->info("Foram processados {$count} ficheiros.");

        return self::SUCCESS;
    }
}
