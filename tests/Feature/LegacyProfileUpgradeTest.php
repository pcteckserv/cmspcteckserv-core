<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LegacyProfileUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_upgrade_preserves_profiles_and_languages_already_created_by_agenda(): void
    {
        DB::table('cms_user_profiles')->insert(['user_id'=>987,'biography'=>'Biografia a preservar','avatar_path'=>'profiles/test.jpg','languages'=>'["pt","en"]','created_at'=>now(),'updated_at'=>now()]);
        $root = dirname(__DIR__,2).'/database/migrations/';
        (require $root.'2026_10_24_190000_create_user_profiles.php')->up();
        (require $root.'2026_10_27_090000_add_staff_profile_languages.php')->up();
        $this->assertDatabaseHas('cms_user_profiles',['user_id'=>987,'biography'=>'Biografia a preservar','avatar_path'=>'profiles/test.jpg']);
        $this->assertSame(['pt','en'], json_decode(DB::table('cms_user_profiles')->where('user_id',987)->value('languages'), true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame(1, DB::table('cms_user_profiles')->count());
    }
}
