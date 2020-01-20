<?php
namespace Abs\BusinessPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class BusinessPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//LOB
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'lobs',
				'display_name' => 'Lobs',
			],
			[
				'display_order' => 1,
				'parent' => 'lobs',
				'name' => 'add-lob',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'lobs',
				'name' => 'delete-lob',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'lobs',
				'name' => 'delete-lob',
				'display_name' => 'Delete',
			],

			//SBUs
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'sbus',
				'display_name' => 'Sbus',
			],
			[
				'display_order' => 1,
				'parent' => 'sbus',
				'name' => 'add-sbu',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'sbus',
				'name' => 'delete-sbu',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'sbus',
				'name' => 'delete-sbu',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}