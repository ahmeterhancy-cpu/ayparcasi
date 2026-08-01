<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        // Çok adımlı doğrulama ekranında üst sınıfın kendi başlığı kalsın.
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        // Marka işareti zaten "kim olduğumuzu" söylüyor; başlık gereksiz.
        return null;
    }

    public function getSubHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubHeading();
        }

        return 'Yönetim paneline girmek için ekip hesabınızı kullanın.';
    }

    /** Vitrinde her yerde "parola" deniyor; panel de aynı sözcüğü kullansın. */
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->label('Parolanız');
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()->label('E-posta adresiniz');
    }
}
