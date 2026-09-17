<?php

namespace Tests\Feature\Concerns;

use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

/**
 * The guard must catch the administrative empty states the templates can
 * produce, and must let ordinary customer Arabic through.
 */
class AssertsNoEmptyStateTest extends TestCase
{
    use AssertsNoEmptyState;

    public function test_it_catches_administrative_empty_states(): void
    {
        foreach ([
            '<p>لا توجد مقالات منشورة حاليًا.</p>',
            '<p>لا توجد عروض متاحة حاليًا.</p>',
            '<h2>لا توجد أعمال منشورة بعد</h2>',
            '<p>لا توجد بيانات منشورة</p>',
            '<p>لا يوجد محتوى</p>',
            '<p>لم يتم نشر أي خدمة بعد.</p>',
        ] as $html) {
            try {
                $this->assertNoCustomerFacingEmptyState($html);
            } catch (AssertionFailedError) {
                continue;
            }

            $this->fail('The guard let an empty state through: '.$html);
        }
    }

    public function test_it_allows_natural_customer_copy(): void
    {
        $this->assertNoCustomerFacingEmptyState('<p>أرسل تفاصيل احتياجك وسنعود إليك بعرض سعر. لا يتم الدفع عبر الموقع.</p><p>لا يوجد دفع عبر الموقع - تطلب، نتواصل معك، ثم تقرر.</p><p>لا توجد رسوم على المعاينة.</p>');
        $this->addToAssertionCount(1);
    }
}
