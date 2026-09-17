<?php

namespace Tests\Feature\Concerns;

/**
 * A public page whose optional data is missing must simply omit the
 * section - it must never print an editor-facing empty state ("no
 * published services", "no projects yet") to a customer.
 *
 * The check is deliberately specific: it matches the administrative
 * empty-state sentences the templates could produce, not every natural
 * use of "لا يوجد" / "لا توجد" in customer copy (e.g. "لا يتم الدفع عبر
 * الموقع"), so a page can be written in normal Arabic without tripping it.
 */
trait AssertsNoEmptyState
{
    /**
     * Administrative empty-state patterns, as they would appear in HTML.
     */
    private const EMPTY_STATE_PATTERNS = [
        // "لا توجد خدمات/مشاريع/أعمال/مقالات/عروض/مناطق/صور/بيانات ... منشورة|متاحة|حاليًا|بعد"
        '/لا (?:توجد|يوجد) (?:خدمات|مشاريع|أعمال|مقالات|عروض|مناطق|صور|بيانات|محتوى|أسئلة|نتائج|عناصر)[^<]{0,60}?(?:منشورة|منشور|متاحة|متاح|حاليًا|حاليا|بعد)/u',
        // "لم تتم إضافة ..." / "لم يتم نشر ..." / "لم تُنشر ..."
        '/لم (?:تتم|يتم) (?:إضافة|نشر|رفع|تسجيل)/u',
        '/لم تُنشر|لم يُنشر|لم تُضف|لم يُضف/u',
        // Generic placeholders
        '/لا يوجد محتوى|لا توجد بيانات|لا توجد نتائج|قريبًا سيتم إضافة/u',
    ];

    protected function assertNoCustomerFacingEmptyState(string $html, string $message = ''): void
    {
        foreach (self::EMPTY_STATE_PATTERNS as $pattern) {
            $this->assertDoesNotMatchRegularExpression($pattern, $html, $message ?: 'a customer-facing empty state leaked into the page');
        }
    }
}
