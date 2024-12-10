<?php

declare(strict_types=1);

use PhpCsFixer\Finder;
use Yard\PhpCsFixerRules\Config;

# PHP CS Fixer can be run by using the composer script `composer format`

$finder = Finder::create()
	->in(__DIR__)
	->name('*.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true)
	->append(['.php-cs-fixer.php']);

return Config::create($finder);
