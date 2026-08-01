<?php

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
