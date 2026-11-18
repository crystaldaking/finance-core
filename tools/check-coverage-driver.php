<?php

declare(strict_types=1);

if (extension_loaded('pcov') && (bool) ini_get('pcov.enabled')) {
    exit(0);
}

if (extension_loaded('xdebug') && hasCoverageMode(xdebug_info('mode'))) {
    exit(0);
}

fwrite(STDERR, "Enable PCOV or Xdebug coverage for the PHP CLI before running coverage or mutation tests.\n");
exit(1);

function hasCoverageMode(mixed $modes): bool
{
    return is_array($modes) && in_array('coverage', $modes, true);
}
