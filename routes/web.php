<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\FavoriteController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// --- Mağaza ---------------------------------------------------------------
Route::get('/magaza', [ShopController::class, 'index'])->name('shop.index');
Route::get('/arama', [ShopController::class, 'search'])->name('shop.search');
Route::get('/kategori/{category:slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/urun/{product:slug}', [ShopController::class, 'product'])->name('shop.product');
Route::get('/urun/{product:slug}/hizli-bakis', [ShopController::class, 'quickView'])->name('shop.quickview');

// --- Sepet ----------------------------------------------------------------
Route::get('/sepet', [CartController::class, 'index'])->name('cart.index');
Route::post('/sepet', [CartController::class, 'store'])->name('cart.store');
Route::patch('/sepet/{key}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/sepet/{key}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::post('/sepet/kupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/sepet/kupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// --- Kasa & ödeme ---------------------------------------------------------
Route::get('/kasa', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/kasa', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/odeme/{order:number}', [PaymentController::class, 'redirect'])->name('payment.redirect');
Route::get('/odeme/donus/{order:number}', [PaymentController::class, 'handleReturn'])->name('payment.return');
Route::post('/odeme/bildirim', [PaymentController::class, 'callback'])->name('payment.callback');

Route::get('/siparis/{order:number}', [CheckoutController::class, 'show'])->name('order.show');

// --- İçerik sayfaları -----------------------------------------------------
Route::get('/hakkimizda', [PageController::class, 'about'])->name('page.about');
Route::get('/iletisim', [PageController::class, 'contact'])->name('page.contact');
Route::get('/teslimat', [PageController::class, 'delivery'])->name('page.delivery');
Route::get('/sikca-sorulan-sorular', [PageController::class, 'faq'])->name('page.faq');
Route::get('/blog', [PageController::class, 'blog'])->name('page.blog');
Route::get('/blog/{post:slug}', [PageController::class, 'post'])->name('page.post');

// --- Küçük etkileşimler ---------------------------------------------------
Route::post('/stok-sor', [InquiryController::class, 'stock'])->name('inquiry.stock');
Route::post('/bulten', [InquiryController::class, 'newsletter'])->name('inquiry.newsletter');

// --- Hesap: giriş / kayıt / parola ----------------------------------------
Route::get('/giris', [LoginController::class, 'show'])->name('login');
Route::post('/giris', [LoginController::class, 'login'])->middleware('throttle:8,1');
Route::post('/cikis', [LoginController::class, 'logout'])->name('logout');

Route::get('/kayit', [RegisterController::class, 'show'])->name('register');
Route::post('/kayit', [RegisterController::class, 'store'])->middleware('throttle:8,1');

Route::get('/sifremi-unuttum', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendLink'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::post('/sifre-sifirla', [PasswordResetController::class, 'update'])
    ->middleware('throttle:8,1')
    ->name('password.update');

// --- Hesabım --------------------------------------------------------------
Route::middleware('auth')->prefix('hesabim')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');

    Route::get('/bilgilerim', [AccountController::class, 'profile'])->name('profile');
    Route::put('/bilgilerim', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/parola', [AccountController::class, 'updatePassword'])->name('password.update');

    Route::get('/siparislerim', [AccountOrderController::class, 'index'])->name('orders');
    Route::get('/siparislerim/{order:number}', [AccountOrderController::class, 'show'])->name('order');
    Route::post('/siparislerim/{order:number}/tekrarla', [AccountOrderController::class, 'reorder'])->name('order.reorder');

    Route::get('/adreslerim', [AddressController::class, 'index'])->name('addresses');
    Route::post('/adreslerim', [AddressController::class, 'store'])->name('addresses.store');
    Route::put('/adreslerim/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/adreslerim/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/adreslerim/{address}/varsayilan', [AddressController::class, 'makeDefault'])->name('addresses.default');

    Route::get('/favorilerim', [FavoriteController::class, 'index'])->name('favorites');
    Route::post('/favorilerim/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::post('/favori-birlestir', [FavoriteController::class, 'merge'])->name('favorites.merge');
});
