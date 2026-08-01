<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Ad soyad')->required()->maxLength(120),

                TextInput::make('email')
                    ->label('E-posta')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(190),

                Select::make('role')
                    ->label('Yetki')
                    ->options([
                        'admin' => 'Yönetici — her şeye erişir',
                        'staff' => 'Personel — panele girer',
                    ])
                    ->default('staff')
                    ->required()
                    ->native(false),

                TextInput::make('password')
                    ->label('Parola')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->helperText('Düzenlerken boş bırakırsanız parola değişmez.'),
            ]);
    }
}
