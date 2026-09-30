<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Artist;
use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class EventDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Event 3: Dewa 19 All Stars (similar to the LOKET screenshot)
        $dewa = Event::firstOrCreate(
            ['name' => 'Boyz II Men + Dewa 19 Live in Jakarta'],
            [
                'description' => 'Saksikan perpaduan musisi legendaris internasional Boyz II Men dan Dewa 19 feat Ari Lasso & Virzha di Indonesia Arena.',
                'location' => 'Indonesia Arena - GBK Senayan, Jakarta',
                'event_date' => '2026-12-09 19:30:00',
                'poster' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80',
                'status' => 'published',
            ]
        );

        if ($dewa->ticketCategories()->count() === 0) {
            $dewa->ticketCategories()->createMany([
                ['name' => 'Festival', 'price' => 350000, 'quota' => 200, 'sold' => 15],
                ['name' => 'VIP Seating', 'price' => 750000, 'quota' => 80, 'sold' => 8],
                ['name' => 'VVIP All Access', 'price' => 1250000, 'quota' => 30, 'sold' => 5],
            ]);
        }

        // Event 4: Tulus Tur Manusia
        $tulusEvent = Event::firstOrCreate(
            ['name' => 'Tulus: Tur Festival Musik 2026'],
            [
                'description' => 'Tur konser musik akbar bersama Tulus membawakan lagu-lagu hits terbaik di Jakarta.',
                'location' => 'Jakarta International Stadium',
                'event_date' => '2026-11-28 20:00:00',
                'poster' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=1200&q=80',
                'status' => 'published',
            ]
        );

        $tulusArtist = Artist::firstOrCreate(
            ['name' => 'Tulus'],
            ['bio' => 'Penyanyi dan penulis lagu Indonesia peraih 17 Anugerah Musik Indonesia.']
        );

        if (!$tulusEvent->artists()->where('artist_id', $tulusArtist->id)->exists()) {
            $tulusEvent->artists()->attach($tulusArtist->id);
        }

        if ($tulusEvent->ticketCategories()->count() === 0) {
            $tulusEvent->ticketCategories()->createMany([
                ['name' => 'Reguler Early Bird', 'price' => 120000, 'quota' => 100, 'sold' => 45],
                ['name' => 'Presale', 'price' => 200000, 'quota' => 150, 'sold' => 20],
                ['name' => 'VIP Area', 'price' => 450000, 'quota' => 50, 'sold' => 10],
            ]);
        }
    }
}
