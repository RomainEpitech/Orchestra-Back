<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDatabase extends Command
{
    protected $signature = 'db:reset';
    protected $description = 'Reset database to initial state';

    public function handle()
    {
        $this->call('migrate:fresh');
        $this->call('db:seed');
        
        $this->info('Database has been reset to initial state');
    }
}