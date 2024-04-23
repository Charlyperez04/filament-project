<?php

namespace App\Filament\Resources;

use App\Enums\ProductTypeEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel= 'Products';
    protected static ?int $navigationSort = 0;

    protected static ?string $navigationGroup = 'Shop';

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit=20;

    // protected static ?string $activeNavigationIcon= 'heroicon-o-check-badge';

    public static function getNavigationBadge():?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }
    public static function getGlobalSearchResultDetails(Model $record):array
    {
        return [
            'Brand'=> $record->brand->name,
            'Description' => $record->description,
        ];
    }

    public static function getGlobalSearchEloquentQuery():Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['brand']);
    }
  public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                ->schema([
                    Section::make()
                    ->schema([
                        TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->unique()
                        ->afterStateUpdated(function(string $operation, $state, Forms\Set $set){
                           if($operation!== 'create'){
                               return;
                           }
                            $set('slug', Str::slug($state));

                        }),
                        TextInput::make('slug')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->unique(Product::class, column: 'slug', ignoreRecord:true),
                        MarkdownEditor::make('description')
                        ->columnSpan(span:'full'),
                               ])->columns(2),
                               Section::make(heading: 'Pricing & Inventory')
                               ->schema([
                                    TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->unique()
                                    ->required(),
                                    TextInput::make('price')
                                    ->numeric()
                                    ->rules('regex:/^\d{1,6}(\.\d{0,2})?$/')
                                    ->required(),
                                    TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required(),
                                    Select::make('type')
                                    ->options([
                                        'downloadable' => ProductTypeEnum::DOWNLOADABLE->value,
                                        'deliverable' => ProductTypeEnum::DELIVERABLE->value,
                                    ])->required(),
                                          ])->columns(2),
                    ]),

                    Group::make()
                    ->schema([
                        Section::make(heading: 'Status')
                        ->schema([
                            Toggle::make('is_visible')
                            ->label('Visibility')
                            ->helperText('Enable or disable the visibility')
                            ->default(true),
                            Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Enable or disable the featured status'),
                            DatePicker::make('published_at')
                            ->label('Avaiability')
                            ->default(now()),
                        ]),
                                   Section::make(heading: 'Image')
                                      ->schema([
                                        FileUpload::make('image')
                                        ->directory('form-attachments')
                                        ->preserveFilenames()
                                        ->image()
                                        ->imageEditor()
                                      ])->collapsible(),
                                      Section::make(heading: 'Associations')
                                      ->schema([
                                        Select::make('brand_id')
                                        ->relationship('brand', 'name'),
                                      ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make(name:'image'),
                Tables\Columns\TextColumn::make(name:'name')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make(name:'brand.name')
                ->searchable()
                ->sortable()
                ->toggleable(),
                Tables\Columns\IconColumn::make(name:'is_visible')->boolean()
                ->sortable()
                ->label('Visibility')
                ->toggleable(),
                Tables\Columns\TextColumn::make(name:'price')
                ->sortable()
                ->toggleable(),
                Tables\Columns\TextColumn::make(name:'quantity')
                ->sortable()
                ->toggleable(),
                Tables\Columns\TextColumn::make(name:'published_at')
                ->date(),
                Tables\Columns\TextColumn::make(name:'type'),
            ])
            ->filters([
                TernaryFilter::make('is_visible')
                ->label('Visibility')
                ->boolean()
                ->trueLabel('Only Visible Products')
                ->falseLabel('Only Hidden Products')
                ->native(false),
                SelectFilter::make('brand')
                ->relationship('brand', 'name')
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),

                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
