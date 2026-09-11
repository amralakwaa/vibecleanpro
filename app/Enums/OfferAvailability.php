<?php

namespace App\Enums;

/**
 * Derived, never stored - see Offer::availability(). Distinct from the
 * Page's own Published/indexable status: an Offer's Page can stay
 * Published (so the URL keeps working and old links/SEO signals aren't
 * lost) while the offer itself has separately expired.
 */
enum OfferAvailability: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Expired = 'expired';
}
