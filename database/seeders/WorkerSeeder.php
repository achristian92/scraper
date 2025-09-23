<?php

namespace Database\Seeders;

use App\Models\Worker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Worker::insert([
            ['full_name' => 'Ana Torres', 'document' => 'DNI 12345678', 'email' => 'ana@demo.com', 'created_at'=>now(),'updated_at'=>now()],
            ['full_name' => 'Luis Pérez', 'document' => 'DNI 87654321', 'email' => 'luis@demo.com', 'created_at'=>now(),'updated_at'=>now()],
            ['full_name' => 'Carlos Vega','document' => null, 'email' => null, 'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
