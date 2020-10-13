<?php

namespace Abs\BusinessPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use App\Outlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sbu extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'sbus';
	public $timestamps = true;
	protected $fillable = [
		'name',
		'lob_id',
		'business_id',
	];
	protected $appends = ['switch_value'];

	public function getSwitchValueAttribute() {
		return !empty($this->attributes['deleted_at']) ? 'Inactive' : 'Active';
	}

	public function lob() {
		return $this->belongsTo('Abs\BusinessPkg\Lob', 'lob_id');
	}

	public static function getSbus($outlet_id) {
		$branch = Outlet::find($outlet_id);
		if (!$branch) {
			return response()->json(['success' => false, 'error' => 'Branch not found']);
		}
		$list = collect(self::whereIn('id', $branch->outlet_sbu()->pluck('id')->toArray())->where('company_id', Auth::user()->company_id)->select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select SBU']);
		return response()->json(['success' => true, 'sbu_list' => $list]);
	}

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

}
