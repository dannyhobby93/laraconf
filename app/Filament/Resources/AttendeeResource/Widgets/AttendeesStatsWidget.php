<?php

namespace App\Filament\Resources\AttendeeResource\Widgets;

use App\Models\Attendee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendeesStatsWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Attendees', Attendee::count())
                ->description('Total number of attendees')
                ->icon('heroicon-o-user-group')
                ->color('danger')
                ->chart([
                    1,
                    2,
                    3,
                    4,
                    2,
                    1,
                    1
                ]),
            Stat::make('Total Revenue', Attendee::sum('ticket_cost') / 100),
        ];
    }
}
