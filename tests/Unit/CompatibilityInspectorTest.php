<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Pcteckserv\CmsCore\Support\CompatibilityInspector;
use PHPUnit\Framework\TestCase;

class CompatibilityInspectorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/cms-compatibility-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/vendor/pcteckserv/cms-core/src',0700,true);
        mkdir($this->root.'/vendor/composer',0700,true);
        mkdir($this->root.'/vendor/tests/plugin',0700,true);
        file_put_contents($this->root.'/.env', 'PRIVATE_SENTINEL_NOT_LOADED');
        file_put_contents($this->root.'/vendor/pcteckserv/cms-core/src/CmsCoreServiceProvider.php','<?php');
    }

    protected function tearDown(): void { (new Filesystem())->deleteDirectory($this->root); }

    private function fixture(string $core='3.0.1', string $constraint='^3.0', bool $snapshot=false): void
    {
        $packages = [['name'=>'pcteckserv/cms-core','version'=>$core,'dist'=>['type'=>$snapshot ? 'path':'zip']], ['name'=>'tests/plugin','version'=>'1.0.0','require'=>['pcteckserv/cms-core'=>$constraint]]];
        foreach (['composer.json'=>['name'=>'tests/site','require'=>['pcteckserv/cms-core'=>$core]], 'composer.lock'=>['packages'=>$packages], 'vendor/composer/installed.json'=>['packages'=>$packages]] as $file=>$data) file_put_contents($this->root.'/'.$file,json_encode($data));
    }

    public function test_same_major_upgrade_allows_changing_a_root_pin_without_loading_environment(): void
    {
        $this->fixture();
        $result = (new CompatibilityInspector())->inspect($this->root,'3.1.7');
        $this->assertTrue($result['compatible']);
        $this->assertTrue($result['automatic_update_allowed']);
        $this->assertTrue($result['requires_constraint_update']);
        $this->assertSame('PRIVATE_SENTINEL_NOT_LOADED',file_get_contents($this->root.'/.env'));
    }

    public function test_legacy_core_and_plugins_require_migration(): void
    {
        $this->fixture('2.9.0','^2.6.3');
        $result = (new CompatibilityInspector())->inspect($this->root,'3.1.7');
        $this->assertFalse($result['compatible']);
        $this->assertFalse($result['automatic_update_allowed']);
        $this->assertCount(2,$result['errors']);
    }

    public function test_deployed_code_constraint_is_checked_as_well_as_metadata(): void
    {
        $this->fixture();
        file_put_contents($this->root.'/vendor/tests/plugin/composer.json',json_encode(['require'=>['pcteckserv/cms-core'=>'^3.1']]));
        $result = (new CompatibilityInspector())->inspect($this->root,'3.0.3');
        $this->assertFalse($result['compatible']);
        $this->assertCount(1,$result['current_problems']);
        $this->assertStringContainsString('^3.1',$result['errors'][0]);
    }

    public function test_snapshot_cannot_be_automatically_relabelled(): void
    {
        $this->fixture(snapshot:true);
        $result = (new CompatibilityInspector())->inspect($this->root,'3.1.7');
        $this->assertTrue($result['compatible']);
        $this->assertFalse($result['automatic_update_allowed']);
    }

    public function test_missing_vendor_and_malformed_metadata_do_not_claim_verified_installation(): void
    {
        $this->fixture();
        file_put_contents($this->root.'/vendor/composer/installed.json','{"packages":false}');
        $result = (new CompatibilityInspector())->inspect($this->root,'3.1.7');
        $this->assertNull($result['installed_core']);
        $this->assertFalse($result['automatic_update_allowed']);
        $this->assertNotEmpty($result['warnings']);
    }
}
