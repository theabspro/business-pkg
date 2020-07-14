<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessOutletAddSacCode extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('business_outlet', function (Blueprint $table) {
			$table->string('sac_code', 64)->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('business_outlet', function (Blueprint $table) {
			$table->dropColumn('sac_code');
		});
	}
}
