<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SbuU982020 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('sbus', function (Blueprint $table) {
			$table->unsignedInteger('business_id')->nullable()->after('company_id');

			$table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('sbus', function (Blueprint $table) {
			$table->dropForeign('sbus_business_id_foreign');

			$table->dropColumn('business_id');
		});
	}
}
