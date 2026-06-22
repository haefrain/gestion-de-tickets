<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withSkip([
        // Bootstrap generado por el recipe de PHPUnit (código de framework).
        __DIR__.'/tests/bootstrap.php',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true, codeQuality: true);
// El estilo de imports lo gobierna PHP-CS-Fixer (clases globales totalmente cualificadas, @Symfony),
// por eso Rector no importa nombres aquí. Los sets de Symfony (rector/rector-symfony) llegan en F6.
