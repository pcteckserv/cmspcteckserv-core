<?php

namespace Tests\Unit;

use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use PHPUnit\Framework\TestCase;

final class PluginCompatibilityTest extends TestCase
{
    public function test_declared_core_constraint_is_respected_and_invalid_metadata_does_not_break_the_catalogue(): void
    {
        foreach (['^3.0'=>true, '<3.0'=>false, 'not-a-constraint'=>false] as $constraint=>$expected) {
            $plugin=new AvailablePlugin('fixture','fixture','fixture/package','Teste',null,null,null,'fixture',coreConstraint:$constraint);
            $this->assertSame($expected,$plugin->compatible());
        }
    }
}
