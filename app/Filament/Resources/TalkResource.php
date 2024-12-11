<?php

namespace App\Filament\Resources;

use App\Enums\TalkLength;
use App\Enums\TalkStatus;
use Filament\Forms;
use App\Models\Talk;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TalkResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TalkResource\RelationManagers;

class TalkResource extends Resource
{
    protected static ?string $model = Talk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('abstract')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('speaker_id')
                    ->relationship('speaker', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->filtersTriggerAction(function ($action) {
                return $action->button()->label('Filters');
            })
            ->persistFiltersInSession()
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->description(function (Talk $record) {
                        return Str::limit($record->abstract, 40, '...');
                    }),
                Tables\Columns\ImageColumn::make('speaker.avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(function (Talk $record) {
                        return 'https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=' . urlencode($record->speaker->name);
                    }),
                Tables\Columns\TextColumn::make('speaker.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('new_talk'),
                Tables\Columns\TextColumn::make('status')
                    ->color(function (TalkStatus $state) {
                        return $state->getColor();
                    })
                    ->badge(),
                IconColumn::make('length')
                    ->icon(function (TalkLength $state) {
                        return $state->getIcon();
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('new_talk'),
                SelectFilter::make('spaker')
                    ->relationship('speaker', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Filter::make('has_avatar')
                    ->label('Show only Speakers with Avatars')
                    ->toggle()
                    ->query(function ($query) {
                        return $query->whereHas('speaker', function (Builder $query) {
                            $query->whereNotNull('avatar');
                        });
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->visible(function (Talk $record) {
                            return $record->status === TalkStatus::SUBMITTED;
                        })
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Talk $record) {
                            $record->approve();
                        })
                        ->after(function () {
                            Notification::make()
                                ->success()
                                ->duration(1000)
                                ->title('Talk Approved')
                                ->body('The speaker has been notified that their talk was approved.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('reject')
                        ->visible(function (Talk $record) {
                            return $record->status === TalkStatus::SUBMITTED;
                        })
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Talk $record) {
                            $record->reject();
                        })
                        ->after(function () {
                            Notification::make()
                                ->danger()
                                ->duration(1000)
                                ->title('Talk Rejected')
                                ->body('The speaker has been notified that their talk was rejected.')
                                ->send();
                        })
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->action(function (Collection $records) {
                            $records->each->approve();
                        })
                        ->icon('heroicon-o-check-circle')
                        ->color('success'),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->tooltip('This will export all the current filtered records.')
                    ->action(function ($livewire) {
                        ray($livewire->getFilteredTableQuery());
                    })
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
            'index' => Pages\ListTalks::route('/'),
            'create' => Pages\CreateTalk::route('/create'),
            // 'edit' => Pages\EditTalk::route('/{record}/edit'),
        ];
    }
}
