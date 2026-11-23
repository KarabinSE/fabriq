<?php

namespace Karabin\Fabriq\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MakeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fabriq:make-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new application user';

    /**
     * Array of custom fields to attach to the user.
     *
     * @var array
     */
    protected $customFields = [];

    /**
     * Execute the console command.
     *
     * Handle creation of the new application user.
     *
     * @return void
     */
    public function handle()
    {
        $email = $this->ask("What is the new user's email address?");
        $name = $this->ask("What is the new user's name?") ?: '';
        $password = $this->secret("What is the new user's password? (blank generates a random one)") ?: Str::random(32);
        $encrypt = $this->confirm('Should the password be encrypted?', true);
        $sendReset = $this->confirm('Do you want to send a password reset email?');

        if ($encrypt) {
            $password = bcrypt($password);
        }

        while ($custom = $this->ask('Do you have any custom user fields to add? Field=Value (blank continues)', false)) {
            [$key, $value] = explode('=', $custom);
            $this->customFields[$key] = value($value);
        }

        $userData = array_merge(compact('email', 'name', 'password'), $this->customFields);
        $this->validateEmail($userData['email']);
        $email = $userData['email'];

        try {
            app('db')->beginTransaction();

            app(config('auth.providers.users.model'))->create($userData);

            if ($sendReset) {
                Password::sendResetLink(compact('email'));

                $this->info("Sent password reset email to {$email}");
            }

            $this->info("Created new user for email {$email}");

            app('db')->commit();
        } catch (Exception $e) {
            $this->error($e->getMessage());

            $this->error('The user was not created');

            app('db')->rollBack();
        }
    }

    /**
     * Validate the email address against the configured user model.
     *
     * @param  string  $email
     */
    private function validateEmail($email): void
    {
        Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(config('auth.providers.users.model'), 'email')],
        ])->validate();
    }
}
