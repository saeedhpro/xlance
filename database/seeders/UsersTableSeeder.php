<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(User::all()->count() == 0) {
            /** @var User $user */
            $user = User::create([
                'first_name' => 'xlance',
                'last_name' => 'xlance',
                'username' => 'xlance',
                'email' => 'info@xlance.ir',
                'password' => bcrypt(123456789),
            ]);
            $user->markEmailAsVerified();
            $role = Role::findByName('admin');
            $user->assignRole($role);
            $user->profile()->create();
            $user->wallet()->create();
        }
    }
}
