<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Filament\Pages\ManageBusinessProfile;
use App\Listeners\SendNewLeadNotification;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Tests\Feature\Seo\Concerns\BuildsSeoFixtures;
use Tests\TestCase;

/**
 * A new lead is stored first and announced second. These tests pin the
 * announcement: one internal email per lead to the managed notification
 * address, with the real lead facts, the right subject for the form it
 * came from - and never to the visitor. And they pin the other half: no
 * recipient, a dead mail server or a failing queue leave the lead
 * exactly where it was, with the visitor seeing success.
 */
class LeadNotificationTest extends TestCase
{
    use BuildsSeoFixtures, RefreshDatabase;

    private const RECIPIENT = 'leads@example.test';

    public function test_a_contact_lead_is_saved_and_the_team_is_notified_at_the_configured_address(): void
    {
        Notification::fake();
        $this->profile();

        $this->post('/contact', ['name' => 'سلمى', 'phone' => '0500000000', 'email' => 'salma@example.test', 'message' => 'سؤال'])
            ->assertRedirect('/contact');

        $lead = Lead::query()->sole();
        $this->assertSame('contact_form', $lead->source);
        Notification::assertSentOnDemand(NewLeadNotification::class, function (NewLeadNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($lead) {
            return $notifiable->routes['mail'] === self::RECIPIENT && $channels === ['mail'] && $notification->lead->is($lead);
        });
        Notification::assertCount(1);
    }

    public function test_a_quote_lead_is_saved_and_the_team_is_notified_at_the_configured_address(): void
    {
        Notification::fake();
        $this->profile();

        $this->post('/quote', ['name' => 'نورة', 'phone' => '0500000000'])->assertRedirect('/quote');

        $lead = Lead::query()->sole();
        $this->assertSame('quote_form', $lead->source);
        Notification::assertSentOnDemand(NewLeadNotification::class, fn (NewLeadNotification $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === self::RECIPIENT && $notification->lead->is($lead));
        Notification::assertCount(1);
    }

    public function test_the_subject_names_the_form_the_lead_came_from_without_urgency(): void
    {
        $this->profile();

        $this->assertSame('طلب عرض سعر جديد — Vibe Clean Pro', (new NewLeadNotification(Lead::factory()->create(['source' => 'quote_form'])))->subject());
        $this->assertSame('رسالة تواصل جديدة — Vibe Clean Pro', (new NewLeadNotification(Lead::factory()->create(['source' => 'contact_form'])))->subject());
        $this->assertSame('رسالة تواصل جديدة من منشأة — Vibe Clean Pro', (new NewLeadNotification(Lead::factory()->create(['source' => 'contact_form_business'])))->subject());
    }

    public function test_the_email_carries_the_lead_facts_service_area_attribution_and_a_panel_link(): void
    {
        $this->profile();
        $service = $this->createCompliantServicePage(slug: 'mail-svc')->pageable;
        $service->update(['name' => 'خدمة-في-البريد']);
        $area = $this->createCompliantAreaPage(slug: 'mail-area')->pageable;
        $area->update(['name' => 'حي-في-البريد']);
        $lead = Lead::factory()->create([
            'source' => 'quote_form', 'name' => 'عميل-البريد', 'phone' => '0511111111', 'email' => 'lead@example.test',
            'service_id' => $service->id, 'area_id' => $area->id, 'message' => 'شقة من ثلاث غرف.', 'landing_page' => '/services/mail-svc',
            'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'spring', 'utm_term' => null, 'utm_content' => null,
            'ip_address' => '203.0.113.9', 'user_agent' => 'UA-Test/1.0',
        ]);

        $mail = (new NewLeadNotification($lead))->toMail(new AnonymousNotifiable);
        $html = (string) $mail->render();

        foreach (['طلب عرض سعر جديد', 'عميل-البريد', '0511111111', 'lead@example.test', 'خدمة-في-البريد', 'حي-في-البريد', 'شقة من ثلاث غرف.', '/services/mail-svc', 'نموذج طلب عرض السعر', 'utm_source: google', 'utm_campaign: spring', 'عرض الطلب في لوحة الإدارة', $lead->created_at->format('Y-m-d H:i')] as $fact) {
            $this->assertStringContainsString($fact, $html);
        }
        $this->assertStringNotContainsString('utm_term', $html, 'empty attribution keys are not listed');
        $this->assertStringContainsString('/admin/leads/'.$lead->id.'/edit', $html, 'the panel link is generated, never typed');
        $this->assertStringNotContainsString('example.com', $html);
        $this->assertLessThan(mb_strpos($html, '203.0.113.9'), mb_strpos($html, 'عرض الطلب في لوحة الإدارة'), 'IP and user agent sit below the action, as technical detail');
        $this->assertSame('طلب عرض سعر جديد — Vibe Clean Pro', $mail->subject);
    }

    public function test_missing_optional_data_renders_cleanly(): void
    {
        $this->profile();
        $lead = Lead::factory()->create([
            'source' => 'contact_form', 'name' => 'بلا-تفاصيل', 'phone' => '0522222222', 'email' => null, 'service_id' => null, 'area_id' => null,
            'message' => null, 'landing_page' => null, 'utm_source' => null, 'utm_medium' => null, 'utm_campaign' => null, 'utm_term' => null, 'utm_content' => null,
            'ip_address' => null, 'user_agent' => null,
        ]);

        $html = (string) (new NewLeadNotification($lead))->toMail(new AnonymousNotifiable)->render();

        $this->assertStringContainsString('بلا-تفاصيل', $html);
        foreach (['البريد الإلكتروني', 'الخدمة', 'المنطقة', 'الرسالة', 'UTM', 'IP:', 'صفحة الدخول'] as $absent) {
            $this->assertStringNotContainsString($absent, $html, $absent.' is not shown as an empty row');
        }
    }

    public function test_without_a_configured_recipient_the_lead_is_still_saved_and_nothing_is_sent(): void
    {
        Notification::fake();
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'email' => 'public@example.test']);

        $this->post('/quote', ['name' => 'نورة', 'phone' => '0500000000'])->assertRedirect('/quote')->assertSessionHas('lead_submitted', true);

        $this->assertSame(1, Lead::query()->count());
        Notification::assertNothingSent();
    }

    public function test_the_public_contact_email_is_never_used_as_the_notification_inbox(): void
    {
        Notification::fake();
        BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'email' => 'public@example.test', 'lead_notification_email' => self::RECIPIENT]);

        $this->post('/contact', ['name' => 'سلمى', 'phone' => '0500000000', 'message' => 'سؤال'])->assertRedirect('/contact');

        Notification::assertSentOnDemandTimes(NewLeadNotification::class, 1);
        Notification::assertSentOnDemand(NewLeadNotification::class, fn ($n, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === self::RECIPIENT && $notifiable->routes['mail'] !== 'public@example.test');
    }

    public function test_a_delivery_failure_escapes_the_queued_listener_so_the_queue_can_retry_it(): void
    {
        $this->profile();
        $lead = Lead::factory()->create(['source' => 'quote_form']);
        $this->mock(MailFactory::class, fn ($mock) => $mock->shouldReceive('mailer')->andThrow(new RuntimeException('SMTP is down')));

        // Invoked exactly as the worker invokes it. A try/catch + report()
        // + return inside handle() would make this assertion fail - and
        // would make a worker count a lost email as a success.
        try {
            (new SendNewLeadNotification)->handle(new LeadCreated($lead));
            $this->fail('The delivery failure was swallowed inside handle().');
        } catch (RuntimeException $exception) {
            $this->assertSame('SMTP is down', $exception->getMessage());
        }

        $this->assertTrue($lead->fresh()->exists, 'the lead is untouched by the failure');
    }

    public function test_the_request_stores_the_lead_and_returns_before_delivery_runs(): void
    {
        Queue::fake();
        $this->profile();

        $this->post('/quote', ['name' => 'نورة', 'phone' => '0500000000'])->assertRedirect('/quote')->assertSessionHas('lead_submitted', true);

        $lead = Lead::query()->sole();
        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === SendNewLeadNotification::class
            && $job->tries === 3
            && $job->backoff === [60, 300]
            && $job->data[0] instanceof LeadCreated
            && $job->data[0]->lead->is($lead));
        Queue::assertCount(1);
        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages(), 'delivery belongs to the worker, not the request');
    }

    public function test_on_a_database_queue_a_failing_delivery_retries_with_backoff_then_lands_in_failed_jobs_and_the_lead_survives(): void
    {
        config(['queue.default' => 'database', 'mail.mailers.smtp.password' => 'hunter2-secret']);
        Log::spy();
        $this->profile();
        $this->mock(MailFactory::class, fn ($mock) => $mock->shouldReceive('mailer')->andThrow(new RuntimeException('SMTP is down')));

        $this->post('/quote', ['name' => 'نورة', 'phone' => '0500000000'])->assertRedirect('/quote')->assertSessionHas('lead_submitted', true);
        $lead = Lead::query()->sole();
        $this->assertSame(1, DB::table('jobs')->count(), 'the request only queued the delivery');

        $worker = fn () => $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->run();

        $worker();
        $this->assertSame(1, DB::table('jobs')->where('attempts', 1)->count(), 'attempt 1 failed and was released');
        $this->assertSame(0, DB::table('failed_jobs')->count());

        $this->travel(59)->seconds();
        $worker();
        $this->assertSame(1, DB::table('jobs')->where('attempts', 1)->count(), 'not retried before the 60s backoff');

        $this->travel(2)->seconds();
        $worker();
        $this->assertSame(1, DB::table('jobs')->where('attempts', 2)->count(), 'attempt 2 failed and was released');

        $this->travel(301)->seconds();
        $worker();
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count(), 'after the last attempt the job is recorded, not lost');

        $lead->refresh();
        $this->assertNull($lead->deleted_at);
        $this->assertSame(LeadStatus::New, $lead->status);
        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context) use ($lead) {
            return str_contains($message, 'the lead is stored')
                && array_keys($context) === ['lead_id', 'exception']
                && $context['lead_id'] === $lead->id
                && ! str_contains(json_encode($context).$message, 'hunter2-secret')
                && ! str_contains(json_encode($context), $lead->phone);
        })->once();
    }

    public function test_failed_never_touches_the_lead_and_never_throws(): void
    {
        $this->profile();
        $lead = Lead::factory()->create(['source' => 'contact_form']);
        Log::spy();

        (new SendNewLeadNotification)->failed(new LeadCreated($lead), new RuntimeException('final failure'));

        $fresh = $lead->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(LeadStatus::New, $fresh->status);
        $this->assertNull($fresh->deleted_at);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_the_listener_is_queued_and_wired_to_the_event(): void
    {
        Event::fake([LeadCreated::class]);
        $this->profile();

        $this->post('/contact', ['name' => 'سلمى', 'phone' => '0500000000', 'message' => 'سؤال'])->assertRedirect('/contact');

        Event::assertDispatchedTimes(LeadCreated::class, 1);
        Event::assertListening(LeadCreated::class, SendNewLeadNotification::class);
        $this->assertInstanceOf(ShouldQueue::class, new SendNewLeadNotification);
    }

    public function test_exactly_one_email_goes_to_the_team_and_none_to_the_visitor(): void
    {
        $this->profile();

        $this->post('/contact', ['name' => 'سلمى', 'phone' => '0500000000', 'email' => 'visitor@example.test', 'message' => 'سؤال'])->assertRedirect('/contact');

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages, 'one lead, one email');
        /** @var Email $email */
        $email = $messages->first()->getOriginalMessage();
        $this->assertSame([self::RECIPIENT], array_map(fn ($address) => $address->getAddress(), $email->getTo()));
        $this->assertSame('رسالة تواصل جديدة — Vibe Clean Pro', $email->getSubject());
        $this->assertStringContainsString('visitor@example.test', $email->getHtmlBody());
        $this->assertStringNotContainsString('visitor@example.test', implode(',', array_map(fn ($a) => $a->getAddress(), [...$email->getTo(), ...$email->getCc(), ...$email->getBcc()])), 'no auto-reply to the visitor');
        $this->assertNotEmpty($email->getTextBody(), 'a plain-text alternative is included');
    }

    public function test_a_lead_created_from_the_panel_or_a_factory_sends_nothing(): void
    {
        Notification::fake();
        $this->profile();

        Lead::factory()->create();

        Notification::assertNothingSent();
    }

    public function test_the_notification_inbox_is_managed_from_the_business_profile_screen(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $this->actingAs($user);
        // The screen edits the single profile row with id 1 (see getRecord()).
        BusinessProfile::query()->forceCreate(['id' => 1, 'name' => 'Vibe Clean Pro', 'city' => 'الرياض']);

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', 'lead_notification_email' => 'not-an-email'])
            ->call('save')
            ->assertHasFormErrors(['lead_notification_email']);

        Livewire::test(ManageBusinessProfile::class)
            ->fillForm(['name' => 'Vibe Clean Pro', 'city' => 'الرياض', 'lead_notification_email' => 'team@example.test'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('team@example.test', BusinessProfile::query()->sole()->lead_notification_email);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function profile(array $attributes = []): BusinessProfile
    {
        return BusinessProfile::query()->create(['name' => 'Vibe Clean Pro', 'phone' => '+966500000000', 'lead_notification_email' => self::RECIPIENT, ...$attributes]);
    }
}
