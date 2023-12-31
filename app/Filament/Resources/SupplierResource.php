<?php

namespace App\Filament\Resources;

use App\Actions\SendEmailAction;
use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers\ProductsRelationManager;
use App\Models\Category;
use App\Models\Country;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'category' => $record->categories->pluck('name')->implode(','),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return self::getUrl('view', ['record' => $record]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('country_id')
                    ->relationship('country', 'name')->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Repeater::make('emails')
                    ->schema([
                        Forms\Components\TextInput::make('other_mail'),
                    ])->columnSpanFull(),
                Forms\Components\TextInput::make('phone1')
                    ->label('Phone Number 1')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('phone2')
                    ->label('Phone Number 2')
                    ->tel(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->options(Category::whereNull('parent_id')->get()->pluck('name', 'id'))
                    ->multiple()
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\Select::make('sub_category')
                    ->label('Sub Category')
                    ->options(fn (Get $get) => Category::whereIn('parent_id', $get('category'))->pluck('name', 'id'))
                    ->multiple()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('image')
                    ->preserveFilenames()
                    ->disk('public_html')
                    ->collection('supplier_media'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('supplier_media')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mainCategories.name')
                    ->label('Categories')
                    ->listWithLineBreaks()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subCategories.name')
                    ->label('Sub Categories')
                    ->default('--')
                    ->listWithLineBreaks()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country.name')
                    ->description(fn (Supplier $record): string => $record->address)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')->toggleable(),
                Tables\Columns\TextColumn::make('phone1')
                    ->toggleable()
                    ->default('---'),
                Tables\Columns\TextColumn::make('phone2')
                    ->toggleable()
                    ->default('---'),
                Tables\Columns\TextColumn::make('description')->hidden(),
            ])
            ->filters([
                Filter::make('supplier_region')
                    ->form([
                        Radio::make('region')
                            ->options([
                                'local' => 'Local',
                                'expat' => 'Expat',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['region'],
                                function (Builder $query, $region): Builder {
                                    return $region == 'local' ? $query->where('country_id', Country::ALGERIA) : $query->where('country_id', '!=', Country::ALGERIA);
                                },
                            );
                    }),
                SelectFilter::make('Countries')
                    ->relationship('country', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Filter::make('Categories')
                    ->form([
                        Select::make('category')
                            ->relationship('mainCategories', 'name')
                            ->multiple()
                            ->searchable()
                            ->live()
                            ->preload(),
                        Select::make('sub_category')
                            ->relationship('subCategories', 'name', fn (Builder $query, Get $get) => $query->whereIn('parent_id', $get('category')))
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['category'],
                                function (Builder $query, $categories): Builder {
                                    return $query->whereHas('mainCategories', fn (Builder $query) => $query->whereIn('categories.id', $categories));
                                },
                            )
                            ->when(
                                $data['sub_category'],
                                function (Builder $query, $sub_categories): Builder {
                                    return $query->whereHas('subCategories', fn (Builder $query) => $query->whereIn('categories.id', $sub_categories));
                                },
                            );
                    }),
                Filter::make('companies')
                    ->form([
                        Select::make('company')
                            ->options(
                                ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9',  'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J',  'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O', 'P' => 'P', 'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T',  'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y', 'Z' => 'Z']
                            )->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['company'],
                                fn (Builder $query, $country): Builder => $query->where('name', 'LIKE', $country . '%'),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()->fromTable()->except(['image']),
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('send_email')
                        ->label('Send Email')
                        ->icon('heroicon-o-envelope')
                        ->color(Color::Lime)

                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->required(),
                            Forms\Components\Textarea::make('message')
                                ->required(),
                            Forms\Components\FileUpload::make('attachment')
                                ->preserveFilenames(),
                        ])
                        ->action(function (array $data, Collection $records, SendEmailAction $sendEmailAction) {
                            $records->each(function ($record) use ($sendEmailAction, $data) {
                                $sendEmailAction->handle($record->email, $data);
                            });
                        })->failureNotification(
                            Notification::make()
                                ->danger()
                                ->title('Email Not Sent')
                                ->body('The email has not been sent.'),
                        )
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Email Sent')
                                ->body('The email has been sent successfully.'),
                        )->deselectRecordsAfterCompletion(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()->fromTable()->except(['image']),
                    ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
