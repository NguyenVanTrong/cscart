<?php


/**
 * Class ArchiveTest
 */
class ArchiveTest extends PHPUnit_Framework_TestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function setUp()
    {
        $this->removeRuntimeFiles();

        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->removeRuntimeFiles();
    }

    public function getWorkDir()
    {
        return realpath(__DIR__ . '/runtime');
    }

    /**
     * @dataProvider zipTestProvider
     */
    public function testZip($archive, $expected_files)
    {
        $dir = $this->getWorkDir() . '/zip';
        $new_archive = $this->getWorkDir() . '/archive.zip';

        mkdir($dir);

        //Test read archive
        $zip = new \Tygh\Tools\Archivers\ZipArchiveReader($archive);

        $this->assertTrue($zip->extractTo($dir));

        $files = $this->getDirFiles($dir);

        $this->assertEquals($expected_files, $files);
        $this->assertEquals($expected_files, $zip->getFiles());

        //Test create archive
        $zip = new \Tygh\Tools\Archivers\ZipArchiveCreator($new_archive);
        $zip->addDir($dir);
        $zip->close();

        $this->assertFileExists($new_archive);

        $this->removeDir($dir);

        //Check created archive
        $zip = new \Tygh\Tools\Archivers\ZipArchiveReader($new_archive);

        $this->assertTrue($zip->extractTo($dir));

        $files = $this->getDirFiles($dir);

        $this->assertEquals($expected_files, $files);
        $this->assertEquals($expected_files, $zip->getFiles());
    }

    public function zipTestProvider()
    {
        return array(
            array(
                __DIR__ . '/data/archive.zip',
                $this->getExpectedFiles('archive.zip')
            )
        );
    }

    /**
     * @dataProvider pharTestProvider
     */
    public function testPhar($archive, $expected_files)
    {
        $dir = $this->getWorkDir() . '/phar';
        $new_archive = $this->getWorkDir() . '/archive.zip';
        $new_archive_tgz = $this->getWorkDir() . '/archive1.v2.4.5.tgz';
        $new_archive_gz = $this->getWorkDir() . '/archive2.v2.4.5.gz';

        mkdir($dir);

        //Test read archive
        $phar = new \Tygh\Tools\Archivers\PharArchiveReader($archive);

        $this->assertTrue($phar->extractTo($dir));

        $files = $this->getDirFiles($dir);

        $this->assertEquals($expected_files, $files);
        $this->assertEquals($expected_files, $phar->getFiles());

        //Test create archive
        $phar = new \Tygh\Tools\Archivers\PharArchiveCreator($new_archive);
        $phar->addDir($dir);
        $phar->close();

        $this->assertFileExists($new_archive);

        $phar = new \Tygh\Tools\Archivers\PharArchiveReader($new_archive);
        $files = $this->getDirFiles($dir);
        $this->assertEquals($files, $phar->getFiles());

        //Test create archive tgz
        $phar = new \Tygh\Tools\Archivers\PharArchiveCreator($new_archive_tgz);
        $phar->addDir($dir);
        $phar->close();

        $this->assertFileExists($new_archive_tgz);

        $phar = new \Tygh\Tools\Archivers\PharArchiveReader($new_archive_tgz);
        $files = $this->getDirFiles($dir);
        $this->assertEquals($files, $phar->getFiles());

        //Test create archive gz
        $phar = new \Tygh\Tools\Archivers\PharArchiveCreator($new_archive_gz);
        $phar->addDir($dir);
        $phar->close();

        $this->assertFileExists($new_archive_gz);

        $phar = new \Tygh\Tools\Archivers\PharArchiveReader($new_archive_gz);
        $files = $this->getDirFiles($dir);
        $this->assertEquals($files, $phar->getFiles());

        $this->removeDir($dir);

        //Check created archive
        $phar = new \Tygh\Tools\Archivers\PharArchiveReader($new_archive);

        $this->assertTrue($phar->extractTo($dir));

        $files = $this->getDirFiles($dir);

        $this->assertEquals($expected_files, $files);
        $this->assertEquals($expected_files, $phar->getFiles());
    }

    public function pharTestProvider()
    {
        return array(
            array(
                __DIR__ . '/data/archive.zip',
                $this->getExpectedFiles('archive.zip')
            )
        );
    }

    /**
     * @dataProvider pearTestProvider
     */
    public function testPear($archive, $expected_files)
    {
        $dir = $this->getWorkDir() . '/pear';
        mkdir($dir);

        $phar = new \Tygh\Tools\Archivers\PearArchiveReader($archive);

        $this->assertTrue($phar->extractTo($dir));

        $files = $this->getDirFiles($dir);

        $this->assertEquals($expected_files, $files);
        $this->assertEquals($expected_files, $phar->getFiles());
    }

    public function pearTestProvider()
    {
        return array(
            array(
                __DIR__ . '/data/archive.tar.gz',
                $this->getExpectedFiles('archive.tar.gz')
            ),
            array(
                __DIR__ . '/data/broken_path_archive.tgz',
                $this->getExpectedFiles('broken_path_archive.tgz')
            )
        );
    }

    /**
     * @dataProvider archiverTestProvider
     */
    public function testArchiver($archive, $expected_files)
    {
        $archiver = new \Tygh\Tools\Archiver();

        $dir = $this->getWorkDir() . '/archiver';
        $new_archive = $this->getWorkDir() . '/archive.zip';

        mkdir($dir);

        //Test read archive
        $this->assertTrue($archiver->extractTo($archive, $dir));

        $files = $this->getDirFiles($dir);

        $this->assertEquals($expected_files, $files);

        //Test create archive
        $this->assertTrue($archiver->compress($new_archive, array($dir)));
        $this->assertFileExists($new_archive);
    }

    public function archiverTestProvider()
    {
        return array(
            array(
                __DIR__ . '/data/archive.zip',
                $this->getExpectedFiles('archive.zip')
            ),
            array(
                __DIR__ . '/data/archive.tar.gz',
                $this->getExpectedFiles('archive.tar.gz')
            ),
            array(
                __DIR__ . '/data/broken_path_archive.tgz',
                $this->getExpectedFiles('broken_path_archive.tgz')
            )
        );
    }

    protected function getExpectedFiles($archive)
    {
        if ($archive === 'broken_path_archive.tgz') {
            $files = array(
                'app/',
                'app/.htaccess',
                'app/index.php',
                'app/test.php',
                'app/test1.php',
                '.htaccess',
                'admin.php',
                'config.php',
                'init.php',
                'robots.txt',
                'test.sql',
                'test2.sql',
            );
        } else {
            $files = array(
                'Tygh/',
                'Tygh/Api/',
                'Tygh/Api/ApiTest.php',
                'Tygh/Api/CurlApiTest.php',
                'Tygh/Api/CurlWrapperApiTest.php',
                'Tygh/Api/Entities/',
                'Tygh/Api/Entities/AuthTest.php',
                'Tygh/Api/Entities/BlocksTest.php',
                'Tygh/Api/Entities/FeaturesTest.php',
                'Tygh/Api/Entities/LangVarsTest.php',
                'Tygh/Api/Entities/LanguagesTest.php',
                'Tygh/Api/Entities/OrdersTest.php',
                'Tygh/Api/Entities/TaxesTest.php',
                'Tygh/Api/Entities/UsergroupsTest.php',
                'Tygh/Api/Entities/UsersTest.php',
                'Tygh/Api/Formats/',
                'Tygh/Api/Formats/JsonTest.php',
                'Tygh/Api/Formats/TextTest.php',
                'Tygh/Core/',
                'Tygh/Core/DataKeeperTest.php',
                'Tygh/Core/ImageHelperTest.php',
                'Tygh/Core/LessTest.php',
                'Tygh/Core/RegistryTest.php',
                'Tygh/Languages/',
                'Tygh/Languages/LanguagesTest.php',
                'Tygh/Languages/ValuesTest.php',
                'Tygh/Shippings/',
                'Tygh/Shippings/RealtimeServicesTest.php',
                'Tygh/Shippings/ShippingsTest.php',
                'Tygh/Tools/',
                'Tygh/Tools/SecurityHelperTest.php',
                'Tygh/Tools/UrlTest.php',
                'Tygh/.htaccess',
                'Tygh/TyghSuite.php',
            );
        }

        sort($files);
        return $files;
    }

    protected function getDirFiles($dir)
    {
        $files = array();
        /**
         * @var \RecursiveDirectoryIterator|\RecursiveIteratorIterator|\SplFileInfo $iterator
         */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir,
                \FilesystemIterator::SKIP_DOTS |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::KEY_AS_PATHNAME
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $key => $item) {
            $path = trim($iterator->getSubPathname(), '\\/');

            /** @var SplFileInfo $item */
            if ($item->isDir()) {
                $path .= DIRECTORY_SEPARATOR;
            }

            $files[] = $path;
        }

        sort($files);
        return $files;
    }

    protected function removeDir($dir)
    {
        if (file_exists($dir)) {
            $files = $this->getDirFiles($dir);
            $dirs = array($dir);

            foreach ($files as $file) {
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    $dirs[] = $file;
                } else {
                    unlink($file);
                }
            }
            usort($dirs, function ($a, $b) {
                return  strlen($a) < strlen($b);
            });

            foreach ($dirs as $dir) {
                rmdir($dir);
            }
        }
    }

    protected function removeRuntimeFiles()
    {
        $this->removeDir($this->getWorkDir() . '/zip');
        $this->removeDir($this->getWorkDir() . '/phar');
        $this->removeDir($this->getWorkDir() . '/pear');
        $this->removeDir($this->getWorkDir() . '/archiver');
        @unlink($this->getWorkDir() . '/archive.zip');
        @unlink($this->getWorkDir() . '/archive.gz');
        @unlink($this->getWorkDir() . '/archive.tgz');
        @unlink($this->getWorkDir() . '/archive1.v2.4.5.tgz');
        @unlink($this->getWorkDir() . '/archive2.v2.4.5.gz');
    }
}