<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The account page shows two-factor authentication only. Name, email and
 * password stay managed from the Users screen, exactly as before, so no
 * user gains a new ability to change their own account details here.
 */
class AccountSecurity extends EditProfile
{
    public function getTitle(): string|Htmlable
    {
        return 'أمان الحساب';
    }

    public static function getLabel(): string
    {
        return 'أمان الحساب';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getMultiFactorAuthenticationContentComponent()
                ?? Text::make('التحقق الثنائي غير مفعّل في لوحة الإدارة.'),
        ]);
    }
}
