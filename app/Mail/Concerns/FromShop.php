<?php

namespace App\Mail\Concerns;

use Illuminate\Mail\Mailables\Address;

/**
 * Gönderen adresi mağaza ayarlarından gelsin; ayarda geçerli bir adres
 * yoksa .env'deki varsayılana düşer.
 */
trait FromShop
{
    protected function shopFrom(): ?Address
    {
        $email = (string) setting('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return new Address($email, (string) setting('shop_name', 'Ay Parçası'));
    }
}
