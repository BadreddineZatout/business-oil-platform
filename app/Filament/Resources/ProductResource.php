<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Country;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 2;

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Supplier' => $record->supplier->name,
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
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('supplier')
                    ->required(),
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
                    ->collection('product_media'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('product_media'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->url(function ($record) {
                        return route('filament.admin.resources.suppliers.view', ['record' => $record->supplier_id]);
                    }, true),
                Tables\Columns\TextColumn::make('mainCategories.name')
                    ->label('Categories')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('subCategories.name')
                    ->label('Sub Categories')
                    ->default('--')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('description')->hidden(),
            ])
            ->filters([
                Filter::make('product_region')
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
                                fn (Builder $query, $region): Builder => $query->whereHas('supplier', function (Builder $query) use ($region): Builder {
                                    return $region == 'local' ? $query->where('country_id', Country::ALGERIA) : $query->where('country_id', '!=', Country::ALGERIA);
                                }),
                            );
                    }),
                Filter::make('Countries')
                    ->form([
                        Select::make('country')
                            ->options(Country::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['country'],
                                fn (Builder $query, $country): Builder => $query->whereHas('supplier', function (Builder $query) use ($country): Builder {
                                    return $query->where('country_id', $country);
                                }),
                            );
                    }),
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
                                fn (Builder $query, $country): Builder => $query->whereHas('supplier', function (Builder $query) use ($country): Builder {
                                    return $query->where('name', 'LIKE', $country . '%');
                                }),
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
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()->fromTable()->except(['image']),
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
