<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Role::all()->count() == 0) {
            /** @var Role $adminRole */
            $adminRole = Role::create(['name' => 'admin']);

            /** @var Role $writerRole */
            $writerRole = Role::create(['name' => 'writer']);
            /** @var Role $supportRole */
            $supportRole = Role::create(['name' => 'support']);

            /** @var Role $employerRole */
            $employerRole = Role::create(['name' => 'employer']);
            /** @var Role $freelancerRole */
            $freelancerRole = Role::create(['name' => 'freelancer']);

            $writerPermissions = [
                'create_article',
                'update_article',
                'delete_article',
            ];
            foreach ($writerPermissions as $p) {
                $permission = $this->permission($p);
                $writerRole->givePermissionTo($permission);
                $adminRole->givePermissionTo($permission);
            }

            $supportPermissions = [
                'open_ticket',
                'close_ticket',
                'answer_ticket',
                'see_ticket',
            ];
            foreach ($supportPermissions as $p) {
                $permission = $this->permission($p);
                $supportRole->givePermissionTo($permission);
                $adminRole->givePermissionTo($permission);
            }

            $employerPermissions = [
                'open_ticket',
                'close_ticket',
                'answer_ticket',
                'see_ticket',
                'create_project',
                'finish_project',
                'accept_request',
                'create_secure_payment',
                'pay_secure_payment',
                'see_freelancer_portfolio',
                'see_freelancer_resume',
                'charge_wallet',
            ];
            foreach ($employerPermissions as $p) {
                $permission = $this->permission($p);
                $employerRole->givePermissionTo($permission);
                $adminRole->givePermissionTo($permission);
            }

            $freelancerPermissions = [
                'open_ticket',
                'close_ticket',
                'answer_ticket',
                'see_ticket',
                'send_request',
                'request_secure_payment',
                'upgrade_business',
                'create_portfolio',
                'update_portfolio',
                'delete_portfolio',
                'see_portfolio',
                'create_resume',
                'update_resume',
                'delete_resume',
                'see_resume',
                'see_finished_projects',
                'upload_documents',
                'charge_wallet',
                'buy_request_package',
            ];
            foreach ($freelancerPermissions as $p) {
                $permission = $this->permission($p);
                $freelancerRole->givePermissionTo($permission);
                $adminRole->givePermissionTo($permission);
            }
        }
    }

    private function permission($p) {
        try{
            /** @var Permission $permission */
            $permission = Permission::findByName($p);
        }catch (\Exception $exception) {
            $permission = Permission::create(['name' => $p]);
        }
        return $permission;
    }
}
