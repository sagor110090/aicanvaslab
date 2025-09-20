<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiModelResource\Pages;
use App\Filament\Resources\AiModelResource\RelationManagers;
use App\Models\AiModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AiModelResource extends Resource
{
    protected static ?string $model = AiModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static ?string $navigationLabel = 'AI Models';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('GPT-4 Turbo'),
                    
                Forms\Components\TextInput::make('model_id')
                    ->required()
                    ->maxLength(255)
                    ->label('Model ID (DeepSeek)')
                    ->placeholder('deepseek-v3')
                    ->helperText('The model identifier used in DeepSeek API'),
                    
                Forms\Components\Toggle::make('supports_images')
                    ->label('Supports Image Input')
                    ->helperText('Enable if this model can process image inputs'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active models are available to users'),
                    
                Forms\Components\TextInput::make('max_tokens')
                    ->numeric()
                    ->placeholder('4096')
                    ->helperText('Maximum tokens for response'),
                    
                Forms\Components\TextInput::make('temperature')
                    ->numeric()
                    ->default(0.7)
                    ->minValue(0)
                    ->maxValue(2)
                    ->step(0.1)
                    ->helperText('Controls randomness: 0 = deterministic, 2 = very creative'),
                    
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->placeholder('Advanced language model with enhanced reasoning capabilities...'),
                    
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Display order (lower numbers appear first)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('model_id')
                    ->label('Model ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Model ID copied')
                    ->limit(30),
                    
                                    
                Tables\Columns\IconColumn::make('supports_images')
                    ->label('Images')
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-x-mark'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('temperature')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                    
                Tables\Filters\TernaryFilter::make('supports_images')
                    ->label('Image Support'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order');
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
            'index' => Pages\ListAiModels::route('/'),
            'create' => Pages\CreateAiModel::route('/create'),
            'edit' => Pages\EditAiModel::route('/{record}/edit'),
        ];
    }
}