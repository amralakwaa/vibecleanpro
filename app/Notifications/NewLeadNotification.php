<?php

namespace App\Notifications;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\BusinessProfile;
use App\Models\Lead;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * The internal "a new lead arrived" email. Sent on demand to the managed
 * notification address (see SendNewLeadNotification), never to the
 * visitor - there is no customer auto-reply. Mail is the only channel
 * today; being a Notification is what lets another channel join later
 * without touching the forms.
 *
 * Not queued itself: the listener that sends it already runs on the
 * queue, and queuing twice would only add a second failure point.
 */
class NewLeadNotification extends Notification
{
    use SerializesModels;

    public function __construct(public Lead $lead) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->lead->loadMissing(['service', 'area']);

        return (new MailMessage)
            ->subject($this->subject())
            ->view(['mail.leads.new', 'mail.leads.new-text'], $this->viewData());
    }

    /**
     * Plain and factual: the kind of lead and the company - no urgency.
     */
    public function subject(): string
    {
        $businessName = BusinessProfile::query()->value('name') ?: config('app.name');

        return $this->typeLabel().' — '.$businessName;
    }

    public function typeLabel(): string
    {
        return match ($this->lead->source) {
            'quote_form' => 'طلب عرض سعر جديد',
            'contact_form_business' => 'رسالة تواصل جديدة من منشأة',
            default => 'رسالة تواصل جديدة',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $lead = $this->lead;

        $utm = collect([
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'utm_term' => $lead->utm_term,
            'utm_content' => $lead->utm_content,
        ])->filter();

        return [
            'lead' => $lead,
            'typeLabel' => $this->typeLabel(),
            'submittedAt' => $lead->created_at?->format('Y-m-d H:i').' (UTC)',
            'sourceLabel' => match ($lead->source) {
                'quote_form' => 'نموذج طلب عرض السعر',
                'contact_form' => 'نموذج التواصل',
                'contact_form_business' => 'نموذج التواصل (منشآت)',
                default => $lead->source,
            },
            'utm' => $utm,
            'adminUrl' => $this->adminUrl(),
        ];
    }

    /**
     * The panel URL comes from the app URL and Filament's own routing -
     * never a typed domain. If it cannot be generated (no panel booted in
     * this context), the email simply carries no link.
     */
    private function adminUrl(): ?string
    {
        try {
            return LeadResource::getUrl('edit', ['record' => $this->lead], panel: 'admin');
        } catch (Throwable) {
            return null;
        }
    }
}
