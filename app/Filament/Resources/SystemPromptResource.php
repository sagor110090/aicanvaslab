<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemPromptResource\Pages;
use App\Filament\Resources\SystemPromptResource\RelationManagers;
use App\Models\SystemPrompt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SystemPromptResource extends Resource
{
    protected static ?string $model = SystemPrompt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'System Prompts';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('General Assistant'),
                    
                Forms\Components\Textarea::make('prompt')
                    ->required()
                    ->minLength(10)
                    ->rows(6)
                    ->placeholder('You are a helpful AI assistant...')
                    ->helperText('This will be used as the system prompt for AI conversations'),
                    
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->rows(3)
                    ->placeholder('A general-purpose assistant for everyday tasks')
                    ->helperText('Brief description of what this prompt does'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active prompts are available to users'),
                    
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
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
                    
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
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
            'index' => Pages\ListSystemPrompts::route('/'),
            'create' => Pages\CreateSystemPrompt::route('/create'),
            'edit' => Pages\EditSystemPrompt::route('/{record}/edit'),
        ];
    }
}
