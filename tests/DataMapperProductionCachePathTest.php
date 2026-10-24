<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DataMapperProductionCachePathTest extends TestCase
{
    public function testMissingAbsoluteFolderIsNotPrefixedWithAppPath(): void
    {
        $folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'datamapper_missing_' . uniqid();
        $this->assertDirectoryDoesNotExist($folder);

        $this->assertSame($folder, \DataMapper::production_cache_folder($folder));
    }

    public function testAbsoluteUnixPathIsUsedAsIs(): void
    {
        $this->assertSame(
            '/var/www/app/releases/1/application/cache/datamapper',
            \DataMapper::production_cache_folder('/var/www/app/releases/1/application/cache/datamapper')
        );
    }

    public function testAbsoluteWindowsPathsAreUsedAsIs(): void
    {
        $this->assertSame('C:\\app\\cache\\datamapper', \DataMapper::production_cache_folder('C:\\app\\cache\\datamapper'));
        $this->assertSame('C:/app/cache/datamapper', \DataMapper::production_cache_folder('C:/app/cache/datamapper'));
        $this->assertSame('\\\\server\\share\\cache', \DataMapper::production_cache_folder('\\\\server\\share\\cache'));
    }

    public function testRelativePathIsRelativeToAppPath(): void
    {
        $this->assertSame(APPPATH . 'datamapper/cache', \DataMapper::production_cache_folder('datamapper/cache'));
    }
}
