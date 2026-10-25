<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\GymShop;
use App\Models\PosSale;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AnalyticsSettingsBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * تاقیکردنەوەی باربوونی داشبۆردی شیکاری کڕین و فرۆشتن
     */
    public function test_sales_analytics_dashboard_loads(): void
    {
        $response = $this->get(route('analytics.sales'));

        $response->assertStatus(200)
            ->assertSee('شیکاری تەواوی کڕین و فرۆشتن')
            ->assertSee('تێچووی کڕین (سەرمایە)')
            ->assertSee('پڕفرۆشترین کاڵاکان');
    }

    /**
     * تاقیکردنەوەی هەناردەکردنی ڕاپۆرتی فرۆشتن بۆ CSV
     */
    public function test_sales_analytics_csv_export(): void
    {
        $response = $this->get(route('analytics.sales.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    /**
     * تاقیکردنەوەی ڕێکخستنی سیستەم و هۆڵ و فۆنت
     */
    public function test_settings_page_and_update(): void
    {
        $gym = Gym::first();

        $response = $this->get(route('settings.index'));
        $response->assertStatus(200)
            ->assertSee('ڕێکخستنی سیستەم، هۆڵ، فۆنت و دیزاین');

        $updateResponse = $this->post(route('settings.update'), [
            'name' => 'GymPro Sulaymaniyah Elite',
            'slogan' => 'هۆڵی پلە یەک',
            'phone' => '0770 123 4567',
            'email' => 'elite@gympro.test',
            'address' => 'سلێمانی - شەقامی بازنەیی مەلیک مەحمود',
            'font_family' => 'Noto Kufi Arabic',
            'font_size' => 'large',
            'theme' => 'light',
            'language' => 'ar',
        ]);

        $updateResponse->assertRedirect();

        $gym->refresh();
        $this->assertEquals('GymPro Sulaymaniyah Elite', $gym->name);
        $this->assertEquals('Noto Kufi Arabic', $gym->settings['font_family']);
        $this->assertEquals('large', $gym->settings['font_size']);
        $this->assertEquals('light', $gym->settings['theme']);
        $this->assertEquals('ar', $gym->settings['language']);
    }

    /**
     * تاقیکردنەوەی گۆڕینی خێرای مۆدی شەو و ڕۆژ
     */
    public function test_theme_toggle(): void
    {
        $response = $this->postJson(route('settings.theme.toggle'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * تاقیکردنەوەی دروستکردنی باک ئەپی داتابەیس و JSON
     */
    public function test_backup_management(): void
    {
        $response = $this->get(route('backups.index'));
        $response->assertStatus(200)
            ->assertSee('باک ئەپی تەواوەتی داتابەیس و داتای سیستەم');

        // Create DB backup
        $createDbResponse = $this->post(route('backups.create'), ['type' => 'db']);
        $createDbResponse->assertRedirect();

        // Create JSON backup
        $createJsonResponse = $this->post(route('backups.create'), ['type' => 'json']);
        $createJsonResponse->assertRedirect();

        $files = File::files(storage_path('app/backups'));
        $this->assertGreaterThanOrEqual(1, count($files));

        // Test download of the first backup file
        $firstFile = $files[0];
        $downloadResponse = $this->get(route('backups.download', ['filename' => $firstFile->getFilename()]));
        $downloadResponse->assertStatus(200);

        // Cleanup created test backup files
        foreach ($files as $f) {
            File::delete($f->getPathname());
        }
    }
}
