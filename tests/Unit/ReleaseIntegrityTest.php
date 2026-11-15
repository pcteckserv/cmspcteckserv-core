<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Pcteckserv\CmsCore\Support\ReleaseIntegrity;
use PHPUnit\Framework\TestCase;

class ReleaseIntegrityTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/cms-integrity-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/src', 0700, true);
        file_put_contents($this->root.'/composer.json', "{}\n");
        file_put_contents($this->root.'/src/CmsCoreServiceProvider.php', "<?php\n// fixture\n");
        $this->manifest();
    }

    protected function tearDown(): void { (new Filesystem())->deleteDirectory($this->root); }

    private function manifest(array $changes = []): void
    {
        $files = [];
        foreach (['composer.json','src/CmsCoreServiceProvider.php'] as $file) $files[$file] = ReleaseIntegrity::hash($this->root.'/'.$file);
        file_put_contents($this->root.'/core-release.json', json_encode(array_replace(['schema'=>1,'version'=>'3.1.7','files'=>$files], $changes)));
    }

    public function test_complete_release_and_windows_line_endings_are_accepted(): void
    {
        file_put_contents($this->root.'/src/CmsCoreServiceProvider.php', "<?php\r\n// fixture\r\n");
        $this->assertTrue((new ReleaseIntegrity())->verify($this->root, 'v3.1.7')['valid']);
    }

    public function test_missing_provider_is_rejected(): void
    {
        unlink($this->root.'/src/CmsCoreServiceProvider.php');
        $this->assertFalse((new ReleaseIntegrity())->verify($this->root)['valid']);
    }

    public function test_changed_file_and_wrong_release_are_rejected(): void
    {
        file_put_contents($this->root.'/composer.json', '{"changed":true}');
        $result = (new ReleaseIntegrity())->verify($this->root, '3.1.8');
        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']);
    }

    public function test_traversal_and_non_string_version_fail_without_exception(): void
    {
        $this->manifest(['files'=>['../outside.php'=>str_repeat('a',64)]]);
        $this->assertFalse((new ReleaseIntegrity())->verify($this->root)['valid']);
        $this->manifest(['version'=>['invalid']]);
        $this->assertFalse((new ReleaseIntegrity())->verify($this->root)['valid']);
    }

    public function test_binary_hash_is_exact_and_manifest_is_required(): void
    {
        file_put_contents($this->root.'/image.png', "\0\r\n\xff");
        $this->assertSame(hash_file('sha256',$this->root.'/image.png'), ReleaseIntegrity::hash($this->root.'/image.png'));
        unlink($this->root.'/core-release.json');
        $this->assertFalse((new ReleaseIntegrity())->verify($this->root)['valid']);
    }

    public function test_python_hash_is_canonical_on_windows_and_linux(): void
    {
        file_put_contents($this->root.'/catalog.py',"print('catalog')\r\n");
        $this->assertSame(hash('sha256',"print('catalog')\n"),ReleaseIntegrity::hash($this->root.'/catalog.py'));
        $manifest=json_decode(file_get_contents($this->root.'/core-release.json'),true);
        $manifest['version']='3.1.9';$manifest['files']['catalog.py']=ReleaseIntegrity::hash($this->root.'/catalog.py');
        file_put_contents($this->root.'/core-release.json',json_encode($manifest));
        file_put_contents($this->root.'/catalog.py',"print('catalog')\n");
        $this->assertTrue((new ReleaseIntegrity())->verify($this->root,'3.1.9')['valid']);
    }

    public function test_historical_python_crlf_hash_accepts_only_equal_content(): void
    {
        foreach (['2.9.1','3.1.7','3.1.8'] as $version) {
            $this->manifest(['version'=>$version]);
            $manifest=json_decode(file_get_contents($this->root.'/core-release.json'),true);
            $manifest['files']['catalog.py']=hash('sha256',"print('catalog')\r\n");
            file_put_contents($this->root.'/core-release.json',json_encode($manifest));
            file_put_contents($this->root.'/catalog.py',"print('catalog')\n");
            $this->assertTrue((new ReleaseIntegrity())->verify($this->root,$version)['valid']);
            file_put_contents($this->root.'/catalog.py',"print('changed')\n");
            $this->assertFalse((new ReleaseIntegrity())->verify($this->root,$version)['valid']);
        }
    }

    public function test_new_manifests_do_not_accept_old_raw_python_hashes(): void
    {
        $manifest=json_decode(file_get_contents($this->root.'/core-release.json'),true);
        $manifest['version']='3.1.9';$manifest['files']['catalog.py']=hash('sha256',"print('catalog')\r\n");
        file_put_contents($this->root.'/core-release.json',json_encode($manifest));
        file_put_contents($this->root.'/catalog.py',"print('catalog')\n");
        $this->assertFalse((new ReleaseIntegrity())->verify($this->root,'3.1.9')['valid']);
    }
}
