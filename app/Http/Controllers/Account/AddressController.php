<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function index()
    {
        return view('account.addresses', [
            'addresses' => Auth::user()->addresses()->with('zone')->get(),
            'zones' => DeliveryZone::active()->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $address = Auth::user()->addresses()->create($data + [
            'is_default' => Auth::user()->addresses()->count() === 0,
        ]);

        if ($request->boolean('is_default')) {
            $address->makeDefault();
        }

        return back()->with('success', 'Adres kaydedildi.');
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($address);

        $address->update($this->validated($request));

        if ($request->boolean('is_default')) {
            $address->makeDefault();
        }

        return back()->with('success', 'Adres güncellendi.');
    }

    public function destroy(Address $address)
    {
        $this->authorizeAddress($address);

        $wasDefault = $address->is_default;
        $address->delete();

        // Varsayılan silindiyse kalanlardan biri varsayılan olsun
        if ($wasDefault) {
            Auth::user()->addresses()->first()?->makeDefault();
        }

        return back()->with('success', 'Adres silindi.');
    }

    public function makeDefault(Address $address)
    {
        $this->authorizeAddress($address);
        $address->makeDefault();

        return back()->with('success', 'Varsayılan adres güncellendi.');
    }

    private function authorizeAddress(Address $address): void
    {
        abort_unless($address->user_id === Auth::id(), 404);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:60'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['nullable', 'string', 'max:40'],
            'delivery_zone_id' => ['nullable', Rule::exists('delivery_zones', 'id')->where('is_active', true)],
            'address' => ['required', 'string', 'max:600'],
        ], [], [
            'title' => 'adres başlığı',
            'recipient_name' => 'alıcı adı',
            'delivery_zone_id' => 'bölge',
            'address' => 'adres',
        ]);
    }
}
