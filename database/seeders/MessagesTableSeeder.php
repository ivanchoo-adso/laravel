<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Message;
use Carbon\Carbon;

class MessagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Message::create([
            'body' => 'Mensaje de prueba',
            'autgoing' => false,
            'type' => 'text',
            'wa_id' => '12345',
            'wam_id' => '67890',
            'status' => 'sent',
            'caption' => 'Ejemplo',
            'data' => 'Datos adicionales',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
