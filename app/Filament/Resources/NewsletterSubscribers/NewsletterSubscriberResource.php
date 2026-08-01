<?php

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Bülten aboneleri';

    protected static ?string $modelLabel = 'abone';

    protected static ?string $pluralModelLabel = 'aboneler';

    protected static ?string $recordTitleAttribute = 'email';

    public static function table(Table $table): Table
    {
        return NewsletterSubscribersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterSubscribers::route('/'),
        ];
    }
}
