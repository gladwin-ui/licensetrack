<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an active super administrator user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Create New Super Administrator User ===');

        $name = $this->ask('Enter administrator name');
        if (empty($name)) {
            $this->error('Name cannot be empty.');
            return 1;
        }

        $email = $this->ask('Enter email address');
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));
            return 1;
        }

        // WhatsApp Phone
        $phone = $this->ask('Enter WhatsApp phone number');
        try {
            $normalizedPhone = PhoneNumber::normalize($phone);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $password = $this->secret('Enter password');
        $passwordConfirm = $this->secret('Confirm password');

        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match.');
            return 1;
        }

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));
            return 1;
        }

        $user = new User([
            'name'              => $name,
            'email'             => $email,
            'phone'             => $normalizedPhone,
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        $user->status = User::STATUS_ACTIVE;
        $user->is_super_admin = true;
        $user->approved_at = now();
        $user->save();

        $this->info("Super administrator [{$user->name}] successfully created with email [{$user->email}] and phone [{$user->phone}].");
        return 0;
    }
}
