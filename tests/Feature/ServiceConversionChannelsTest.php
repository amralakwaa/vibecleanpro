<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * Owner decision 2026-11-19: on a service page WhatsApp and a direct call
 * are the two primary actions, every WhatsApp link opens a chat that
 * already names the service, and the reviews section is never one lonely
 * quote while the company has other approved reviews.
 */
class ServiceConversionChannelsTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private function profile(): void
    {
        BusinessProfile::query()->create([
            'name' => 'Vibe Clean Pro',
            'city' => 'الرياض',
            'phone' => '0557100772',
            'whatsapp_number' => '966557100772',
        ]);
    }

    public function test_every_whatsapp_link_on_a_service_page_names_the_service(): void
    {
        $this->profile();
        $page = $this->createCompliantServicePage(slug: 'glass-facade-cleaning');
        $expected = 'https://wa.me/966557100772?text='.rawurlencode('مرحبًا، أرغب في عرض سعر لخدمة '.$page->pageable->name.' في الرياض. الحي: ');

        $html = $this->get('/services/glass-facade-cleaning')->assertOk()->getContent();

        preg_match_all('#href="(https://wa\.me/[^"]*)"#', $html, $links);
        $this->assertNotEmpty($links[1]);
        foreach ($links[1] as $link) {
            $this->assertSame($expected, html_entity_decode($link));
        }
    }

    public function test_whatsapp_and_call_lead_the_hero_and_the_mobile_bar(): void
    {
        $this->profile();
        $this->createCompliantServicePage(slug: 'glass-facade-cleaning');

        $html = $this->get('/services/glass-facade-cleaning')->assertOk()->getContent();

        $hero = substr($html, strpos($html, 'data-hero-cta'), 4000);
        $this->assertLessThan(strpos($hero, 'tel:0557100772'), strpos($hero, 'wa.me'));
        $this->assertLessThan(strpos($hero, 'أو اطلب عرض سعر مكتوب'), strpos($hero, 'tel:0557100772'));
        $this->assertStringNotContainsString('طلب خدمة', substr($html, strrpos($html, 'lg:hidden fixed inset-x-0 bottom-0')));
    }

    public function test_reviews_fill_up_to_three_with_the_companys_other_approved_reviews(): void
    {
        $this->profile();
        $page = $this->createCompliantServicePage(slug: 'glass-facade-cleaning');
        $other = Service::factory()->create(['name' => 'تنظيف منازل']);
        Testimonial::query()->create(['service_id' => $page->pageable_id, 'author_name' => 'عميل الواجهة', 'rating' => 5, 'content' => 'واجهة نظيفة', 'source' => 'google', 'consent_confirmed' => true, 'approved_at' => now()]);
        Testimonial::query()->create(['service_id' => $other->id, 'author_name' => 'عميل المنزل', 'rating' => 4, 'content' => 'منزل مرتب', 'source' => 'whatsapp', 'consent_confirmed' => true, 'approved_at' => now()]);
        Testimonial::query()->create(['service_id' => $other->id, 'author_name' => 'غير معتمد', 'rating' => 1, 'content' => 'مخفي', 'source' => 'google']);

        $response = $this->get('/services/glass-facade-cleaning')->assertOk();

        $response->assertSeeInOrder(['عميل الواجهة', 'عميل المنزل · تنظيف منازل · رسالة واتساب من العميل']);
        $response->assertDontSee('غير معتمد');
        $response->assertSee('4.5</span> من 5، متوسط <span class="tabular-nums">2</span> آراء', false);
    }
}
