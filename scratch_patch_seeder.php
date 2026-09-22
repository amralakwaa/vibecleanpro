<?php

// Patch AreaContentSeeder.php to:
// 1. Add robots_index = true to the seo->update() call
// 2. Fix idempotency: use contains() for isWave1
// 3. Move FAQs inside the main if block
// 4. Remove unused use DB import

$file = __DIR__.'/database/seeders/AreaContentSeeder.php';
$content = file_get_contents($file);

// 1. Remove unused DB import
$content = str_replace("use Illuminate\\Support\\Facades\\DB;\n", '', $content);

// 2. Remove unused Page import
$content = str_replace("use App\\Models\\Page;\n", '', $content);

// 3. Add robots_index to seo->update()
$old = "            \$seo->update([\n                'meta_title' => \$data['meta_title'],\n                'meta_description' => \$data['meta_description'],\n            ]);";
$new = "            \$seo->update([\n                'meta_title' => \$data['meta_title'],\n                'meta_description' => \$data['meta_description'],\n                'robots_index' => true,\n            ]);";
$content = str_replace($old, $new, $content);

// 4. Fix isWave1 detection
$old = '            $isWave1 = false;
            foreach ($blocks as $block) {
                if (isset($block->data[\'_version\']) && $block->data[\'_version\'] === self::VERSION) {
                    $isWave1 = true;
                    break;
                }
            }';
$new = '            $isWave1 = $blocks->contains(fn ($block) => ($block->data[\'_version\'] ?? null) === self::VERSION);';
$content = str_replace($old, $new, $content);

// 5. Move FAQs inside the main if block and fix the FAQs condition
$old = '            }

            // FAQs Update
            // Idempotent approach: Delete existing FAQs that match these questions to avoid dupes, or just clear and recreate if wave1
            // We\'ll just clear FAQs if we just cleared the blocks.
            if ($hasGenericBlocks || $isWave1) {
                $page->faqs()->delete();
                foreach ($data[\'faqs\'] as $index => $faqPair) {
                    $page->faqs()->create([
                        \'question\' => $faqPair[0],
                        \'answer\' => $faqPair[1],
                        \'sort_order\' => $index + 1,
                        \'is_active\' => true,
                    ]);
                }
            }
        }
    }
}';
$new = '
                // FAQs: clear and recreate in sync with the block refresh.
                $page->faqs()->delete();
                foreach ($data[\'faqs\'] as $index => $faqPair) {
                    $page->faqs()->create([
                        \'question\' => $faqPair[0],
                        \'answer\' => $faqPair[1],
                        \'sort_order\' => $index + 1,
                        \'is_active\' => true,
                    ]);
                }
            }
        }
    }
}';
$content = str_replace($old, $new, $content);

file_put_contents($file, $content);
echo "Done. Verifying syntax...\n";
passthru('php -l database/seeders/AreaContentSeeder.php');
