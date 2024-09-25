<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as infolistSection;
use Filament\Forms\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?int $navigationSort = 3;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('brand')
                        ->rules('min:2')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category_id')->required()->options(Category::where('is_active', true)->get()->pluck('name','id'))
                        ->label('Category')
                        ->searchable() ,
                    Section::make('Details')
                        ->description("Insert the product's details over here")
                        ->schema([
                            Forms\Components\TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->prefix('€'),
                            Forms\Components\TextInput::make('discountPercentage')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('stock')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('rating')
                                ->required()
                                ->numeric(),
                        ])->columns(),
                ]),
                Group::make()->schema([
                    Forms\Components\MarkdownEditor::make('description')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Section::make('Images')->collapsed()->schema([
                   Forms\Components\TextInput::make('thumbnail')
                       ->required()
                        ->maxLength(255),
                        Forms\Components\TextInput::make('images'),
                        //Forms\Components\FileUpload::make('thumbnail')->disk('public')->directory('thumbnails'),
                    ])->columnSpan(1),
                ]),
            ])->columns([
                'default'=>1,
                'md'=>2,
                'lg'=>3,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('eur')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric('1')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    //Tables\Actions\DeleteBulkAction::make()->authorize((new ProductPolicy)->delete(auth()->user(),new Product() )),
                ]),
                BulkAction::make('Change Category')
                    ->form([
                        Forms\Components\Select::make('category')
                            ->required()
                            ->options((Category::where('is_active', true)->get()->pluck('name','id')))
                        ->searchable(),
                    ])
                    ->requiresConfirmation()
                    ->authorize(fn (User $user) => $user->can('Edit Product', Product::class))
                    ->action(fn ($records, $data) => $records->each->update($data)),
                BulkAction::make('Rate')
                ->form([
                    Forms\Components\TextInput::make('rating')
                    ->required()
                    ->numeric()
                ])
                    ->requiresConfirmation()
                    ->action(fn ($records, $data) => $records->each->update($data)),
            ]);
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        $user = auth()->user();
        $categoryName = optional(Category::find($user->category_id))->name ?? 'Categoria padrão';
        return $infolist->schema([
            infolistSection::make([
                TextEntry::make('title')
                ->size('lg')
                ->weight('bold')
                ->hiddenLabel()
                ->translateLabel(),
                TextEntry::make('description')
                ->hiddenLabel()
                ->html()
                ->alignJustify(),
            ])->columnSpan(3),
            infolistSection::make([
                TextEntry::make('brand')->size('lg'),
                TextEntry::make('price')->size('lg'),
                TextEntry::make('stock')->size('lg'),
                TextEntry::make('rating')->size('lg'),
                TextEntry::make('discountPercentage')->size('lg'),
                TextEntry::make('category.name')->size('lg')->color('primary')->url('http://firstlesson.test/admin/categories'),
                //IconEntry::make('category.is_active')->boolean()
            ])->columns(),
            infolistSection::make([
                ImageEntry::make('thumbnail')->disk('s3')->columnSpan(1)->square(),
                infolistSection::make([
                    ImageEntry::make('images')->disk('s3')->columnSpan(1),
                ])->collapsed()
            ]),
        ])->columns([
            'default'=>1,
            'md'=>2,
            'lg'=>3]);
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
            'view' => Pages\ViewProduct::route('/{record}/view'),
        ];
    }

}
