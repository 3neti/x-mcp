<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('cannot import X-Change internals or address the legacy lifecycle API', function () {
    $source = collect(File::allFiles(dirname(__DIR__, 2).'/src'))
        ->map(fn (SplFileInfo $file): string => (string) File::get($file->getPathname()))
        ->implode("\n");

    expect($source)
        ->not->toContain('LBHurtado\\XChange\\Actions')
        ->not->toContain('LBHurtado\\XChange\\Models')
        ->not->toContain('LBHurtado\\XChange\\Treasury')
        ->not->toContain('LBHurtado\\Voucher')
        ->not->toContain('Illuminate\\Database')
        ->not->toContain('/api/x/v1');
});
